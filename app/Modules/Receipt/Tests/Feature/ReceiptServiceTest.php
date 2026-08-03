<?php

use Modules\Receipt\Facades\ReceiptFacade;
use Modules\Receipt\Models\Receipt;

// ---------------------------------------------------------------------------
// ReceiptFacade → ReceiptServiceInterface → ReceiptService delegation tests
// ---------------------------------------------------------------------------

describe('ReceiptFacade CRUD', function () {
    it('returns all receipts via facade', function () {
        Receipt::create(['name' => 'Cash Receipt', 'code' => 'CR']);
        Receipt::create(['name' => 'Bank Receipt', 'code' => 'BR']);

        $result = ReceiptFacade::getAll();

        expect($result)->toHaveCount(2);
    });

    it('returns a single receipt by id via facade', function () {
        $receipt = Receipt::create(['name' => 'Online Transfer', 'code' => 'ON']);

        $found = ReceiptFacade::getById($receipt->id);

        expect($found)
            ->toBeInstanceOf(Receipt::class)
            ->and($found->id)->toBe($receipt->id)
            ->and($found->name)->toBe('Online Transfer');
    });

    it('stores a new receipt via facade', function () {
        $receipt = ReceiptFacade::store([
            'name' => 'Cheque Receipt',
            'code' => 'CHQR',
        ]);

        expect($receipt)->toBeInstanceOf(Receipt::class);
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'name' => 'Cheque Receipt',
            'code' => 'CHQR',
        ]);
    });

    it('updates an existing receipt via facade', function () {
        $receipt = Receipt::create(['name' => 'Old Receipt', 'code' => 'OLD']);

        $updated = ReceiptFacade::update(
            ['name' => 'Updated Receipt', 'code' => 'NEW'],
            $receipt->id,
        );

        expect($updated)
            ->toBeInstanceOf(Receipt::class)
            ->and($updated->name)->toBe('Updated Receipt')
            ->and($updated->code)->toBe('NEW');
    });

    it('deletes a receipt via facade', function () {
        $receipt = Receipt::create(['name' => 'Delete Me', 'code' => 'DEL']);

        $result = ReceiptFacade::delete($receipt->id);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('receipts', ['id' => $receipt->id]);
    });
});

describe('ReceiptService edge cases', function () {
    it('throws ModelNotFoundException when getting non-existent receipt', function () {
        expect(fn () => ReceiptFacade::getById(99999))
            ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('throws ModelNotFoundException when deleting non-existent receipt', function () {
        expect(fn () => ReceiptFacade::delete(99999))
            ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('returns empty collection when no receipts exist', function () {
        $result = ReceiptFacade::getAll();
        expect($result)->toHaveCount(0);
    });

    it('validates unique name constraint on store', function () {
        Receipt::create(['name' => 'Duplicate', 'code' => 'DUP']);

        expect(fn () => Receipt::create(['name' => 'Duplicate', 'code' => 'DUP2']))
            ->toThrow(Illuminate\Database\QueryException::class);
    });
});
