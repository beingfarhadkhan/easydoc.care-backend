<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;

class PlanController extends Controller
{
    public function getPlan(Request $request)
    {
        $plan = Plan::where('id', $request->plan_id)->first();
        if ($plan) {
            return response()->json([
                'data' => $plan
            ]);    
        }
    }
}
