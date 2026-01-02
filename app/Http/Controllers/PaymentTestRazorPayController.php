<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\EasydocBillingPayment;
use Illuminate\Support\Str;
use Auth;

class PaymentTestRazorPayController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(
            env('RAZORPAY_TEST_KEY'),
            env('RAZORPAY_TEST_SECRET')
        );

        $receiptId = 'rcpt_' . Str::random(10);
        $amount = 1999; // Plan price (INR)

        $order = $api->order->create([
            'receipt' => $receiptId,
            'amount' => $amount * 100, // Razorpay uses paise
            'currency' => 'INR'
        ]);

        EasydocBillingPayment::create([
            'invoice_id' => $receiptId,
            'user_id' => Auth::id() ?? 1,
            'account_id' => 5,
            'amount' => $amount,
            'razorpay_order_id' => $order['id'],
            'status' => 'created'
        ]);

        return view('hello', [
            'orderId' => $order['id'],
            'amount' => $amount,
            'razorpayKey' => env('RAZORPAY_TEST_KEY')
        ]);
    }

    public function paymentSuccess(Request $request)
    {
        $api = new Api(
            env('RAZORPAY_TEST_KEY'),
            env('RAZORPAY_TEST_SECRET')
        );

        try {
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            EasydocBillingPayment::where('razorpay_order_id', $request->razorpay_order_id)
                ->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'status' => 'success',
                    'captured' => 'yes'
                ]);

            return redirect()->back()->with('success', 'Payment Successful');

        } catch (\Exception $e) {

            EasydocBillingPayment::where('razorpay_order_id', $request->razorpay_order_id)
                ->update(['status' => 'failed']);

            return redirect()->back()->with('error', 'Payment Failed');
        }
    }
}
