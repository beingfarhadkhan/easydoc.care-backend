<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vital;


class VitalController extends Controller
{
    public function store(Request $request)
    {
        $existing = Vital::where('appointment_id', $request->appointment_id)->first();
        if ($existing) {
            $existing->update([
                'vitals' => json_encode($request->vitals),
                'lab_results' => json_encode($request->lab_results),
                'updated_at' => now()
            ]);
            return response()->json([
                'message' => 'Vital signs and lab results updated successfully',
                'vital' => $existing
            ], 200);
        }
        $vital = Vital::create([
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'vitals' => json_encode($request->vitals),
            'lab_results' => json_encode($request->lab_results),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Vital signs and lab results saved successfully',
            'vital' => $vital
        ], 201);
    }   

    public function show($id)
    {
        $vital = Vital::where('id', $id)->first();
        if (!$vital) {
            return response()->json(['message' => 'No vital signs or lab results found'], 404);
        }

        return response()->json([
            'vital_id' => $id,
            'patient_id' => $vital->patient_id,
            'appointment_id' => $vital->appointment_id,
            'vitals' => json_decode($vital->vitals),
            'lab_results' => json_decode($vital->lab_results)
        ], 200);
    }

    public function getVitalsByAppointment(Request $request)
    {
        $vital = Vital::where('appointment_id', $request->appointment_id)->first();
        if (!$vital) {
            return response()->json(['message' => 'No vital signs or lab results found for this appointment'], 404);
        }

        return response()->json([
            'vital_id' => $vital->id,
            'patient_id' => $vital->patient_id,
            'appointment_id' => $vital->appointment_id,
            'vitals' => json_decode($vital->vitals),
            'lab_results' => json_decode($vital->lab_results)
        ], 200);
    }

}
