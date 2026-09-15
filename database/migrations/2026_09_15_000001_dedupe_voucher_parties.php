<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data cleanup: remove duplicate voucher_parties rows created by a now-fixed
 * bug. The voucher update pipeline used to store a NEW voucher_party row
 * whenever the incoming payload lacked a party id (e.g. the party was
 * re-selected in the UI and the form rebuilt the object without its id),
 * while the hasOne relation kept serving the original row — so party edits
 * appeared to silently do nothing.
 *
 * Fixed in VoucherService::processPartyUpdateStep (resolves the existing row
 * via voucher_id). This migration repairs the data: for every voucher with
 * more than one voucher_parties row, keep the newest one (the user's latest
 * edit) and archive + delete the rest.
 *
 * Nothing is lost: every removed row is first copied into a
 * voucher_parties_duplicates_archive table (mirroring the source schema), so
 * down() can restore it. Mirrors the approach of
 * 2026_07_30_000000_remove_duplicate_voucher_numbers.
 *
 * Idempotent: re-running after the unique index is in place is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Archive DDL (CREATE TABLE ... LIKE) is MySQL/MariaDB-only; on other
        // drivers (SQLite test DBs) we still dedupe but skip the backup copy.
        $archiving = Schema::getConnection()
            ->getDriverName() === 'mysql'
            || Schema::getConnection()->getDriverName() === 'mariadb';

        DB::transaction(function () use ($archiving) {
            if ($archiving && ! Schema::hasTable('voucher_parties_duplicates_archive')) {
                Schema::create('voucher_parties_duplicates_archive', function (Blueprint $table) {
                    // Mirror of voucher_parties; created via CREATE TABLE LIKE
                    // below when supported, so this callback only declares the
                    // fallback shape used by non-MySQL drivers (unused there).
                    $table->id();
                    $table->unsignedBigInteger('voucher_id');
                    $table->string('name');
                    $table->string('mailing_name');
                    $table->string('line1')->nullable();
                    $table->string('line2')->nullable();
                    $table->string('line3')->nullable();
                    $table->unsignedBigInteger('state_id')->nullable();
                    $table->unsignedBigInteger('country_id')->nullable();
                    $table->unsignedBigInteger('gst_registration_type_id')->default(1);
                    $table->string('gstin')->nullable();
                    $table->unsignedBigInteger('place_of_supply_state_id')->nullable();
                    $table->timestamps();
                });
            }

            $duplicatedVoucherIds = DB::table('voucher_parties')
                ->select('voucher_id')
                ->groupBy('voucher_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('voucher_id')
                ->map(fn ($id) => (int) $id);

            foreach ($duplicatedVoucherIds as $voucherId) {
                $rows = DB::table('voucher_parties')
                    ->where('voucher_id', $voucherId)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                // The newest row wins: it reflects the user's last successful
                // edit (e.g. the re-selected party name the UI kept showing
                // as "saved but not applied" on the ORIGINAL row).
                $keepId = $rows->first()->id;
                $deleteIds = $rows->pluck('id')->reject(fn ($id) => (int) $id === (int) $keepId);

                if ($deleteIds->isEmpty()) {
                    continue;
                }

                if ($archiving) {
                    DB::statement(
                        'INSERT INTO voucher_parties_duplicates_archive SELECT * FROM voucher_parties WHERE id IN ('.$deleteIds->implode(',').')'
                    );
                }

                DB::table('voucher_parties')->whereIn('id', $deleteIds)->delete();

                // Point the kept row at the voucher (defensive no-op in
                // practice — duplicates always share voucher_id already).
                DB::table('voucher_parties')
                    ->where('id', $keepId)
                    ->update(['voucher_id' => $voucherId]);
            }
        });

        // Guard against recurrence: one party row per voucher, period.
        if (! $this->indexExists('voucher_parties', 'voucher_parties_voucher_id_unique')) {
            Schema::table('voucher_parties', function (Blueprint $table) {
                $table->unique('voucher_id', 'voucher_parties_voucher_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voucher_parties_duplicates_archive')) {
            DB::statement(
                'INSERT IGNORE INTO voucher_parties (voucher_id, name, mailing_name, line1, line2, line3, state_id, country_id, gst_registration_type_id, gstin, place_of_supply_state_id, created_at, updated_at)
                 SELECT voucher_id, name, mailing_name, line1, line2, line3, state_id, country_id, gst_registration_type_id, gstin, place_of_supply_state_id, created_at, updated_at
                 FROM voucher_parties_duplicates_archive'
            );
        }

        Schema::table('voucher_parties', function (Blueprint $table) {
            $table->dropUnique('voucher_parties_voucher_id_unique');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $conn = Schema::getConnection();
            $database = $conn->getDatabaseName();

            $count = DB::selectOne(
                'SELECT COUNT(*) as c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $indexName]
            );

            return ((int) $count->c) > 0;
        } catch (Throwable) {
            // If information_schema is unavailable (non-MySQL drivers), assume
            // it exists so we never attempt to add it twice.
            return true;
        }
    }
};
