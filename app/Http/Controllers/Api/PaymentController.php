<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Order;
use App\Models\Clinic;

class PaymentController extends Controller
{
    // public function basedOnClinic(request $request)
    // {
    //     $payments = Payment::where('clinic_id', $request->clinic_id)->get();
    //     return response()->json($payments);
    // }
    public function basedOnClinic(Request $request)
    {
        // $payments = Payment::where('clinic_id', $request->clinic_id)->get();

        $payments = Payment::with('receipt.appointment.patient')
        ->where('clinic_id', $request->clinic_id)
        ->get();

        // Mode-wise sum
        $modeWiseTotal = [];

        foreach ($payments as $payment) {
            $mode = $payment->payment_mode ?? 'unknown';
            $amount = (float) ($payment->amount ?? 0);

            if (!isset($modeWiseTotal[$mode])) {
                $modeWiseTotal[$mode] = 0;
            }

            $modeWiseTotal[$mode] += $amount;
        }

        return response()->json([
            'total_payments_count' => $payments->count(),

            'payment_mode_wise_amount' => $modeWiseTotal,

            // optional: raw payment data
            'payments' => $payments
        ], 200);
    }



    public function basedOnAccount(request $request)
    {
        $payments = Payment::where('account_id', $request->account_id)->get();
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $payment = Payment::create([
            'receipt_id' => $request->receipt_id,
            'clinic_id' => $request->clinic_id,
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'payment_mode' => $request->type,
            'payment_date' => $request->payment_date,
            // 'transaction_id' => $request->transaction_id,
            'remarks' => $request->remarks ,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Payment created successfully',
            'payment' => $payment
            ], 201);
    }

    public function show($id)
    {
        $payment = Payment::find($id);
        if(!$payment){
            return response()->json(['message' => 'Payment not found'], 404);
        }
        return response()->json(['payment' => $payment]);
    }

    public function update(Request $request)
    {
        $payment = Payment::find($request->receipt_id);
        if(!$payment){
            return response()->json(['message' => 'Payment not found'], 404);
        }
       
        Payment::where('id',$request->receipt_id)->update([
        'amount' => $request->amount,
        'payment_mode' => $request->type,
        'payment_date' => $request->payment_date,
        'transaction_id' => $request->transaction_id,
        'remarks' => $request->remarks ,
        'updated_at' => now()
        ]);

        return response()->json(['message' => 'Payment updated successfully'], 201);
    }   

    public function destroy(string $id)
    {
        //
    }

    public function advancePayment(Request $request){

        $clinic = Clinic::find($request->clinic_id);
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 201);
        } 
        
        $receipt = Receipt::where('clinic_id', $request->clinic_id)
            ->whereNotNull('advance_amount')
            ->get();

        $totalReceiptAmount = 0;
        $totalPaidAmount = 0;
        $receipts = [];

        foreach($receipt as $r){
            $subTotal = 0;
            $particulars = $r->particulars;
            if (is_string($particulars)) {
            $particulars = json_decode($particulars, true);
            }
            
            if(!empty($particulars) && is_array($particulars)){
            foreach($particulars as $p){
                if(is_array($p)){
                $q = floatval($p['quantity'] ?? 0);
                $fee = floatval($p['service_fee'] ?? 0);
                $disc = floatval($p['discount_percent'] ?? 0);
                $subTotal += $q * $fee * (1 - ($disc / 100));
                }
            }
            }
            
            $receiptTotal = $subTotal - ($r->additional_discount ?? 0);
            $totalReceiptAmount += $receiptTotal;

            $currentPaid = 0;
            $payment_mode = $r->payment_mode;

            if (is_string($payment_mode)) {
            $payment_mode = json_decode($payment_mode, true);
            }

            if(is_array($payment_mode)){
            foreach($payment_mode as $m){
                $currentPaid += floatval($m['amount'] ?? 0);
            }
            }
            
            $receiptPaidAmount = $currentPaid + $r->advance_amount;
            $totalPaidAmount += $receiptPaidAmount;
            $balanceAmount = $receiptTotal - $receiptPaidAmount;
            
            $receipts[] = array_merge($r->toArray(), [
            'total_amount' => $receiptTotal,
            'totalPaid' => $receiptPaidAmount,
            'balance_amount' => $balanceAmount
            ]);
        }

        $orders = Order::where('clinic_id', $request->clinic_id)
                ->whereNotNull('advance_amount')
                ->get();

        return response()->json([
            'receipts' => $receipts,
            'order' => $orders, 

            // 'grandReceiptTotal' => $totalReceiptAmount,
            // 'grandTotalPaid' => $totalPaidAmount,
            // 'grandBalanceAmount' => $totalReceiptAmount - $totalPaidAmount
        ], 200);
    }


}
