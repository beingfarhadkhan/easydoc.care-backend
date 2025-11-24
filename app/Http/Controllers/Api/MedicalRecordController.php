<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Appointment;
use App\Models\AssignToClinic;

class MedicalRecordController extends Controller
{
    public function uploadFile(Request $request)
    {
        // Validate file
        $request->validate([
            'file' => 'required|file|max:5120', // Max 5MB
        ]);

        // Get the file
        $file = $request->file('file');

        // Create upload directory if not exists
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Generate unique file name
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Move file to public/uploads
        $file->move($uploadPath, $fileName);

        // Generate URL
        $fileUrl = url('uploads/' . $fileName);

        // Return JSON response
        return response()->json([
            'message' => 'File uploaded successfully.',
            'file_url' => $fileUrl,
        ], 201);
    }

    public function saveMedicalRecord(Request $request)
    {
        // var_dump($request->records);
        foreach ($request->records as $r) {
            $m = MedicalRecord::where('document_url', $r['document_url'])->first();
            if (!$m) {
                MedicalRecord::create([
                    'patient_id' => $request->patient_id,
                    'appointment_id' => $request->appointment_id,
                    'record_type' => $r['record_type'],
                    'document_url' => $r['document_url'],
                    'investigation_date' => isset($r['investigation_date']) ? date('Y-m-d', strtotime($r['investigation_date'])) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]); 
            } else {
                // Optionally update existing record
                $m->update([
                    'record_type' => $r['record_type'],
                    'investigation_date' => isset($r['investigation_date']) ? date('Y-m-d', strtotime($r['investigation_date'])) : $m->investigation_date,
                    'updated_at' => now(),
                ]);
                $m->save();
            }                         
        }
        
        return response()->json([
            'message' => 'Records saved successfully.'
        ], 201);
    }

    public function deleteMedicalRecord(Request $request)
    {
        $record = MedicalRecord::where('document_url', $request->document_url)->first();
        if (!$record) {
            return response()->json(['message' => 'Medical record not found'], 404);
        }

        $record->delete();

        $file_name = Str::afterLast($request->document_url, '/');
        $filePath = public_path("uploads/".$file_name);
        
        if (File::exists($filePath)) {
            File::delete($filePath);

            return response()->json([
                'message' => 'Medical record and file deleted successfully'
            ], 200);
        }else{
            return response()->json([
                'message' => 'file not found'
            ], 200);
        }        
    }

    public function getMedicalRecordsByAppointment(Request $request)
    {
                
        // Validate appointment_id presence
        if (!$request->has('appointment_id')) {
            return response()->json(['message' => 'appointment_id is required'], 422);
        }
        // Find the appointment
        $appointment = Appointment::find($request->appointment_id);
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found'], 404);
        }

        $atc = AssignToClinic::where('user_id', auth()->user()->id)->where('clinic_id',$request->clinic_id)->first();

        if($request->clinic_id == $appointment->clinic_id && $atc){
            // Retrieve and return medical records for the appointment
            $records = MedicalRecord::where('appointment_id', $request->appointment_id)->get();
            return response()->json([
                'records' => $records
            ], 200);
        } else {
            return response()->json(['message' => 'Invalid Appointment ID'], 403);
        }
    }


}
