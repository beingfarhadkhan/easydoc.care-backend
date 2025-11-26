<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RazorPaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        $order = $api->order->create([
            'receipt' => 'HMS_' . time(),
            'amount' => $request->amount * 100,
            'currency' => 'INR'
        ]);

        // Store order
        $payment = Payment::create([
            'patient_id' => $request->patient_id,
            'razorpay_order_id' => $order->id,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'key' => config('services.razorpay.key'),
            'amount' => $request->amount * 100,
            'patient_id' => $request->patient_id
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $signatureStatus = $this->verifySignature(
            $request->razorpay_signature,
            $request->razorpay_payment_id,
            $request->razorpay_order_id
        );

        if ($signatureStatus) {
            Payment::where('razorpay_order_id', $request->razorpay_order_id)->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'paid'
            ]);

            return redirect('/payment-success');
        }

        return redirect('/payment-failed');
    }

    protected function verifySignature($signature, $paymentId, $orderId)
    {
        $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
