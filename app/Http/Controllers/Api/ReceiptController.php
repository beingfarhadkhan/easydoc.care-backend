<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receipt;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\AssignToClinic;
use App\Models\AssignToAccount;
use App\Models\User;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Inventory;
use App\Models\Consumable;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;


class ReceiptController extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {              
        $existing = Receipt::where('appointment_id', $request->appointment_id)->first();

        $clinic = Clinic::find($request->clinic_id);
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 201);
        }

        $advance = 0;

        if (!empty($request->adv_payment_mode)) {
            foreach ($request->adv_payment_mode as $payment) {

                $amount = ($payment['amount'] ?? 0);
                $discountPercent = ($payment['discount_percent'] ?? 0);

                if ($discountPercent > 0) {
                    $discount = ($amount * $discountPercent) / 100;
                    $amount = max($amount - $discount, 0);
                }

                $advance += $amount;
            }
        }
        
        if ($existing) {            
            $existing->update([
                'status' => $request->status,
                'particulars' => json_encode($request->particulars),
                'payment_mode' => json_encode($request->payment_mode),
                'adv_payment_mode' => json_encode($request->adv_payment_mode),
                'advance_amount' => $advance,
                'remarks' => $request->remarks,
                'additional_discount' => $request->additional_discount,
                'updated_at' => now(),
            ]);

            

            $pdf_url = $this->generatePdf($existing->id);
            
            $existing->update([
                'pdf_url' => $pdf_url,
                'updated_at' => now(),
            ]);

            foreach ($request->payment_mode as $payment) {
                $existingPayment = Payment::where('receipt_id', $existing->id)
                    ->where('payment_mode', $payment['type'])
                    ->where('amount', $payment['amount'])
                    ->first();
                
                if (!$existingPayment) {
                    Payment::create([
                        'receipt_id'   => $existing->id,
                        'clinic_id'    => $request->clinic_id,
                        'account_id'  => $clinic->account_id,
                        'payment_mode'   => $payment['type'],
                        'amount'       => $payment['amount'],
                        'transaction_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            if (!empty($request->adv_payment_mode)) {
                foreach ($request->adv_payment_mode as $payment) {
                    Payment::create([
                        'receipt_id' => $existing->id,
                        'clinic_id' => $request->clinic_id,
                        'account_id' => $clinic->account_id,
                        'payment_mode' => $payment['type'],
                        'amount' => $payment['amount'],
                        'transaction_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
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

                // if ($inventories->isEmpty()) {
                //     return response()->json([
                //         'status' => false,
                //         'message' => "Stock not available for {$serviceName}"
                //     ], 422);
                // }

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

             $appointment = Appointment::with('patient')
            ->find($existing->appointment_id);

            $particulars = $request->particulars ?? [];

            foreach ($particulars as $particular) {

                $procedure = $particular['service_name'] ?? null;

                if (!$procedure) {
                    continue;
                }

                // Check if consumable already exists for this appointment + procedure
                $exists = Consumable::where('clinic_id', $request->clinic_id)
                    ->where('date_of_appointment', $appointment->appointment_date)
                    ->where('patient_name', $appointment->patient->name)
                    ->where('procedure', $procedure)
                    ->exists();

                // If already exists → skip
                if ($exists) {
                    continue;
                }

                // Otherwise create new consumable
                Consumable::create([
                    'clinic_id'           => $request->clinic_id,
                    'patient_name'        => $appointment->patient->name,
                    'date_of_appointment' => $appointment->appointment_date,
                    'procedure'           => $procedure,
                    'product_used'        => null, // will be updated later
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $existing = Receipt::where('id',$existing->id)->first();

            return response()->json([
            'message' => 'Receipt updated and PDF regenerated successfully',
            'receipt' => $existing->fresh(),
            ], 200);
        }

        $clinic = Clinic::find($request->clinic_id);
        
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 201);
        } 

        $advance = 0;

        if (!empty($request->adv_payment_mode)) {
            foreach ($request->adv_payment_mode as $payment) {

                $amount = ($payment['amount'] ?? 0);
                $discountPercent = ($payment['discount_percent'] ?? 0);

                if ($discountPercent > 0) {
                    $discount = ($amount * $discountPercent) / 100;
                    $amount = max($amount - $discount, 0);
                }

                $advance += $amount;
            }
        }



        // dd($request->clinic_id);
        $receiptNo = $this->generateReceiptNo($request->clinic_id, $clinic->account_id);
    
        $receipt = Receipt::create([
            'receipt_no' => $receiptNo,
            'clinic_id' => $request->clinic_id,
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'status' => $request->status,
            'particulars' => json_encode($request->particulars),
            'payment_mode' => json_encode($request->payment_mode),
            'adv_payment_mode' => json_encode($request->adv_payment_mode),
            'advance_amount' => $advance, 
            'pdf_url' => null,
            'additional_discount' => $request->additional_discount,
            'remarks' => $request->remarks,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        $pdf_url = $this->generatePdf($receipt->id);

        Receipt::where('id',$receipt->id)->update([
            'pdf_url' => $pdf_url,
            'updated_at' => now()
        ]);

        

        foreach ($request->payment_mode as $payment) {
            Payment::create([
                'receipt_id'   => $receipt->id,
                'clinic_id'    => $request->clinic_id,
                'account_id'  => $clinic->account_id,
                'payment_mode'   => $payment['type'],
                'amount'       => $payment['amount'],
                'transaction_date' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        if (!empty($request->adv_payment_mode)) {
            foreach ($request->adv_payment_mode as $payment) {
                Payment::create([
                    'receipt_id' => $receipt->id,
                    'clinic_id' => $request->clinic_id,
                    'account_id' => $clinic->account_id,
                    'payment_mode' => $payment['type'],
                    'amount' => $payment['amount'],
                    'transaction_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
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

        $appointment = Appointment::with('patient')
            ->find($receipt->appointment_id);

        $particulars = $request->particulars ?? [];

        foreach ($particulars as $particular) {

            Consumable::create([
                'clinic_id'        => $request->clinic_id,
                // 'appointment_id'   => $receipt->appointment_id,
                // 'receipt_id'       => $receipt->id,
                // 'patient_id'       => $receipt->patient_id,
                'patient_name'     => $appointment->patient->name ,
                'date_of_appointment' => $appointment->appointment_date ,
                // 'time_slot'        => $appointment->time_slot ?? null,
                'procedure'     => $particular['service_name'] ,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $receipt = Receipt::where('id',$receipt->id)->first();

        return response()->json([
            'message' => 'Receipt created successfully',
            'receipt' => $receipt
            ], 201);
    }
    public function show($id)
    {
        $receipt = Receipt::find($id);
        if(!$receipt){
            return response()->json(['message' => 'Receipt not found'], 404); 
        }
        return response()->json(
            [
                'receipt_id' => $id,
                'clinic-id'=> $receipt->clinic_id,
                'patient_id' => $receipt->patient_id,
                'appointment_id' => $receipt->appointment_id,
                'status' => $receipt->status,
                'particulars' => json_decode($receipt->particulars, true),
                'payment_mode' => json_decode($receipt->payment_mode, true),
                'additional_discount' => $receipt->additional_discount,
                'remarks' => $receipt->remarks,
                'pdf_url' => $receipt->pdf_url,
                'created_at' => $receipt->created_at,
                'updated_at' => $receipt->updated_at
            ]
        );
    }

    public function getReceiptByAppointment(Request $request)
    {
        $receipts = Receipt::where('appointment_id', $request->appointment_id)->first();
        if(!$receipts){
            $appointment = Appointment::find($request->appointment_id);
            if (!$appointment) {
                return response()->json(['message' => 'Appointment not found'], 404);
            }

            $services = json_decode($appointment->services, true);
            return response()->json([
                'message' => 'No receipts found for this appointment',
                'services' => $services
            ], 404); 
        } 

        $patient = Patient::find($receipts->patient_id);
        
        return response()->json([
            'clinic_id'=> $receipts->clinic_id,
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_uhid' => $patient->uhid,
            'patient_contact' => $patient->phone,
            'appointment_id' => $receipts->appointment_id,
            'status' => $receipts->status,
            'particulars' => json_decode($receipts->particulars, true),
            'payment_mode' => json_decode($receipts->payment_mode, true),
            'adv_payment_mode' => json_decode($receipts->adv_payment_mode, true),
            'advance_amount' => $receipts->advance_amount,
            'additional_discount' => $receipts->additional_discount,            
            'remarks' => $receipts->remarks,
            'pdf_url' => $receipts->pdf_url,
            'created_at' => $receipts->created_at,
            'updated_at' => $receipts->updated_at
        ], 200);
    }

   public function getReceiptByPatientId(Request $request)
    {
        $receipts = Receipt::where('patient_id', $request->patient_id)
            ->orderBy('created_at', 'DESC') 
            ->get();

        if ($receipts->isEmpty()) {
            return response()->json([
                'message' => 'No receipts found for this patient'
            ], 404);
        }

    
        $formattedReceipts = $receipts->map(function ($receipt) {
            return [
                'id' => $receipt->id,
                'receipt_no' => $receipt->receipt_no,
                'clinic_id' => $receipt->clinic_id,
                'patient_id' => $receipt->patient_id,
                'appointment_id' => $receipt->appointment_id,
                'status' => $receipt->status,
                'particulars' => json_decode($receipt->particulars, true),
                'payment_mode' => json_decode($receipt->payment_mode, true),
                'adv_payment_mode' => json_decode($receipt->adv_payment_mode, true),
                'advance_amount' => $receipt->advance_amount,
                'additional_discount' => $receipt->additional_discount,
                'remarks' => $receipt->remarks,
                'pdf_url' => $receipt->pdf_url,
                'created_at' => $receipt->created_at,
                'updated_at' => $receipt->updated_at
            ];
        });

        return response()->json([
            'patient_id' => $request->patient_id,
            'total_receipts' => $formattedReceipts->count(),
            'receipts' => $formattedReceipts
        ],200);
    }


    public function update(Request $request)
    {
        $receipt = Receipt::find($request->receipt_id);
        if(!$receipt){
            return response()->json(['message' => 'Receipt not found'], 404);
        }

        Receipt::where('id', $request->receipt_id)->update([
            'status' => $request->status,
            'particulars' => $request->particulars,
            'payment_mode' => $request->payment_mode,
            'remarks' => $request->remarks,
            'updated_at' => now()
        ]);
        return response()->json([
            'message' => 'Receipt updated successfully'            
        ], 200);
    }
        
    public function destroy($id)
    {
        
    }

    // public function generatePdf($id)
    // {
    //     $receipt = Receipt::find($id);
    //     // var_dump($id);
    //     if (!$receipt) {
    //         return response()->json(['message' => 'Receipt not found'], 404);
    //     }

    //     $clinic = Clinic::find($receipt->clinic_id);
    //     $patient = Patient::find($receipt->patient_id);
    //     $appointment = Appointment::find($receipt->appointment_id);
    //     $particulars = json_decode($receipt->particulars, true) ?: [];
    //     $payment_mode = json_decode($receipt->payment_mode, true) ?: [];

    //     // Simple HTML layout for the PDF (replace with a dedicated view if available)
    //     $html = '<!doctype html><html><head><meta charset="utf-8"><style>
    //         body{font-family: DejaVu Sans, Arial, sans-serif; font-size:14px;}
    //         .header{margin-bottom:20px;}
    //         .section{margin-bottom:12px;}
    //         table{width:100%;border-collapse:collapse;}
    //         th,td{padding:6px;border:1px solid #ddd;text-align:left;}
    //         </style></head><body>';
    //     $html .= '<div class="header"><h2>Receipt #' . $receipt->id . '</h2></div>';
    //     $html .= '<div class="section"><strong>Clinic:</strong> ' . ($clinic ? e($clinic->name) : '-') . '</div>';
    //     $html .= '<div class="section"><strong>Patient:</strong> ' . ($patient ? e($patient->name ?? $patient->full_name ?? '-') : '-') . '</div>';
    //     $html .= '<div class="section"><strong>Appointment:</strong> ' . ($appointment ? e($appointment->id) : '-') . '</div>';

    //     $html .= '<div class="section"><strong>Particulars</strong>';
    //     if (count($particulars) > 0) {
    //         $html .= '<table><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody>';
    //         foreach ($particulars as $p) {
    //             $desc = is_array($p) ? ($p['description'] ?? '') : (string)$p;
    //             $amt = is_array($p) ? ($p['amount'] ?? '') : '';
    //             $html .= '<tr><td>' . e($desc) . '</td><td>' . e($amt) . '</td></tr>';
    //         }
    //         $html .= '</tbody></table>';
    //     } else {
    //         $html .= '<div>-</div>';
    //     }
    //     $html .= '</div>';

    //     $html .= '<div class="section"><strong>Payment Mode</strong>';
    //     if (is_array($payment_mode) && count($payment_mode) > 0) {
    //         $html .= '<ul>';
    //         foreach ($payment_mode as $m) {
    //             $html .= '<li>' . e(is_array($m) ? json_encode($m) : $m) . '</li>';
    //         }
    //         $html .= '</ul>';
    //     } else {
    //         $html .= '<div>' . e($receipt->payment_mode ?? '-') . '</div>';
    //     }
    //     $html .= '</div>';

    //     $html .= '<div class="section"><strong>Status:</strong> ' . e($receipt->status ?? '-') . '</div>';
    //     $html .= '<div class="section"><strong>Remarks:</strong> ' . e($receipt->remarks ?? '-') . '</div>';
    //     $html .= '<div class="section" style="margin-top:20px;font-size:12px;color:#666;">Generated: ' . now()->toDateTimeString() . '</div>';
    //     $html .= '</body></html>';

    //     // Generate PDF (requires a PDF provider like barryvdh/laravel-dompdf configured)
    //     $pdf = \PDF::loadHTML($html)->setPaper('a4', 'portrait');

    //     $filename = 'receipt_' . $receipt->id . '_' . time() . '.pdf';
    //     $path = 'receipts/' . $filename;

    //     // Save to the public disk so it is accessible via storage URL
    //     \Storage::disk('public')->put($path, $pdf->output());

    //     $url = asset('storage/' . $path);

    //     // Persist pdf_url on the receipt
    //     $receipt->pdf_url = $url;
    //     $receipt->save();

    //     return response()->json([
    //         'message' => 'PDF generated successfully',
    //         'pdf_url' => $url,
    //         'receipt' => $receipt
    //     ], 200);
    // }

    // private function generatePdf($id)
    // {
    //     $receipt = Receipt::findOrFail($id);
        
    //     $clinic = Clinic::find($receipt->clinic_id);
    //     $patient = Patient::find($receipt->patient_id);
    //     $appointment = Appointment::find($receipt->appointment_id);
    //     $particulars = json_decode($receipt->particulars, true) ?: [];
    //     $payment_mode = json_decode($receipt->payment_mode, true) ?: [];
    //     $accountId = $clinic->account_id;
    //     $accountName = Account::find($accountId);
    //     // dd($accountName);
    //     // Define path in /public/uploads/receipts/
    //     $uploadDir = public_path('receipts');
    //     if (!file_exists($uploadDir)) {
    //         mkdir($uploadDir, 0777, true);
    //     }

    //     // Filename based on receipt number
    //     $filename = 'receipt_' . $receipt->receipt_no . '.pdf';
    //     $fullPath = $uploadDir . '/' . $filename;

    //     // If file doesn't exist, generate PDF
    //     if (!file_exists($fullPath)) {
    //         Pdf::view('pdf.receipt', compact(
    //             'receipt', 'clinic', 'patient', 'appointment', 'particulars', 'payment_mode', 'accountName'
    //         ))
    //             ->format('a4')
    //             ->orientation('portrait')
    //             ->save($fullPath);
    //     }

    //     if (file_exists($fullPath)) {
    //         unlink($fullPath);

    //         Pdf::view('pdf.receipt', compact(
    //             'receipt', 'clinic', 'patient', 'appointment', 'particulars', 'payment_mode', 'accountName'
    //         ))
    //             ->format('a4')
    //             ->orientation('portrait')
    //             ->save($fullPath);
    //     }

    //     // Create public URL (for frontend access)
    //     $url = asset('receipts/' . $filename);

    //     // Optional: store the URL on receipt model
    //     $receipt->update(['pdf_url' => $url]);

    //     return $url;

    //     // return response()->file($fullPath, [
    //     //     'Content-Type' => 'application/pdf',
    //     //     'Content-Disposition' => 'inline; filename="' . $filename . '"'
    //     // ]);
    // } old version

 

    private function generatePdf($id)
    {
        $receipt = Receipt::findOrFail($id);

        $clinic = Clinic::find($receipt->clinic_id);
        $patient = Patient::find($receipt->patient_id);
        $appointment = Appointment::find($receipt->appointment_id);
        $particulars = json_decode($receipt->particulars, true) ?: [];
        $payment_mode = json_decode($receipt->payment_mode, true) ?: [];
        $adv_payment_mode = json_decode($receipt->adv_payment_mode, true) ?: [];
        $advance_amount = $receipt->advance_amount;
        $additional_discount = $receipt->additional_discount ?? 0;
        $accountId = $clinic->account_id;
        $accountName = Account::find($accountId);
        $assignToAccount = AssignToAccount::where('account_id', $accountId)->first();
        $user = $assignToAccount ? User::find($assignToAccount->user_id) : null;
        $accountPhone = $user ? $user->phone : null;
        $doctor = User::find(Appointment::find($receipt->appointment_id)->doctor_id);
        $docSign = $doctor ? $doctor->signature_image : null;
        $doctorName = $doctor ? $doctor->name : null;
        
        // Default fallback
        $template = $clinic->receipt_template ?? 'modern';

        // Blade path
        $viewPath = 'pdf.receipts.' . $template;

        // Safety fallback
        if (!view()->exists($viewPath)) {
            $viewPath = 'pdf.receipts.default';
        }

        // Path to store PDFs
        $uploadDir = public_path('receipts');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // PDF filename
        $filename = 'receipt_' . $receipt->receipt_no . '.pdf';
        $fullPath = $uploadDir . '/' . $filename;

        // If file already exists, remove it before regenerating
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Generate PDF with DOMPDF
        $pdf = PDF::loadView($viewPath, compact(
            'receipt', 'clinic', 'patient', 'appointment', 'particulars', 'payment_mode', 'accountName', 'additional_discount','accountPhone','doctorName','docSign','adv_payment_mode','advance_amount'
        ))->setPaper('a4', 'portrait');

        // Save the generated file
        $pdf->save($fullPath);

        // Public URL
        $url = asset('receipts/' . $filename);

        // Save path in DB if needed
        $receipt->update(['pdf_url' => $url]);

        return $url;
    }



    // private function generateReceiptNo($clinicId, $accountId)
    // {
    //     $year = date('Y');

    //     // Find last receipt for this clinic in this year
    //     $lastReceipt = Receipt::where('clinic_id', $clinicId)
    //         ->whereYear('created_at', $year)
    //         ->orderBy('id', 'desc')
    //         ->first();

    //     // Determine next sequence number
    //     $nextNumber = 1;
    //     if ($lastReceipt && preg_match('/(\d{5})$/', $lastReceipt->receipt_no, $matches)) {
    //         $nextNumber = intval($matches[1]) + 1;
    //     }

    //     // Format sequence as 5-digit padded number
    //     $sequence = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

    //     // Final format: EasyDoc-FY2025-23-00001  select it from 1april 
    //     return "EasyDoc-FY{$year}-{$accountId}-{$sequence}";
    // }

    private function generateReceiptNo($clinicId, $accountId)
    {
        $now = now();

        // Determine Financial Year start year
        // FY starts from 1st April
        if ($now->month >= 4) {
            $fyYear = $now->year;       // FY2025
            $fyStartDate = "{$fyYear}-04-01";
            $fyEndDate   = ($fyYear + 1) . "-03-31";
        } else {
            $fyYear = $now->year - 1;   // FY2024
            $fyStartDate = "{$fyYear}-04-01";
            $fyEndDate   = $now->year . "-03-31";
        }

        // Find last receipt in the same financial year
        $lastReceipt = Receipt::where('clinic_id', $clinicId)
            ->whereBetween('created_at', [$fyStartDate, $fyEndDate])
            ->orderBy('id', 'desc')
            ->first();

        // Sequence reset every FY
        $nextNumber = 1;
        if ($lastReceipt && preg_match('/(\d{5})$/', $lastReceipt->receipt_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        // Pad sequence
        $sequence = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // EXACT format you want
        return "EasyDoc-FY{$fyYear}-{$accountId}-{$sequence}";
    }


    public function getPaymentByDoctor(Request $request)
    {
        $doctor = auth()->user();
        if ($doctor ->role != 2 && $doctor ->role != 1) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

         // Fetch all appointments linked to the doctor
        $appointmentIds = Appointment::where('doctor_id', $doctor->id)
            ->pluck('id');

        if ($appointmentIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'No appointments found for this doctor'
            ]);
        }

        // Fetch receipts for those appointments
        $receipts  = Receipt::whereIn('appointment_id', $appointmentIds)
            ->with(['appointment:id,appointment_date,time_slot',
            'patient:id,name,phone'
            ])
            ->orderBy('id', 'desc')
            ->get();

            $payments = $receipts->map(function ($receipts) {
                $modeWiseAmount = [];
                // Normal payment
                $paymentModes = json_decode($receipts->payment_mode, true) ?: [];
                // $paidAmount = array_sum(array_column($paymentModes, 'amount'));
                 $paidAmount = 0;

                foreach ($paymentModes as $payment) {
                    $type = $payment['type'] ?? 'unknown';
                    $amount = (float) ($payment['amount'] ?? 0);

                    $paidAmount += $amount;

                    if (!isset($modeWiseAmount[$type])) {
                        $modeWiseAmount[$type] = 0;
                    }
                    $modeWiseAmount[$type] += $amount;
                }

                 // Advance payment
                $advanceModes = json_decode($receipts->adv_payment_mode, true) ?: [];
                // $advanceAmount = 0;

                // foreach ($advanceModes as $adv) {
                //     $amount = $adv['amount'] ?? 0;

                //     $advanceAmount += max($amount, 0);
                // }
                $advanceAmount = 0;

                foreach ($advanceModes as $adv) {
                    $type = $adv['type'] ?? 'unknown';
                    $amount = (float) ($adv['amount'] ?? 0);

                    $advanceAmount += $amount;

                    if (!isset($modeWiseAmount[$type])) {
                        $modeWiseAmount[$type] = 0;
                    }
                    $modeWiseAmount[$type] += $amount;
                }
                
                return [
                    'id' => $receipts->id,
                    'receipt_no' => $receipts->receipt_no,
                    'appointment_id' => $receipts->appointment_id,
                    'patient' => $receipts->patient,
                    'patient_name' => $receipts->name,
                    'status' => $receipts->status,
                    'paid_amount' => $paidAmount,
                    'advance_amount' => $advanceAmount,
                    'total_amount' => $paidAmount + $advanceAmount,
                    'payment_mode_wise_amount' => $modeWiseAmount,
                    'pdf_url' => $receipts->pdf_url,
                    'additional_discount' => $receipts->additional_discount,
                    'created_at' => $receipts->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'count' => $payments->count(),
            'data' => $payments
         ],200);
        
    }

}
