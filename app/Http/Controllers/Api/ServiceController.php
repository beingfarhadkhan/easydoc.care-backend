<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;


class ServiceController extends Controller
{
    // Get all services
    public function index(Request $request)
    {     
        $services = Service::where('clinic_id', $request->clinic_id)->get();
        return response()->json(['data' => $services], 200);
    }

    // Create a new service
    public function store(Request $request)
    {       
        $service = Service::create([
            'clinic_id' =>$request->clinic_id,
            'service_name' => $request->service_name,
            'amount' => $request->amount,
            'created_at' => now(),
            'updated_at' => now()
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
        return response()->json(['data' => $service]);
    }

    // Update a service
    public function update(Request $request)
    {
        $service = Service::find($request->service_id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
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
    public function destroy($id)
    {
        $service = Service::find($id);
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

        return response()->json([
            'status' => true,
            'query' => $search,
            'recommendations' => $recommendations
        ]);
    }
}
