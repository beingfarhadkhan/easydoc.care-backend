<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountBilling;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountBillingController extends Controller
{
    public function index()
    {
        return response()->json(AccountBilling::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'plan_name' => 'required|string',
            'plan_price' => 'nullable|numeric',
            'plan_start_date' => 'nullable|date',
            'plan_end_date' => 'nullable|date',
            'billing_cycle' => 'nullable|string',
            'no_of_docs_in_use' => 'nullable|integer',
            'no_of_docs_allowed' => 'nullable|integer',
            'no_of_admins' => 'nullable|integer',
            'no_of_staff_in_use' => 'nullable|integer',
            'no_of_staff_allowed' => 'nullable|integer',
            'no_of_clinics_in_use' => 'nullable|integer',
            'no_of_clinics_allowed' => 'nullable|integer',
        ]);

        $billing = AccountBilling::create($data);
        return response()->json(['message' => 'Billing plan added successfully', 'data' => $billing]);
    }

    public function getBillingByAccountId(request $request)
    {
     
        $billing = AccountBilling::where('account_id', $request->account_id)->first();

        if (!$billing) {
            return response()->json([
                'message' => 'No billing record found for this account ID'
            ], 404);
        }

        return response()->json([
            'message' => 'Billing record fetched successfully',
            'data' => $billing
        ], 200);
          

    }

    
}
