<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data cleanup: remove duplicate voucher numbers that block the unique index
 * created by 2026_07_31_000001_add_unique_voucher_index.
 *
 * For every group of vouchers sharing (voucher_type_id, voucher_no,
 * fiscal_year_id), we keep the "canonical" row — the one referenced by another
 * voucher (voucher_references.ref_voucher_id) if any, otherwise the lowest id —
 * and archive + delete the rest, including their child rows (voucher_entries,
 * voucher_parties, voucher_references, stock journals and their entries).
 *
 * Nothing is lost: every removed row is first copied into a *_duplicates_archive
 * table (mirroring the source schema), so down() can restore it.
 *
 * Runs BEFORE 2026_07_31_000001 so the unique index migration succeeds.
 */
return new class extends Migration
{
    /** Tables we may need to touch, in archive (leaf-first) dependency order. */
    private const CHILD_TABLES = [
        'stock_journal_godown_entries' => 'stock_journal_entry_id',
        'stock_journal_entries' => 'stock_journal_id',
        'stock_journals' => 'id',
        'voucher_references' => 'voucher_id',
        'voucher_parties' => 'voucher_id',
        'voucher_entries' => 'voucher_id',
        'vouchers' => 'id',
    ];

    public function up(): void
    {
        // Archive tables use MySQL-only DDL (CREATE TABLE ... LIKE); on other
        // drivers (SQLite test DBs) we still dedupe but skip the backup copy.
        $this->archiving = $this->supportsArchiveTables();
        if ($this->archiving) {
            $this->createArchiveTables();
        }

        DB::transaction(function () {
            $groups = DB::table('vouchers')
                ->select('voucher_type_id', 'voucher_no', 'fiscal_year_id')
                ->groupBy('voucher_type_id', 'voucher_no', 'fiscal_year_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($groups as $group) {
                $candidates = DB::table('vouchers')
                    ->where('voucher_type_id', $group->voucher_type_id)
                    ->where('voucher_no', $group->voucher_no)
                    ->where('fiscal_year_id', $group->fiscal_year_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                if ($candidates->count() < 2) {
                    continue;
                }

                // Prefer the row referenced by another voucher; otherwise the
                // lowest id. Any references pointing at a removed duplicate are
                // repointed to the canonical row instead of being deleted.
                $referenced = DB::table('voucher_references')
                    ->whereIn('ref_voucher_id', $candidates)
                    ->pluck('ref_voucher_id')
                    ->map(fn ($id) => (int) $id)
                    ->first();

                $keep = $referenced ?? $candidates->first();

                foreach ($candidates as $id) {
                    if ($id === $keep) {
                        continue;
                    }
                    $this->archiveAndDeleteVoucher($id, $keep);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            // Restore in reverse dependency order (parents before children).
            foreach (array_reverse(array_keys(self::CHILD_TABLES)) as $table) {
                $archive = $table.'_duplicates_archive';
                if (! Schema::hasTable($archive)) {
                    continue;
                }
                // Explicit column list — the archive has an extra archived_at
                // column, so SELECT * would mismatch the target's column count.
                $columns = array_values(array_filter(
                    Schema::getColumnListing($archive),
                    fn ($col) => $col !== 'archived_at'
                ));
                $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
                DB::statement(
                    "INSERT IGNORE INTO `{$table}` ({$colList}) SELECT {$colList} FROM `{$archive}`"
                );
            }
        });
    }

    /**
     * Archive and remove one duplicate voucher and everything owned by it.
     * References that point at the removed voucher are moved to $keepId.
     */
    private function archiveAndDeleteVoucher(int $voucherId, int $keepId): void
    {
        // Repoint inbound references (voucher X referenced this duplicate)
        DB::table('voucher_references')
            ->where('ref_voucher_id', $voucherId)
            ->update(['ref_voucher_id' => $keepId]);

        $stockJournalId = DB::table('vouchers')
            ->where('id', $voucherId)
            ->value('stock_journal_id');

        $this->archiveWhere('vouchers', 'id', $voucherId);

        if ($stockJournalId) {
            // Archive the stock journal subtree (godown entries -> entries -> journal)
            $entryIds = DB::table('stock_journal_entries')
                ->where('stock_journal_id', $stockJournalId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($entryIds) {
                $this->archiveWhere('stock_journal_godown_entries', 'stock_journal_entry_id', $entryIds);
                $this->archiveWhere('stock_journal_entries', 'id', $entryIds);
            }
            $this->archiveWhere('stock_journals', 'id', $stockJournalId);
        }

        $this->archiveWhere('voucher_entries', 'voucher_id', $voucherId);
        $this->archiveWhere('voucher_parties', 'voucher_id', $voucherId);
        $this->archiveWhere('voucher_references', 'voucher_id', $voucherId);
    }

    /** Copy matching rows to the archive table, then delete them from source. */
    private function archiveWhere(string $table, string $column, int|array $ids): void
    {
        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) {
            return;
        }

        if ($this->archiving) {
            $archive = $table.'_duplicates_archive';

            // Build an explicit column list (excluding archived_at) so the INSERT
            // is independent of physical column order across source/archive tables.
            $columns = array_values(array_filter(
                Schema::getColumnListing($archive),
                fn ($col) => $col !== 'archived_at'
            ));
            $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_values($ids);

            DB::statement(
                "INSERT INTO `{$archive}` ({$colList}, `archived_at`) ".
                "SELECT {$colList}, NOW() FROM `{$table}` WHERE `{$column}` IN ({$placeholders})",
                $params
            );
        }
        DB::table($table)->whereIn($column, $ids)->delete();
    }

    /** Create (fresh) *_duplicates_archive tables mirroring the source schemas. */
    private function createArchiveTables(): void
    {
        foreach (array_keys(self::CHILD_TABLES) as $table) {
            $archive = $table.'_duplicates_archive';
            Schema::dropIfExists($archive);
            DB::statement("CREATE TABLE `{$archive}` LIKE `{$table}`");
            Schema::table($archive, function ($t) {
                $t->timestamp('archived_at')->nullable();
            });
        }
    }

    /** Whether the current driver supports CREATE TABLE ... LIKE (MySQL/MariaDB). */
    private function supportsArchiveTables(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private bool $archiving = false;
};
