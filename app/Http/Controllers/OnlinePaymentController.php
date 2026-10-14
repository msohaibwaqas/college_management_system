<?php

namespace App\Http\Controllers;

use App\Models\FeeVoucher;
use App\Models\OnlineTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class OnlinePaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentGatewayService $service)
    {
        $this->paymentService = $service;
    }

    /**
     * Show checkout page for a specific voucher.
     * PUBLIC ACCESS: Validated by voucher number.
     */
    public function checkout($voucher_number)
    {
        $voucher = FeeVoucher::where('voucher_number', $voucher_number)
            ->where('status', 'pending')
            ->firstOrFail();

        return view('public.payments.checkout', compact('voucher'));
    }

    /**
     * Process checkout request.
     */
    public function initiate(Request $request, $id)
    {
        $voucher = FeeVoucher::findOrFail($id);
        
        $request->validate([
            'gateway' => 'required|in:jazzcash,easypaisa',
            'mobile_number' => 'required|string|min:11'
        ]);

        $result = $this->paymentService->initiatePayment(
            $voucher, 
            $request->gateway, 
            $request->mobile_number
        );

        if ($result['success']) {
            return redirect()->route('payment.processing', $result['transaction_id']);
        }

        return back()->with('error', $result['message']);
    }

    public function processing($transaction_id)
    {
        $transaction = OnlineTransaction::with('feeVoucher')->findOrFail($transaction_id);
        
        // This view would ideally have a polling JS to check status
        return view('public.payments.processing', compact('transaction'));
    }

    /**
     * Handle Gateway Callbacks (Webhooks)
     */
    public function callback(Request $request)
    {
        $success = $this->paymentService->handleCallback($request->all());

        return response()->json([
            'status' => $success ? 'success' : 'failed'
        ]);
    }
}
