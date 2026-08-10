<?php

namespace Modules\StockJournal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\StockJournalEntry\Models\StockJournalEntry;
use Modules\Voucher\Models\Voucher;

class StockJournal extends Model
{
    use HasFactory;

    protected $table = 'stock_journals';

    protected $fillable = [
        'journal_no',
        'journal_date',
        'type',
        'remarks',

    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // The DB column is a DATE; casting normalizes any client date format
        // (e.g. the ISO-8601 '2026-04-01T00:00:00.000Z' that the frontend
        // sends for a JS Date) to 'Y-m-d' before insert — without it MariaDB
        // rejects the raw ISO string (see Voucher::$casts for the same pattern).
        'journal_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('default_order', function (Builder $builder) {
            $builder
                ->orderBy('journal_date', 'desc')
                ->orderBy('journal_no', 'asc');
        });
    }

    public function stock_journal_entries(): HasMany
    {
        return $this->hasMany(StockJournalEntry::class, 'stock_journal_id');
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class, 'stock_journal_id', 'id');
    }
}
