<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AssignToAccount;
use App\Models\User;
use App\Models\AccountBilling;
use App\Models\EasydocBillingPayment;
use App\Models\Plan;

class AccountController extends Controller
{    
    public function show($id){
        $account = Account::find($id);
        if(!$account){
            return response()->json(['message' => 'Account not found'], 204);
        }
        // Return account details as JSON
        return response()->json([
            'account_id' => $id,
            'account' => $account
        ]);
    }

    public function store(Request $request)
    {
        if ($request->gst && $request->gst !== null && Account::where('gst', $request->gst)->exists()) {
            return response()->json([
            'status'  => false,
            'message' => 'GST already exists'
            ], 422);
        }
        
        if ($request->pan && $request->pan !== null && Account::where('pan', $request->pan)->exists()) {
            return response()->json([
            'status'  => false,
            'message' => 'PAN already exists'
            ], 422);
        }


        $account = Account::create([
            'legal_name' => $request->legal_name,
            'display_name' => $request->display_name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
            'gst' => $request->gst,
            'pan' => $request->pan,
            'primary_user' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        AssignToAccount::create([
            'user_id' => auth()->user()->id,
            'account_id' => $account->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $plan = Plan::where('id', '1')->first();

        $billing = AccountBilling::create([
            'account_id' => $account->id,
            'plan_name' => $plan->plan_name,
            'plan_price' => $plan->current_pricing,
            'plan_start_date' => now(),
            'plan_end_date' => now()->addMonth((int)$plan->validity),
            'billing_cycle' => 'Monthly',
            'no_of_docs_in_use' => 1,
            'no_of_docs_allowed' => $plan->doctor_limit,
            'no_of_admins_in_use' => 1,
            'no_of_admins_allowed' => $plan->admin_limit,
            'no_of_staff_in_use' => 0,
            'no_of_staff_allowed' => $plan->staff_limit,
            'no_of_clinics_in_use' => 0,
            'no_of_clinics_allowed' =>$plan->clinic_limit,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $account['billing_details'] = $billing;

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account
            ], 201);
    }



    public function assignToAccount(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 204);
        }

        $account = Account::where('id', $request->account_id)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 204);
        }
        
        $record = AssignToAccount::where('user_id', $request->user_id)
            ->where('account_id', $request->account_id)
            ->first();

        if ($record) {
            return response()->json(['message' => 'User is already assigned to the account'], 204);
        }

        // Update admin usage count in AccountBilling
        $billing = AccountBilling::where('account_id', $request->account_id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$billing) {
            return response()->json([
                'message' => 'Billing configuration not found for this account'
            ], 404);
        }

        if ($billing && (int)$billing->no_of_admins_in_use >= (int)$billing->no_of_admins_allowed) {
            return response()->json([
                'message' => 'Admin limit reached. Cannot add more admins.'
            ], 403);
        }


        AssignToAccount::create([
            'user_id' => $request->user_id,
            'account_id' => $request->account_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);


        if ($billing) {
            $billing->no_of_admins_in_use = (int)$billing->no_of_admins_in_use + 1;
            // $billing->updated_at = now();
            $billing->save();
        }

        return response()->json(['message' => 'User assigned to account successfully'], 201);
        
    }

    public function update(Request $request)
    {
        $account = Account::where('id', $request->user_id)->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 200);
        }
       Account::where('id',$request->user_id )->update([
            'legal_name' => $request->legal_name,
            'display_name' => $request->display_name,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
            'gst' => $request->gst,
            'pan' => $request->pan,
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Account updated successfully'], 200);
    }

    public function removeUserFromAccount(Request $request)
    {
         $account = Account::find($request->account_id);

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }
        
        $record = AssignToAccount::where('user_id', $request->user_id)
            ->where('account_id', $request->account_id)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'User is not assigned to the account'], 204);
        }

        if($record->user_id == $account->primary_user){
            return response()->json(['message' => 'Cannot remove primary user from the account'], 400);
        }else
        
        {
            AssignToAccount::where('user_id', $request->user_id)
            ->where('account_id', $request->account_id)
            ->delete();

            // Update admin usage count in AccountBilling
            $billing = AccountBilling::where('account_id', $request->account_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($billing) {
                $billing->no_of_admins_in_use = (int)$billing->no_of_admins_in_use - 1;
                // $billing->updated_at = now();
                $billing->save();
            }

             return response()->json(['message' => 'User removed from account successfully'], 200);
        }
    }

    public function getAllAccountsByUser(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
 
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $accounts = AssignToAccount::where('user_id', $request->user_id)
            ->with('account')
            ->get()
            ->map(function ($item) {
                return $item->account;
            });
  
        if($accounts->isEmpty()){
            return response()->json(['message' => 'No accounts found for this user'], 404);
        }

        return response()->json(['accounts' => $accounts], 200);
    }

    public function getAllUsersByAccount(Request $request)
    {
        $account = Account::where('id', $request->account_id)->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 204);
        }

        $users = AssignToAccount::where('account_id', $request->account_id)
            ->with('user')
            ->get()
            ->map(function ($item) {
                return $item->user;
            });

        return response()->json(['users' => $users], 200);
    }

    public function getInvoiceByAccount(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payments = EasydocBillingPayment::where('account_id', $request->account_id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'message' => 'No invoices found for this account'
            ], 404);
        }

        $billing = AccountBilling::where('account_id', $request->account_id)
        ->orderBy('created_at', 'desc')
        ->first();

        $dueDate = $billing ? $billing->plan_end_date : null;

        $invoices = $payments->map(function ($payment) use ($dueDate) {
            return [
                'invoice_id'              => $payment->invoice_id,
                'invoice_date'            => $payment->invoice_date,
                'due_date'                => $dueDate,
                'amount'                  => $payment->amount,
                'status'                  => $payment->status,
                'invoice_url'             => $payment->invoice_url,            
                'created_at'              => $payment->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'count'  => $invoices->count(),
            'data'   => $invoices
        ], 200);
    }

    public function getCurrentBillingDetail(Request $request)
    {
        $billing = AccountBilling::where('account_id', $request->account_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$billing) {
            return response()->json(['message' => 'Billing details not found'], 404);
        }

        $currentPlanDetail = $billing->current_plan_detail ? json_decode($billing->current_plan_detail, true) : null;

        return response()->json([
            'current_plan_details' => $currentPlanDetail
        ], 200);
    }

    
    public function updatePatientDetailsConfig(Request $request)
    {
        $account = Account::where('id',$request->account_id)->first();

        if(!$account){
            return response()->json(['message' => 'Account not found'],404);
        }

        Account::where('id',$request->account_id)->update([
            'patient_detail_config' => json_encode($request->patient_detail_config),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Patient details configuration updated successfully'
        ], 200);
    }

    public function getPatientDetailsConfig(Request $request)
    {
        $account = Account::where('id',$request->account_id)->first();

        if(!$account){
            return response()->json(['message' => 'Account not found'],404);
        }

        $patientDetailConfig = json_decode($account->patient_detail_config,true);

        return response()->json([
            'patient_detail_config' => $patientDetailConfig
        ], 200);
        
    }

//    public function updatePaymentDetails(Request $request)
//     {
//         $account = Account::find($request->account_id);

//         if (!$account) {
//             return response()->json(['message' => 'Account not found'], 404);
//         }

//         // Decode existing payment details
//         $existing = json_decode($account->payment_details, true) ?? [];

//         // New details coming from request (only one or more fields)
//         $new = $request->payment_details; 
//         // Example: ['upi' => 'newupi@okaxis']

//         // Merge so only sent keys update, others remain same
//         $merged = array_merge($existing, $new);

//         $account->update([
//             'payment_details' => json_encode($merged),
//             'updated_at' => now()
//         ]);

//         return response()->json([
//             'message' => 'Payment details updated successfully'
//         ], 200);
//     }

    public function updatePaymentDetails(Request $request)
    {
        $account = Account::find($request->account_id);

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $existing = json_decode($account->payment_details, true) ?? [];
        $incoming = $request->payment_details ?? [];

        if (isset($incoming['bank']) && is_array($incoming['bank'])) {

            $existingBanks = $existing['bank'] ?? [];

            foreach ($incoming['bank'] as $newBank) {

                // REMOVE bank account
                if (isset($newBank['id']) && !empty($newBank['deleted'])) {
                    $existingBanks = array_values(array_filter($existingBanks, function ($oldBank) use ($newBank) {
                        return $oldBank['id'] != $newBank['id'];
                    }));
                    continue;
                }

                // UPDATE or ADD
                $found = false;
                foreach ($existingBanks as &$oldBank) {
                    if (isset($oldBank['id'], $newBank['id']) && $oldBank['id'] == $newBank['id']) {
                        $oldBank = array_merge($oldBank, $newBank);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $existingBanks[] = $newBank;
                }
            }

            $existing['bank'] = $existingBanks;
            unset($incoming['bank']);
        }

        // Merge other types (upi, qr, link)
        $merged = array_merge($existing, $incoming);

        $account->update([
            'payment_details' => json_encode($merged),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Payment details updated successfully'
        ], 200);
    }  

    public function removeBankAccount(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'bank_id'    => 'required'
        ]);

        $account = Account::find($request->account_id);

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $paymentDetails = json_decode($account->payment_details, true) ?? [];

        if (empty($paymentDetails['bank'])) {
            return response()->json(['message' => 'No bank accounts found'], 404);
        }

        $banks = $paymentDetails['bank'];

        $filteredBanks = array_values(array_filter($banks, function ($bank) use ($request) {
            return $bank['id'] != $request->bank_id;
        }));

        if (count($banks) == count($filteredBanks)) {
            return response()->json(['message' => 'Bank account not found'], 404);
        }

        $paymentDetails['bank'] = $filteredBanks;

        $account->update([
            'payment_details' => json_encode($paymentDetails),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Bank account removed successfully'
        ], 200);
    }




    public function getPaymentDetails(Request $request)
    {
        $account = Account::where('id',$request->account_id)->first();

        if(!$account){
            return response()->json(['message' => 'Account not found'],404);
        }

        $paymentDetails = json_decode($account->payment_details,true);

        return response()->json([
            'payment_details' => $paymentDetails
        ], 200);
        
    }

    public function uploadQR(Request $request)
    {
        
            $file = $request->file('QR_img');
            $uploadPath = public_path('QR_images');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);
            $fileUrl = url('QR_images/' . $filename);
            return response()->json([
                'message' => 'QR uploaded successfully',
                'QR_url' => $fileUrl,
                
            ], 201);        
    }

    // public function getInvoiceByAccount(Request $request)
    // {
    //     $user = User::find(auth()->user()->id);



    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $payments = EasydocBillingPayment::where('account_id', $request->account_id)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     // dd($payments);

    //     if ($payments->isEmpty()) {
    //         return response()->json([
    //             'message' => 'No invoices found for this account'
    //         ], 404);
    //     }

    //     // Group by invoice_id → each group is an invoice
    //     $invoices = $payments->groupBy('invoice_id')->map(function ($items, $invoiceId) {
    //         return [
    //             'invoice_id' => $invoiceId,
    //             'invoice_date' => $items->first()->invoice_date,
    //             // 'payments' => $items->map(function ($item) {
    //             //     return [
    //             //         'payment_mode' => $item->payment_mode,
    //             //         'amount' => $item->amount,
    //             //         'transaction_date' => $item->transaction_date,
    //             //     ];
    //             // }),
    //             // 'total_amount' => $items->sum('amount'),
    //             // 'created_at' => $items->first()->created_at,

    //             'amount' => $items->first()->amount,
    //             'status' => $items->first()->status,
    //             'view_pay' => $items->first()->invoice_url                //  "sr.": 2,


    //             // "invice date": 2025,
    //             // "due date": 2025,
    //             // "Amount": 10000,
    //             // "status": "paid/unpaid",
    //             // "view/pay": "pdf invoice ki"
    //         ];
    //     })->values();

    //     return response()->json([
    //         'status' => true,
    //         'count' => $invoices->count(),
    //         'data' => $invoices
    //     ], 200);
    // }

   
    // public function addLogo(Request $request)
    // {
    //     $account = Account::where('id', $request->account_id)->first();
    //     if (!$account) {
    //         return response()->json(['message' => 'Account not found'], 204);
    //     }

    //     if ($request->hasFile('logo')) {
    //         $file = $request->file('logo');
    //         $filename = 'logo_' . $account->id . '.' . $file->getClientOriginalExtension();
    //         $filePath = $file->storeAs('uploads/accounts/logos', $filename, 'public');

    //         Account::where('id', $account->id)->update([
    //             'logo_url' => '/storage/' . $filePath,
    //             'updated_at' => now()
    //         ]);

    //         return response()->json(['message' => 'Logo uploaded successfully', 'logo_url' => '/storage/' . $filePath], 201);
    //     } else {
    //         return response()->json(['message' => 'No logo file provided'], 400);
    //     }
    // }
}
    