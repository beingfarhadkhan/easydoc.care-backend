<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Clinic;
use App\Models\AssignToClinic;
use App\Models\Inventory;


class ServiceController extends Controller
{
    // Get all services
    // public function index(Request $request)
    // {     
    //     $services = Service::where('doctor_id',   )->get();
    //     return response()->json(['data' => $services], 200);
    // }

    public function index(Request $request)
    {
        $doctorId = auth()->id(); // logged-in doctor

        // 1. Get all clinics assigned to this doctor
        $clinicIds = AssignToClinic::where('user_id', $doctorId)->pluck('clinic_id');

        if ($clinicIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor is not assigned to any clinic',
                'data' => []
            ], 404);
        }

        // 2. Fetch all services from these clinics
        $services = Service::whereIn('clinic_id', $clinicIds)->get();

        return response()->json([
            'data' => $services
        ], 200);
    }



    // Create a new service
    // public function store(Request $request)
    // {       
    //     $service = Service::create([
    //         'clinic_id' =>$request->clinic_id,
    //         'service_name' => $request->service_name,
    //         'amount' => $request->amount,
    //         'created_at' => now(),
    //         'updated_at' => now()
    //     ]);

    //     return response()->json([
    //         'message' => 'Service created successfully',
    //         'data' => $service
    //     ], 201);
    // }


    public function store(Request $request)
    {
        $user = auth()->user(); // logged-in doctor or admin

        // Validate input
        $request->validate([
            'clinic_id' => 'required|integer',
            'service_name' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        // Check if user is doctor (not admin)
        if ($user->role == 2 || $user->role == 1) {

            //  Verify doctor is assigned to the clinic
            $assigned = AssignToClinic::where('user_id', $user->id)
                        ->where('clinic_id', $request->clinic_id)
                        ->exists();

            if (!$assigned) {
                return response()->json([
                    'message' => 'Unauthorized. Doctor is not assigned to this clinic.'
                ], 403);
            }
        }
        // If user is Admin/Superadmin → skip restrictions
        $clinic = Clinic::find($request->clinic_id);
        
    
        // Create the service
        $service = Service::create([
            'doctor_id'   => $user->id,
            'clinic_id'   => $request->clinic_id,
            'clinic_name' => $clinic ? $clinic->name : null,
            'service_name'=> $request->service_name,
            'amount'      => $request->amount,
            'created_at'  => now(),
            'updated_at'  => now()
        ]);

        return response()->json([
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }


    // Get a specific service
    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['status' => false, 'message' => 'Service not found'], 404);
        }
        $clinic = Clinic::find($service->clinic_id);
        $service->clinic_name = $clinic ? $clinic->clinic_name : null;
        return response()->json(['data' => $service]);
    }

    //Update a service
    public function update(Request $request)
    {
        $service = Service::where('id',$request->service_id)
        ->where('doctor_id', auth()->user()->id)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found or you are not authorized to update it'], 404);
        }

        $validated = $request->validate([
            'service_name' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
        ]);

        $service->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Service updated successfully',
            'data' => $service
        ]);
    }

    // Delete a service
    public function destroy(Request $request)
    {
        $service = Service::find($request->id);
        if (!$service) {
            return response()->json(['status' => false, 'message' => 'Service not found'], 404);
        }

        $service->delete();

        return response()->json(['status' => true, 'message' => 'Service deleted successfully']);
    }

    // 🔍 Recommendation API (Search Suggestion)
    public function recommendationService(Request $request)
    {
        $search = $request->search;

        if (!$search) {
            return response()->json(['status' => false, 'message' => 'Search parameter is required'], 400);
        }

        $recommendations = Service::where('service_name', 'LIKE', $search . '%')
            ->orderBy('service_name', 'asc')
            ->take(10)
            ->get(['id', 'service_name', 'amount']);          
            
        /* Inventory Recommendations */
        $inventories = Inventory::where(function ($q) use ($search) {
            $q->where('product_name', 'LIKE', $search . '%')
              ->orWhere('product_code', 'LIKE', $search . '%')
              ->orWhere('barcode', 'LIKE', $search . '%');
        })
            ->select(
            'product_name',
            'selling_price',
            'size',
            Inventory::raw('SUM(stock) as total_stock')
        )
        ->groupBy('product_name','selling_price','size')
        ->orderBy('product_name', 'asc')
        ->limit(10)
        ->get();

        $recommendations = $recommendations
        ->merge($inventories)
        ->values();

        return response()->json([
            'status' => true,
            'query' => $search,
            'recommendations' => $recommendations,
        ]);
    }
}
