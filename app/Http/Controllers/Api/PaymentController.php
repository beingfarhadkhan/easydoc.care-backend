<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function basedOnClinic(request $request)
    {
        $payments = Payment::where('clinic_id', $request->clinic_id)->get();
        return response()->json($payments);
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


}
