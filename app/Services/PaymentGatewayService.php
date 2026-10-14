<?php

namespace App\Services;

use App\Models\FeeVoucher;
use App\Models\OnlineTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    /**
     * Initialize a payment request for JazzCash or EasyPaisa.
     * In a production environment, this would call the respective gateway APIs.
     * We will implement robust stubs that mimic the async behavior.
     */
    public function initiatePayment(FeeVoucher $voucher, string $gateway, string $mobileNumber)
    {
        // 1. Create a pending transaction record
        $transaction = OnlineTransaction::create([
            'fee_voucher_id' => $voucher->id,
            'gateway' => $gateway,
            'transaction_reference' => 'TXN-' . strtoupper(Str::random(10)),
            'amount' => $voucher->payable_amount,
            'mobile_number' => $mobileNumber,
            'status' => 'pending',
        ]);

        // 2. Simulate API Call
        // In reality: POST to https://sandbox.jazzcash.com.pk/api/pay
        $success = $this->simulateGatewaySuccess($gateway);

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'reference' => $transaction->transaction_reference,
                'message' => 'Payment initiated. Please check your mobile for the MPIN prompt.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Gateway connection failed. Please try again.'
        ];
    }

    /**
     * Webhook/Callback handler to finalize payment.
     */
    public function handleCallback(array $payload)
    {
        $ref = $payload['pp_ResponseCode'] ?? null;
        $txnRef = $payload['pp_TxnRefNo'] ?? null;

        $transaction = OnlineTransaction::where('transaction_reference', $txnRef)->first();

        if ($transaction && $payload['pp_ResponseCode'] == '000') {
            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => $payload
            ]);

            // Mark Fee Voucher as Paid
            $voucher = $transaction->feeVoucher;
            $voucher->update([
                'status' => 'paid',
                'paid_amount' => $transaction->amount,
                'payment_date' => now(),
                'bank_name' => ucfirst($transaction->gateway) . ' Online'
            ]);

            return true;
        }

        return false;
    }

    protected function simulateGatewaySuccess($gateway)
    {
        // Always succeed for demo purposes
        return true;
    }
}
