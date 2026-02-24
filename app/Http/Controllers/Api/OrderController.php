<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Clinic;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Account;
use App\Models\AssignToAccount;
use App\Models\User;
use PDF;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('patient:id,name,phone')->where('clinic_id', $request->clinic_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'count' => $orders->count(),
            'data' => $orders
        ]);
    }


    public function store(Request $request)
    {   
        $clinic = Clinic::find($request->clinic_id);
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 201);
        } 
        
        $total = 0;
        $advance = 0;

       if (!empty($request->advance_amount)) {
            foreach ($request->advance_amount as $payment) {

                $amount = ($payment['amount'] ?? 0);
                $discountPercent = ($payment['discount_percent'] ?? 0);

                // Apply percentage discount if present
                if ($discountPercent > 0) {
                    $discount = ($amount * $discountPercent) / 100;
                    $amount = max($amount - $discount, 0);
                }

                $advance += $amount;
            }
        }

        $paidViaPaymentMode = 0;

        if (!empty($request->payment_mode)) {
            foreach ($request->payment_mode as $payment) {
                $paidViaPaymentMode += ($payment['amount'] ?? 0);
            }
        }


        foreach ($request->particulars as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = ($item['price'] ?? 0);
            $discountPercent = ($item['discount_percent'] ?? 0);
            
            // Apply percentage discount if present
            if ($discountPercent > 0) {
                $discount = ($price * $discountPercent) / 100;
                $price = max($price - $discount, 0);
            }
            
            $total += $qty * $price;
        }

        
        $total = max($total - ($request->additional_discount ?? 0), 0);

        $totalPaid = $advance + $paidViaPaymentMode;

        $balance = ($totalPaid >= $total) ? 0 : ($total - $totalPaid);

        // $balance = max($total - $advance, 0);

        $order = Order::create([
            'order_no' => 'ORD-' . time(),
            'clinic_id' => $request->clinic_id,
            'patient_id' => $request->patient_id,
            'address' => json_encode($request->address),
            'status' => $request->status ?? 'pending',
            'particulars' => json_encode($request->particulars),
            'payment_mode' => json_encode($request->payment_mode),
            'adv_payment_mode' => json_encode($request->advance_amount),
            'advance_amount' => $advance,
            'total_amount' => $total,
            'balance_amount' => $balance,
            'pdf_url' => null,
            'additional_discount' => $request->additional_discount,
            'remarks' => $request->remarks,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pdf_url = $this->generatePdf($order->id);

        Order::where('id',$order->id)->update([
            'pdf_url' => $pdf_url,
            'updated_at' => now()
        ]);

        

        foreach ($request->payment_mode as $payment) {
            Payment::create([
                'receipt_id'   => $order->id,
                'clinic_id'    => $request->clinic_id,
                'account_id'  => $clinic->account_id,
                'payment_mode'   => $payment['type'],
                'amount'       => $payment['amount'],
                'transaction_date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);          
        }

        foreach ($request->advance_amount as $payment) {
            Payment::create([
                'receipt_id'   => $order->id,
                'clinic_id'    => $request->clinic_id,
                'account_id'  => $clinic->account_id,
                'payment_mode'   => $payment['type'],
                'amount'       => $payment['amount'],
                'transaction_date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);          
        }

        //Deduct stock for each particular
        foreach ($request->particulars as $item) {

            $serviceName = $item['service_name'] ?? null;
            $qtyNeeded   = (int) ($item['quantity'] ?? 0);

            if (!$serviceName || $qtyNeeded <= 0) {
                continue;
            }

            $inventories = Inventory::where('product_name', 'LIKE', $serviceName)
                ->where('stock', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->get();
                
            $inventory = Inventory::where('product_name', $serviceName)
            ->where('clinic_id', $request->clinic_id)
            ->where('account_id', $clinic->account_id)
            ->first();

            $stockDetails = json_decode($inventory->stock_details, true);
            $deductQty = (int) ($item['quantity'] ?? 0);
            $size        = $item['size'] ?? null;
            // dd($size);
            $new_Total_stock = $inventory->total_stock_available - $deductQty;
            
            if($stockDetails !=null){
                foreach ($stockDetails as &$batch) {
                    if ($batch['size'] == $size && $deductQty > 0) {
                        $available = $batch['stock'];

                        if ($available >= $deductQty) {
                            $batch['stock'] -= $deductQty;
                            $deductQty = 0;
                        } else {
                            $deductQty -= $available;
                            $batch['stock'] = 0;
                        }
                    }
                }
            }

            $inventory->update([
                'stock_details' => json_encode($stockDetails),
                'total_stock_available' => max(0, $new_Total_stock)
            ]);

            foreach ($inventories as $inventory) {

                if ($qtyNeeded <= 0) break;

                if ($inventory->stock >= $qtyNeeded) {
                    $inventory->decrement('stock', $qtyNeeded);
                    $qtyNeeded = 0;
                } else {
                    $qtyNeeded -= $inventory->stock;
                    $inventory->update(['stock' => 0]);
                }
            }

            // if ($qtyNeeded > 0) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => "Insufficient stock for {$serviceName}"
            //     ], 422);
            // }
        }

        $order = Order::find($order->id);

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    public function getOrdersByPatient(Request $request)
    {
        $orders = Order::where('patient_id', $request->patient_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'count' => $orders->count(),
            'data' => $orders
        ]);
    }

    public function update(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $clinic = Clinic::find($order->clinic_id);
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        /* ===============================
        Calculate Total
        =============================== */
        $total = 0;
        foreach ($request->particulars as $item) {
            $qty   = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discountPercent = ($item['discount_percent'] ?? 0);
            
            // Apply percentage discount if present
            if ($discountPercent > 0) {
                $discount = ($price * $discountPercent) / 100;
                $price = max($price - $discount, 0);
            }
            $total += $qty * $price;
        }

        /* ===============================
        Calculate Advance (from JSON)
        =============================== */
        $advance = 0;
          if (!empty($request->advance_amount)) {
            foreach ($request->advance_amount as $payment) {

                $amount = ($payment['amount'] ?? 0);
                $discountPercent = ($payment['discount_percent'] ?? 0);

                // Apply percentage discount if present
                if ($discountPercent > 0) {
                    $discount = ($amount * $discountPercent) / 100;
                    $amount = max($amount - $discount, 0);
                }

                $advance += $amount;
            }
        }

        $paidViaPaymentMode = 0;

        if (!empty($request->payment_mode)) {
            foreach ($request->payment_mode as $payment) {
                $paidViaPaymentMode += ($payment['amount'] ?? 0);
            }
        }


        // Apply additional discount if provided
        $total = max($total - ($request->additional_discount ?? 0), 0);

        $totalPaid = $advance + $paidViaPaymentMode;

        $balance = ($totalPaid >= $total) ? 0 : ($total - $totalPaid);
        

        $order->update([
            'address' => json_encode($request->address),
            'status' => $request->status ?? $order->status,
            'particulars' => json_encode($request->particulars),
            'payment_mode' => json_encode($request->payment_mode),
            'adv_payment_mode' => json_encode($request->advance_amount),
            'advance_amount' => $advance,
            'total_amount' => $total,
            'balance_amount' => $balance,
            'additional_discount' => $request->additional_discount,
            'remarks' => $request->remarks,
            'updated_at' => now()
        ]);

       
        $pdf_url = $this->generatePdf($order->id);
        $order->update(['pdf_url' => $pdf_url]);

        /* ===============================
        Save Payment Mode Payments
        =============================== */
        if (!empty($request->payment_mode)) {
            foreach ($request->payment_mode as $payment) {
                Payment::create([
                    'receipt_id' => $order->id,
                    'clinic_id' => $order->clinic_id,
                    'account_id' => $clinic->account_id,
                    'payment_mode' => $payment['type'],
                    'amount' => $payment['amount'],
                    'transaction_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        /* ===============================
        Save Advance Payments
        =============================== */
        if (!empty($request->advance_amount)) {
            foreach ($request->advance_amount as $payment) {
                Payment::create([
                    'receipt_id' => $order->id,
                    'clinic_id' => $order->clinic_id,
                    'account_id' => $clinic->account_id,
                    'payment_mode' => $payment['type'],
                    'amount' => $payment['amount'],
                    'transaction_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Order updated successfully',
            'data' => $order->fresh()
        ], 200);
    }

    public function show(Request $request)
    {
        $order = Order::with(['patient:id,name,phone,uhid'])->find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }

    public function addAdvance(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $newAdvance = $order->advance_amount + (float)$request->amount;
        $balance = max($order->total_amount - $newAdvance, 0);

        $order->update([
            'advance_amount' => $newAdvance,
            'balance_amount' => $balance
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Advance added successfully',
            'data' => $order
        ]);
    }

    private function generatePdf($id)
    {
        $order = Order::findOrFail($id);

        $clinic = Clinic::find($order->clinic_id);
        // dd($clinic);
        $patient = Patient::find($order->patient_id);
        // dd($order->patient_id);
        $particulars = json_decode($order->particulars, true) ?: [];
        $payment_mode = json_decode($order->payment_mode, true) ?: [];

        $additional_discount = $order->additional_discount ?? 0;
        $advance_amount = $order->advance_amount ?? 0;
        $accountId = $clinic->account_id;

        $accountName = $accountId ? Account::find($accountId) : null;
        // dd($account);
        $assignToAccount = $accountId
            ? AssignToAccount::where('account_id', $accountId)->first()
            : null;

        $user = $assignToAccount ? User::find($assignToAccount->user_id) : null;
        $accountPhone = $user ? $user->phone : null;

        // PDF directory
        $uploadDir = public_path('orders');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // File name
        $filename = 'order_' . $order->order_no . '.pdf';
        $fullPath = $uploadDir . '/' . $filename;

        // Remove old file if exists
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Generate PDF
        $pdf = PDF::loadView('pdf.order', compact(
            'order',
            'clinic',
            'patient',
            'particulars',
            'payment_mode',
            'accountName',
            'accountPhone',
            'additional_discount',
            'advance_amount'
        ))->setPaper('a4', 'portrait');

        // Save PDF
        $pdf->save($fullPath);

        // Public URL
        $url = asset('orders/' . $filename);

        // Update order
        $order->update([
            'pdf_url' => $url
        ]);

        return $url;
    }



}
