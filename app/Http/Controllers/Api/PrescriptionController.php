<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prescription;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Dictionary;
use App\Models\Clinic;
use PDF;

class PrescriptionController extends Controller
{
    public function store(Request $request)
    {  
        $existing = Prescription::where('appointment_id', $request->appointment_id)->first();   
        if ($existing) {
            $existing->update([
                'prescription_data' => json_encode($request->prescription_data),
                'updated_at' => now()
            ]);

            $data = $request->prescription_data;

        $categories = [
            'chiefComplaints' => 'chief_complaint',
            'symptoms' => 'symptom',
            'diagnosis' => 'diagnosis',
            'medications' => 'medication',
            'injections' => 'injection',
            'investigations' => 'investigation',
            'examinationFinding' => 'examination_finding',
            'oralFinding' => 'oral_finding'
        ];

        foreach ($categories as $key => $type) {
            if (!empty($data[$key])) {
                foreach ($data[$key] as $item) {
                    $name = $item['name'] ?? null;
                    if ($name && !Dictionary::where('name', $name)->exists()) {
                        Dictionary::create([
                            'type' => $type,
                            'name' => $name
                        ]);
                    }
                }
            }
        }

            $pdf_url = $this->generatePdf($existing->id);
        
            $existing->update([
                'prescription_url' => $pdf_url,
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Receipt updated and PDF regenerated successfully',
                'receipt' => $existing->fresh(),
                ], 200);
        }

        $prescription = Prescription::create([
            'patient_id' => $request->patient_id,
            'clinic_id' => $request->clinic_id,
            'appointment_id' => $request->appointment_id,
            'prescription_data' => json_encode($request->prescription_data),
            'prescription_url' => $request->prescription_url ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $data = $request->prescription_data;

        $categories = [
            'chiefComplaints' => 'chief_complaint',
            'symptoms' => 'symptom',
            'diagnosis' => 'diagnosis',
            'medications' => 'medication',
            'injections' => 'injection',
            'investigations' => 'investigation',
            'examinationFinding' => 'examination_finding',
            'oralFinding' => 'oral_finding'
        ];

        foreach ($categories as $key => $type) {
            if (!empty($data[$key])) {
                foreach ($data[$key] as $item) {
                    $name = $item['name'] ?? null;
                    if ($name && !Dictionary::where('name', $name)->exists()) {
                        Dictionary::create([
                            'type' => $type,
                            'name' => $name
                        ]);
                    }
                }
            }
        }

        $pdf_url = $this->generatePdf($prescription->id);

        Prescription::where('id',$prescription->id)->update([
            'prescription_url' => $pdf_url,
            'updated_at' => now()
        ]);
        return response()->json([
            'message' => 'Prescription saved successfully',
        ], 201);
    }

    public function getPrescriptionByAppointment(Request $request)
    {
        $prescription = Prescription::where('appointment_id', $request->appointment_id)->first();
        if (!$prescription) {
            return response()->json([
                'message' => 'No prescription found for this appointment',
            ], 404);
        }
        return response()->json([
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
            'appointment_id' => $prescription->appointment_id,
            'prescription_data' => json_decode($prescription->prescription_data),
            'prescription_url' => $prescription->prescription_url,
        ], 200);
    }
    
    public function show($id)
    {
        $prescription = Prescription::where('id', $id)->first();
        if (!$prescription) {
            return response()->json(['message' => 'No prescription found'], 404);
        }

        return response()->json([
            'prescription_id' => $id,
            'patient_id' => $prescription->patient_id,
            'appointment_id' => $prescription->appointment_id,
            'prescription_data' => json_decode($prescription->prescription_data),
            'prescription_url' => $prescription->prescription_url,
        ], 200);
    }

    
    private function generatePDF($id)
    {
        $prescription = Prescription::findOrFail($id);
        $patient = Patient::find($prescription->patient_id);
        $clinic = Clinic::find($prescription->clinic_id);

        $prescription_data = json_decode($prescription->prescription_data, true);

         $uploadDir = public_path('prescription_pdf');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // PDF filename
        $filename = 'prescription_' . $id . '.pdf';
        $fullPath = $uploadDir . '/' . $filename;

        // If file already exists, remove it before regenerating
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // $pdf = PDF::loadView('pdf.prescription', compact(
        //     'prescription_data',
        // ))->setPaper('a4', 'portrait');


        $pdf = PDF::loadView('pdf.prescription', [
            'prescription' => $prescription,
            'patient' => $patient,
            'clinic' => $clinic,
            'prescription_data' => json_decode($prescription->prescription_data, true),
        ])->setPaper('a4', 'portrait');

        // Add footer using callback
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->get_font("DejaVu Sans", "normal");
        $size = 9;

        $w = $canvas->get_width();
        $h = $canvas->get_height();

        // Doctor name (left side)
        $canvas->page_text(35, $h - 30, $prescription->doctor_name ?? 'Dr. Name', $font, $size, [0, 0, 0]);

        // Page numbers (right side)
        $canvas->page_text($w - 130, $h - 30, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, $size, [0, 0, 0]);


        // $pdf = PDF::loadView('pdf.prescription', ['data' => $prescription_data = json_decode($prescription->prescription_data, true)]);
   
        $pdf->save($fullPath);

        $url = asset('prescription_pdf/' . $filename);

        $prescription->update(['pdf_url' => $url]);

        return  $url;
    }

    public function getRecommendations(Request $request)
    {
        $type = $request->type; // e.g. chief_complaint
        $search = $request->query('search');

        $query = Dictionary::query()
            ->where('type', $type)
            ->where('name', 'LIKE', "%{$search}%")
            ->orderBy('name', 'asc')
            ->get(['name']);

        return response()->json($query);
    }

}




// patient id
// appoinment id 
// clinic id 
// prescription url
// prescription data {}
// created at 
// updated at  



// vacine{}
// vaccination data 
