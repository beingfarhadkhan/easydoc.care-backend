<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\EasydocBillingPayment;
use App\Models\AssignToAccount;
use App\Models\Plan;
use App\Models\Dummy;
use App\Models\AccountBilling;
use Carbon\Carbon;
// use Illuminate\Support\Str;

class RazorPayPaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $user = auth()->user();
        $amount = $request->amount; // in rupees

        $bkAmount = $this->calculateAmount($request->plan_id, $request->addons);
        // dd($bkAmount);
        if ((int)$bkAmount !== (int)$amount) {
            return response()->json([
                'message' => 'Invalid Request. Please retry after sometime.'
            ], 400);
        }
        

        $api = new Api(
            env('RAZORPAY_TEST_KEY'),
            env('RAZORPAY_TEST_SECRET')
        );

        $invoiceId = $this->generateInvoiceId($request->account_id);

        $order = $api->order->create([
            'receipt' => $invoiceId,
            'amount' => $bkAmount * 100, // Razorpay needs paise
            'currency' => 'INR',
        ]);

        $plan = Plan::where('id', $request->plan_id)
            ->where('type', 1)
            ->firstOrFail();

        $addons = [];

        foreach ($request->addons ?? [] as $addon) {

            $addonPlan = Plan::where('id', $addon['addon_id'])
                ->where('type', 2)
                ->first();

            if (!$addonPlan) {
                continue;
            }

            $addons[] = [
                'addon_id'  => $addonPlan->id,
                'name'      => $addonPlan->plan_name,
                'quantity'  => $addon['quantity'],
                'price'     => $addonPlan->current_pricing,
                'old_price' => $addonPlan->old_pricing,
            ];
        }

        
        $invoiceData = [
            'account_id'     => $request->account_id,
            'plan_id'        => $plan->id,
            'plan_name'      => $plan->plan_name,
            'billing_cycle'  => $plan->validity,
            'price'          => $plan->current_pricing,
            'old_price'      => $plan->old_pricing,
            'addons'         => $addons,
            'total_amount'   => $bkAmount,
        ];

        // Save order in DB
        EasydocBillingPayment::create([
            'invoice_id' => $invoiceId,
            'user_id' => $user->id,
            'account_id' => $request->account_id,
            'amount' => $bkAmount,
            'razorpay_order_id' => $order['id'],
            'invoice_data' => json_encode($invoiceData),
            'status' => 'created',
            'invoice_date' => now()
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amount,
            'razorpayKey' => env('RAZORPAY_TEST_KEY')
        ]);
    }

    public function verifyPayment(Request $request)
    {
        // $api = new Api(
        //     env('RAZORPAY_TEST_KEY'),
        //     env('RAZORPAY_TEST_SECRET')
        // );

        // try {
        //     $api->utility->verifyPaymentSignature([
        //         'razorpay_order_id' => $request->razorpay_order_id,
        //         'razorpay_payment_id' => $request->razorpay_payment_id,
        //         'razorpay_signature' => $request->razorpay_signature,
        //     ]);
        // } catch (SignatureVerificationError $e) {
        //     return response()->json(['error' => 'Payment verification failed'], 400);
        // }

        $payment = EasydocBillingPayment::where(
            'razorpay_order_id',
            $request->razorpay_order_id
        )->first();

        // $payment->update([
        //     'razorpay_payment_id' => $request->razorpay_payment_id,
        //     'status' => 'paid',
        //     'captured' => 'yes',
        // ]);

        // Update accountBilling record based on account_id
        // $user = auth()->user();
        // $account = AssignToAccount::where('user_id', $user->id)->first();
        // $account_id = $account->id ?? null;
        // $accountBilling = AccountBilling::where('account_id', $account_id)->first();

        // if ($accountBilling) {
        //     $accountBilling->update([
        //         'payment_status' => 'paid',
        //         'last_payment_date' => now(),
        //         'razorpay_payment_id' => $request->razorpay_payment_id,
        //     ]);
        // }
        

        // return response()->json(['success' => true]);

         if (!$payment) {
            return response()->json(['error' => 'Order not found'], 404);
        }         
                
        return response()->json([
            'message' => 'Payment successful',
            'data' => $payment 
            
        ]);
    }

    private function generateInvoiceId($accountId)
    {
        $now = now();

        if ($now->month >= 4) {
            $fyStartYear = $now->year;        // 2025
            $fyStartDate = "{$fyStartYear}-04-01";
            $fyEndDate   = ($fyStartYear + 1) . "-03-31";
        } else {
            $fyStartYear = $now->year - 1;    // 2024
            $fyStartDate = "{$fyStartYear}-04-01";
            $fyEndDate   = $now->year . "-03-31";
        }

        $fyShort = substr($fyStartYear, -2);

        $lastInvoice = EasydocBillingPayment::whereBetween('created_at', [$fyStartDate, $fyEndDate])
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastInvoice && preg_match('/(\d{5})$/', $lastInvoice->invoice_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        $sequence = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Final format: INV-FY25-00001
        return "INV-FY{$fyShort}-{$sequence}";
    }


    // private function calculateAmount($plan_id,$addons = []){
    //     $plan = Plan::where('id',$plan_id)->where('type',1)->first();

    //     if(!$plan){
    //         return response()->json([
    //             'message' => 'Plan not exist'
    //         ],404);
    //     }

    //     $planPrice = $plan->current_pricing;

    //     $addonPrices = Plan::where('id', $plan_id)->where('type', 2)->value('current_pricing');
    //     // $addonPrices = json_decode($addonPricesJson, true);
    //     // [
    //     //     'doctor' => 1999,
    //     //     'staff' => 999,
    //     //     'admin' => 599,
    //     //     'clinic' => 2999,
    //     // ];

    //     $bkamount = $planPrice;

    //     foreach($addons as $addon => $quanity){
    //         $bkamount += $addonPrices[$addon] * $quantity;
    //     }

    //     $bkamount = $bkamount + ($bkamount * 0.18);

    //     return $bkamount;
    // }
    
    // private function calculateAmount($plan_id, $addons = [])
    // {
    // // Base plan
    // $plan = Plan::where('id', $plan_id)->where('type', 1)->first();

    // if (!$plan) {
    //     return response()->json(['message' => 'Plan not exist'], 404);
    // }

    // $bkamount = $plan->current_pricing;

    // // Addons
    // foreach ($addons as $addonPlanId => $quantity) {
    
    //         $addonPrice = Plan::where('id', $addonPlanId)
    //             ->where('type', 2)
    //             ->value('current_pricing');
    
    //         if (!$addonPrice) {
    //             continue; // skip invalid addon
    //         }
    
    //         $bkamount += $addonPrice * $quantity;
    //     }
    
    //     // GST 18%
    //     $bkamount += ($bkamount * 0.18);
    
    //     return $bkamount;
    // }
    private function calculateAmount($plan_id, $addons = [])
    {
        // Base plan
        $plan = Plan::where('id', $plan_id)
            ->where('type', 1)
            ->first();

        if (!$plan) {
            return response()->json(['message' => 'Plan not exist'], 404);
        }

        $bkamount = $plan->current_pricing;

        // Addons (array of objects)
        foreach ($addons as $addon) {

            // validate addon structure
            if (!isset($addon['addon_id'], $addon['quantity'])) {
                continue;
            }

            $addonPrice = Plan::where('id', $addon['addon_id'])
                ->where('type', 2)
                ->value('current_pricing');

            if (!$addonPrice) {
                continue; // skip invalid addon
            }

            $bkamount += $addonPrice * $addon['quantity'];
        }

        // GST 18%
        $bkamount += ($bkamount * 0.18);

        return ceil($bkamount);
    }




    public function razorpayWebhook(Request $request){

            // dd($request->all());
       $payment = EasydocBillingPayment::where(
            'razorpay_order_id',
            $request->razorpay_order_id
        )->first();
        
        if (!$payment) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            
        $paymentEntity = $request->payload['payment']['entity'];

        $payment->update([
            'webhook_data' => json_encode($request->payload),
            'razorpay_payment_id' => $paymentEntity['id'],
            'status' => $paymentEntity['status'],
            'captured' => $paymentEntity['captured'] ? 'yes' : 'no',
      ]);  


    if ($paymentEntity['captured'] === true) {

        $accountBilling = AccountBilling::where(
            'account_id',
            $payment->account_id
        )->first();

        if ($accountBilling) {

            $invoice = json_decode($payment->invoice_data, true);
            // dd($invoice);

            $plan = Plan::where('id', $invoice['plan_id'])
            ->where('type', 1)
            ->first();
         


            $docsAllowed    = $plan->doctor_limit;
            $staffAllowed   = $plan->staff_limit;
            $adminsAllowed  = $plan->admin_limit;
            $clinicsAllowed = $plan->clinic_limit;

            foreach ($invoice['addons'] ?? [] as $addon) {

            $addonPlan = Plan::where('id', $addon['addon_id'])
                ->where('type', 2)
                ->first();

            if (!$addonPlan) {
                continue;
            }

            $qty = $addon['quantity'];

            $docsAllowed    += ($addonPlan->doctor_limit    * $qty);
            $staffAllowed   += ($addonPlan->staff_limit   * $qty);
            $adminsAllowed  += ($addonPlan->admin_limit  * $qty);
            $clinicsAllowed += ($addonPlan->clinic_limit * $qty);
        }
            $plan_start_date = $accountBilling->plan_end_date ? Carbon::parse($accountBilling->plan_end_date)->addDay() : now();
            $accountBilling->update([
                'plan_name'               => $plan->plan_name,
                'plan_price'              => $plan->current_pricing,
                'billing_cycle'           => match ((int) $invoice['billing_cycle']) {
                                                12 => 'yearly',
                                                1  => 'monthly',
                                                default => null,
                                            },
                'plan_end_date'           => $plan_start_date->addMonth((int)$plan->validity) ,

                'no_of_docs_allowed'      => $docsAllowed,
                'no_of_staff_allowed'     => $staffAllowed,
                'no_of_admins_allowed'    => $adminsAllowed,
                'no_of_clinics_allowed'   => $clinicsAllowed,

                'current_plan_detail'     => $invoice,

                'current_plan_details' => $payment->invoice_data,

                'plan_start_date' => $accountBilling->plan_end_date ? Carbon::parse($accountBilling->plan_end_date)->addDay() : now(),
            ]);
        }
    }       

       return response()->json([
           'message' => 'Success'],200);
    }

}


