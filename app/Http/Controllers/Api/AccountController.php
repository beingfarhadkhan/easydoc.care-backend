<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AssignToAccount;
use App\Models\User;
use App\Models\AccountBilling;
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
            'no_of_admins_in_allowed' => $plan->admin_limit,
            'no_of_staff_in_use' => 0,
            'no_of_staff_allowed' => $plan->staff_limit,
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

        AssignToAccount::create([
            'user_id' => $request->user_id,
            'account_id' => $request->account_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

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
        
        $record = AssignToAccount::where('user_id', $request->user_id)
            ->where('account_id', $request->account_id)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'User is not assigned to the account'], 204);
        }

        AssignToAccount::where('user_id', $request->user_id)
            ->where('account_id', $request->account_id)
            ->delete();

        return response()->json(['message' => 'User removed from account successfully'], 200);
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
    