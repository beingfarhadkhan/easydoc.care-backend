<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\AssignToClinic;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $appointments = Appointment::all();
        return response()->json($appointments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $appointment = Appointment::create([
            'clinic_id' => $request->clinic_id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'mode' => $request->mode,
            'appointment_date' => $request->appointment_date,
            'duration' => $request->duration,
            'time_slot' => $request->time_slot,
            'check_in_status' => $request->check_in_status,
            'type' => $request->type,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $appointment = Appointment::find($id);
        if(!$appointment){
            return response()->json(['message' => 'Appointment not found'], 404); 
        }
        // Return appointment details as JSON
        return response()->json([
            'appointment_id' => $id,
            'appointment' => $appointment
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $appointment = Appointment::where('id', $request->appointment_id)->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        Appointment::where('id',$request->appointment_id) ->update([
            'clinic_id' => $request->clinic_id,
            'patient_id' => $request->patient_id,
            'mode' => $request->mode,
            'appointment_date' => $request->appointment_date,
            'duration' => $request->duration,
            'time_slot' => $request->time_slot,
            'check_in_status' => $request->check_in_status,
            'type' => $request->type,
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getAllAppointmentsByClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        // $appointments = Appointment::with('patient')->where('clinic_id', $request->clinic_id)->get();
        $allfutureappointments = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->whereDate('appointment_date', '>', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get();

        $todayappointments = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->whereDate('appointment_date', now()->toDateString())
            ->orderBy('time_slot', 'asc')
            ->get();

        // $booked = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
        //     ->where('clinic_id', $request->clinic_id)
        //     ->whereDate('appointment_date', $request->appointment_date)
        //     ->where('type', 1)
        //     ->where('check_in_status','<',3)
        //     ->orderBy('time_slot', 'asc')
        //     ->get();
        
        $followup = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('type', 2)
            ->where('check_in_status','<',3)
            ->orderBy('time_slot', 'asc')
            ->get();

        $checkin = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('check_in_status',2)
            ->get();
        
        $completed = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->whereDate('appointment_date', now()->toDateString())
            ->where('check_in_status', 4)
            ->orderBy('time_slot', 'asc')
            ->get();
        
        return response()->json([
            // 'appointments' => $appointments,
            // 'booked' => $booked,
            'followup' => $followup,
            'allfutureappointments' => $allfutureappointments,
            'todayappointments' => $todayappointments,
            'checkin' => $checkin,
            'completed' => $completed
        ]);
    }

    public function getAllAppointmentsByPatientInClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        $allfutureappointments = Appointment::with(['patient:id,uhid,name,phone,gender,age'])
            ->where('clinic_id', $request->clinic_id)
            ->where('patient_id', $request->patient_id)
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('time_slot', 'asc')
            ->get();
        
        return response()->json($allfutureappointments);
    }

    public function cancelAppointment(Request $request)
    {
        $appointment = Appointment::find($request->appointment_id);
        
        if (! $appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        if ($appointment->check_in_status == 2) {
            return response()->json(['message' => 'Cannot cancel a checked-in appointment'], 400);
        }

        if ($appointment->check_in_status == 3) {
            return response()->json(['message' => 'Appointment already cancelled'], 400);
        }

        $appointment->check_in_status = 3;
        $appointment->updated_at = now();
        $appointment->save();

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'appointment' => $appointment
        ]);
    }

    public function getAllPastVisitsByPatient(Request $request)
    {   
        $userId = auth()->user()->id;

        $assignedClinicIds = AssignToClinic::where('user_id', $userId)
            ->pluck('clinic_id')
            ->toArray();

        if (empty($assignedClinicIds)) {
            return response()->json(['message' => 'No clinics assigned to this user'], 404);
        }

        $patient = Patient::find($request->patient_id);
        if (!$patient) {
            return response()->json(['message' => 'Patient not found'], 404);
        }

        $pastAppointments = Appointment::with([
            'clinic:id,name,address,phone,email',
            'patient:id,uhid,name,phone,gender,age'            
        ])
        ->where('patient_id', $request->patient_id)
        ->whereIn('clinic_id', $assignedClinicIds)
        ->whereDate('appointment_date', '<', now()->toDateString())
        ->orderBy('appointment_date', 'desc')
        ->orderBy('time_slot', 'desc')
        ->get();

        if ($pastAppointments->isEmpty()) {
            return response()->json(['message' => 'No past visits found'], 404);
        }

        
        $pastAppointments->transform(function ($appointment) {
            
        $prescription = Prescription::where('patient_id', $appointment->patient_id)
            ->where('appointment_id', $appointment->id)
            ->select('prescription_url')
            ->first();
        $appointment->prescription_pdf = $prescription ? $prescription->prescription_url : null;

        return $appointment;
        });

        return response()->json([
            'patient_id' => $patient->id,
            'total_visits' => $pastAppointments->count(),
            'visits' => $pastAppointments
        ]);
    }

}
