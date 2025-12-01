<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Razorpay\Api\Api;
use App\Models\EasydocBillingPayment;

use Illuminate\Http\Request;

class RazorPayPaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(env('RAZORPAY_TEST_KEY'), env('RAZORPAY_TEST_SECRET'));

        // Create Razorpay order
        $order = $api->order->create([
            'receipt'  => 'RCPT_' . $request->receipt_id,
            'amount'   => $request->amount * 100,  // paise
            'currency' => 'INR',
        ]);

        // Save in DB
        $payment = EasydocBillingPayment::create([
            'receipt_id'        => $request->receipt_id,
            'user_id'           => $request->user_id,
            'amount'            => $request->amount,
            'razorpay_order_id' => $order->id,
            'status'            => 'created',
            'captured'          => 0
        ]);

        return response()->json([
            'order_id'  => $order->id,
            'amount'    => $request->amount * 100,
            'user_id'   => $request->user_id,
            'receipt_id'=> $request->receipt_id
        ]);
    }


    // ----------------------------
    // VERIFY PAYMENT
    // ----------------------------
    public function verifyPayment(Request $request)
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            // Verify the signature
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id
            ]);

            // Update DB
            EasydocBillingPayment::where('razorpay_order_id', $request->razorpay_order_id)
                ->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'status'              => 'paid',
                    'captured'            => 1
                ]);

            return response()->json(['message' => 'Payment Successful'], 200);

        } catch (\Exception $e) {

            EasydocBillingPayment::where('razorpay_order_id', $request->razorpay_order_id)
                ->update([
                    'status'   => 'failed',
                    'captured' => 0
                ]);

            return response()->json(['message' => 'Payment Verification Failed'], 400);
        }
    }

}
