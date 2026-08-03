<?php

use Modules\Payment\Facades\PaymentFacade;
use Modules\Payment\Models\Payment;

// ---------------------------------------------------------------------------
// PaymentFacade → PaymentServiceInterface → PaymentService delegation tests
// ---------------------------------------------------------------------------

describe('PaymentFacade CRUD', function () {
    it('returns all payments via facade', function () {
        Payment::create(['name' => 'Cash', 'code' => 'CASH']);
        Payment::create(['name' => 'Bank Transfer', 'code' => 'BANK']);

        $result = PaymentFacade::getAll();

        expect($result)->toHaveCount(2);
    });

    it('returns a single payment by id via facade', function () {
        $payment = Payment::create(['name' => 'Credit Card', 'code' => 'CC']);

        $found = PaymentFacade::getById($payment->id);

        expect($found)
            ->toBeInstanceOf(Payment::class)
            ->and($found->id)->toBe($payment->id)
            ->and($found->name)->toBe('Credit Card');
    });

    it('stores a new payment via facade', function () {
        $payment = PaymentFacade::store([
            'name' => 'Cheque',
            'code' => 'CHQ',
        ]);

        expect($payment)->toBeInstanceOf(Payment::class);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'name' => 'Cheque',
            'code' => 'CHQ',
        ]);
    });

    it('updates an existing payment via facade', function () {
        $payment = Payment::create(['name' => 'Old Name', 'code' => 'OLD']);

        $updated = PaymentFacade::update(
            ['name' => 'Updated Name', 'code' => 'NEW'],
            $payment->id,
        );

        expect($updated)
            ->toBeInstanceOf(Payment::class)
            ->and($updated->name)->toBe('Updated Name')
            ->and($updated->code)->toBe('NEW');
    });

    it('deletes a payment via facade', function () {
        $payment = Payment::create(['name' => 'Delete Me', 'code' => 'DEL']);

        $result = PaymentFacade::delete($payment->id);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    });
});

// Note: getPaymentsByFreightId() was fixed to query VoucherReference correctly
// (ref_voucher_id instead of reference_id) and return Voucher records (payment
// vouchers linked to the freight) instead of the broken Payment::where('freight_id', ...) query.
// The service now follows the same pattern as ReceiptVoucherService::getFreightReceiptByFreightId().


describe('PaymentService edge cases', function () {
    it('throws ModelNotFoundException when getting non-existent payment', function () {
        expect(fn () => PaymentFacade::getById(99999))
            ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('throws ModelNotFoundException when deleting non-existent payment', function () {
        expect(fn () => PaymentFacade::delete(99999))
            ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    it('returns empty collection when no payments exist', function () {
        $result = PaymentFacade::getAll();
        expect($result)->toHaveCount(0);
    });

    it('validates unique name constraint on store', function () {
        Payment::create(['name' => 'Duplicate', 'code' => 'DUP']);

        expect(fn () => Payment::create(['name' => 'Duplicate', 'code' => 'DUP2']))
            ->toThrow(Illuminate\Database\QueryException::class);
    });
});
