<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;

class PlanController extends Controller
{
    public function getPlan(Request $request)
    {
        $plan = Plan::where('type',1)->get();
        if ($plan) {
            return response()->json([
                'data' => $plan
            ]);    
        }
    }
    public function getAddon(Request $request)
    {
        $plan = Plan::where('type',2)->get();
        if ($plan) {
            return response()->json([
                'data' => $plan
            ]);    
        }
    }
}
