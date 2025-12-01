<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Clinic;
use App\Models\AssignToClinic;
use App\Models\PatientToAccount;
use App\Models\User;
use App\Models\Account;


use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patients = Patient::all();
        return response()->json([
            'patient' => $patients,
            'counts' => count($patients)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $patient = Patient::create([
            'clinic_id' => $request->clinic_id,
            'uhid' => $request->uhid,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'dob' => $request->dob,             
            'age' => $request->age,
            'ref_doctor_name' => $request->ref_doctor_name,
            'ref_doctor_phone' => $request->ref_doctor_phone,
            'alt_phone' => $request->alt_phone,
            'city' => $request->city,
            'pin' => $request->pin,
            'blood_group' => $request->blood_group,
            'marital_status' => $request->marital_status,
            'occupation' => $request->occupation,
            'guardian_name' => $request->guardian_name,
            'guardian_relation' => $request->guardian_relation,
            'patient_language' => $request->patient_language,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $account = Clinic::where('id', $request->clinic_id)->first();

        PatientToAccount::create([
            'patient_id' => $patient->id,
            'account_id' => $account->account_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $patient
            ], 201);    
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $patient = Patient::find($id);
        if(!$patient){
            return response()->json(['message' => 'Patient not found'], 404);
        }
        // Return patient details as JSON
        return response()->json([
            'patient_id' => $id,
            'patient' => $patient
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
        $patient = Patient::where('id', $request->patient_id)->first();
        if (!$patient) {
            return response()->json(['message' => 'Patient not found'], 404);
        }

        Patient::where('id',$request->patient_id)->update([
            'clinic_id' => $request->clinic_id,
            // 'uhid' => $request->uhid,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'dob' => $request->dob,             
            'age' => $request->age,
            'ref_doctor_name' => $request->ref_doctor_name,
            'ref_doctor_phone' => $request->ref_doctor_phone,
            'alt_phone' => $request->alt_phone,
            'city' => $request->city,
            'pin' => $request->pin,
            'blood_group' => $request->blood_group,
            'marital_status' => $request->marital_status,
            'occupation' => $request->occupation,
            'guardian_name' => $request->guardian_name,
            'guardian_relation' => $request->guardian_relation,
            'patient_language' => $request->patient_language,
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Patient updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function recommendPatients(Request $request)
    {
        $search = $request->search;
        // $patient = PatientToAccount::where('account_id', $request->account_id)->pluck('patient_id');
        // $recommendPatients = Patient::whereIn('id', $patient)
        //                     ->where(function ($query) use ($search) {
        //                     $query->whereRaw('LOWER(name) LIKE ?', [strtolower($search) . '%'])
        //                         ->orWhereRaw('LOWER(email) LIKE ?', [strtolower($search) . '%'])
        //                         ->orWhereRaw('LOWER(phone) LIKE ?', [strtolower($search) . '%']);
        //                     })->get();
        
    

        $accountId = $request->account_id;
        $recommendPatients = Patient::where('name', 'like', ''.$search.'%')
            ->whereHas('accounts', function($q) use ($accountId) {
                $q->where('account_id', $accountId);
            })
            ->get();



        return response()->json($recommendPatients);
    }

    public function getAllPatientsByClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        $patients = Patient::where('clinic_id', $request->clinic_id)->get();
        
        if(!$patients){
            return response()->json(['message' => 'No patients found'], 404);
        }
        
        return response()->json([
            'patients' => $patients
        ]);
    }


    public function getAllPatientByAccount(Request $request)
    {
       $account = Account::where('id', $request->account_id)->first();
        
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $patients = Patient::where('account_id', $request->account_id)->get();
        
        if(!$patients){
            return response()->json(['message' => 'No patients found'], 404);
        }
        
        return response()->json([
            'patients' => $patients
        ]);
    }

    public function getPatientByPhone(Request $request)
    {
        $patient = Patient::where('phone', $request->phone)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'No patient found with this phone number',
            ], 404);
        }
        return response()->json([
            'patient_id' => $patient->id,
            'patient' => $patient,
        ], 200);
    }

}