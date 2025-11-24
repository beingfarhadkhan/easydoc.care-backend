<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prescription</title>

    <style>
        @page { margin: 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.4; color: #000; }
        h2, h3 { margin: 0; padding: 0; }
        .header, .footer { text-align: center; }
        .header { margin-bottom: 6px; }
        .footer { position: fixed; bottom: 10px; left: 0; right: 0; font-size: 10px; color: #555; }
        .gray { color: #575656; }
        .section-title {
            font-weight: bold;
            margin-top: 16px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table th, table td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        table th { background: #f5f5f5; }
        .no-border td { border: none !important; }
        .small { font-size: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h2>{{ $prescription->doctor_name ?? 'Dr. Name' }}</h2>
    <div class="small gray">Not valid for Medico Legal Purpose</div>
</div>

<hr>

<!-- PATIENT DETAILS -->
@php
$vitals = $prescription_data['vitals'] ?? [];
@endphp

<p>
    <strong>{{ $patient['name'] ?? 'Patient Name' }}</strong>,
    {{ $patient['gender'] ?? '' }},
    {{ $patient['age'] ?? '' }} years,
    <span style="float: right">{{ \Carbon\Carbon::parse($prescription->created_at ?? now())->format('d/m/Y, H:i') }}</span>
</p>

<p><strong>UHID: </strong>{{ $prescription->uhid ?? '—' }}</p>

<!-- PATIENT MEDICAL HISTORY -->
@if(!empty($prescription_data['patientMedicalHistory']))
<div class="section-title">Patient Medical History</div>
@php $pmh = $prescription_data['patientMedicalHistory']; @endphp

@if(!empty($pmh['existingConditions']))
<p><strong>Existing Conditions:</strong> {{ implode(', ', $pmh['existingConditions']) }}</p>
@endif

@if(!empty($pmh['pastSurgicalProcedures']))
<p><strong>Past Surgical Procedures:</strong> {{ implode(', ', $pmh['pastSurgicalProcedures']) }}</p>
@endif

@if(!empty($pmh['familyHistory']))
<p><strong>Family History:</strong> {{ implode(', ', $pmh['familyHistory']) }}</p>
@endif

@if(!empty($pmh['foodAllergies']))
<p><strong>Food Allergies:</strong> {{ implode(', ', $pmh['foodAllergies']) }}</p>
@endif

@if(!empty($pmh['currentMedications']))
<p><strong>Current Medications:</strong> {{ implode(', ', $pmh['currentMedications']) }}</p>
@endif

@if(!empty($pmh['lifestyleHabits']))
<p><strong>Lifestyle Habits:</strong> {{ implode(', ', $pmh['lifestyleHabits']) }}</p>
@endif
@endif

<!-- VITALS -->
@if(!empty($vitals))
<div class="section-title">Vitals</div>
<p>
    SPO2: {{ $vitals['spo2'] ?? '' }} |
    Height: {{ $vitals['height'] ?? '' }} cm |
    Weight: {{ $vitals['weight'] ?? '' }} kg |
    BMI: {{ $vitals['bmi'] ?? '' }} |
    BP: {{ $vitals['bloodPressure'] ?? (($vitals['systolicBP'] ?? '') . '/' . ($vitals['diastolicBP'] ?? '')) }}
</p>
@endif

<!-- CHIEF COMPLAINTS -->
@if(!empty($prescription_data['chiefComplaints']))
<div class="section-title">Chief Complaints</div>
@foreach($prescription_data['chiefComplaints'] as $list_item)
<p>{{ $list_item['name'] }} (Since: {{ $list_item['since'] ?? '-' }})</p>
@endforeach
@endif

<!-- SYMPTOMS -->
@if(!empty($prescription_data['symptoms']))
<div class="section-title">Symptoms</div>
@foreach($prescription_data['symptoms'] as $list_item)
<p>{{ $list_item['name'] }} (Since: {{ $list_item['since'] ?? '-' }} | Severity: {{ $list_item['severity'] ?? '-' }})</p>
@endforeach
@endif

<!-- EXAMINATION -->
@if(!empty($prescription_data['examination']))
<div class="section-title">Examination Findings</div>
@foreach($prescription_data['examination'] as $list_item)
<p>{{ $list_item['name'] }} {{ $list_item['observation'] ? ' - '.$list_item['observation'] : '' }}</p>
@endforeach
@endif

<!-- DIAGNOSIS -->
@if(!empty($prescription_data['diagnosis']))
<div class="section-title">Diagnosis</div>
@foreach($prescription_data['diagnosis'] as $list_item)
<p>{{ $list_item['name'] }} (Condition: {{ $list_item['condition'] ?? '-' }} | Severity: {{ $list_item['severity'] ?? '-' }} | Notes: {{ $list_item['notes'] ?? '-' }})</p>
@endforeach
@endif

<!-- MEDICATIONS -->
@if(!empty($prescription_data['medications']))
<div class="section-title">Prescription</div>
<table>
    <tr>
        <th>#</th>
        <th>Medicine</th>
        <th>Dose</th>
        <th>Frequency</th>
        <th>Duration</th>
        <th>Remarks</th>
    </tr>
    @foreach($prescription_data['medications'] as $key => $med)
    <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $med['name'] ?? '-' }}</td>
        <td>{{ $med['dose'] ?? '-' }}</td>
        <td>{{ $med['frequency'] ?? '-' }}</td>
        <td>{{ $med['duration'] ?? '-' }}</td>
        <td>{{ $med['remarks'] ?? '-' }}</td>
    </tr>
    @endforeach
</table>
@endif

<!-- INJECTIONS -->
@if(!empty($prescription_data['injections']))
<div class="section-title">Injections</div>
@foreach($prescription_data['injections'] as $inj)
<p>{{ $inj['name'] ?? '-' }} ({{ $inj['dose'] ?? '-' }}) - {{ $inj['route'] ?? '-' }} - {{ $inj['frequency'] ?? '-' }} - {{ $inj['duration'] ?? '-' }}</p>
@endforeach
@endif

<!-- INVESTIGATIONS -->
@if(!empty($prescription_data['investigations']))
<div class="section-title">Investigations</div>
@foreach($prescription_data['investigations'] as $inv)
<p>{{ $inv['name'] ?? '-' }} - {{ $inv['test'] ?? '-' }} ({{ $inv['status'] ?? '-' }}) {{ $inv['notes'] ?? '' }}</p>
@endforeach
@endif

<!-- EYE REPORT -->
@if(!empty($prescription_data['eyeReport']))
<div class="page-break"></div>
<div class="section-title">Eye Report</div>

@foreach(['autoRefraction' => 'Auto Refraction', 'visualAcuity' => 'Visual Acuity', 'subjectiveRefraction' => 'Subjective Refraction', 'currentGlass' => 'Current Glass', 'finalGlass' => 'Final Glass', 'iop' => 'IOP'] as $key => $label)
@if(!empty($prescription_data['eyeReport'][$key]))
<h4>{{ $label }}</h4>
<table>
    <tr>
        @foreach(array_keys($prescription_data['eyeReport'][$key][0]) as $col)
            <th>{{ strtoupper($col) }}</th>
        @endforeach
    </tr>
    @foreach($prescription_data['eyeReport'][$key] as $row)
    <tr>
        @foreach($row as $val)
            <td>{{ $val ?? '-' }}</td>
        @endforeach
    </tr>
    @endforeach
</table>
@endif
@endforeach
@endif

<!-- DENTAL CHART -->
@if(!empty($prescription_data['dentalChart']))
<div class="section-title">Dental Chart</div>
<p><strong>Dentition Type:</strong> {{ $prescription_data['dentalChart']['dentitionType'] ?? '-' }}</p>

@if(!empty($prescription_data['dentalChart']['selectedTeeth']))
<p><strong>Selected Teeth:</strong> {{ implode(', ', $prescription_data['dentalChart']['selectedTeeth']) }}</p>
@endif

@if(!empty($prescription_data['dentalChart']['examinations']))
<p><strong>Examinations:</strong></p>
@foreach($prescription_data['dentalChart']['examinations'] as $tooth => $exam)
<p>Tooth {{ $tooth }}: {{ $exam }}</p>
@endforeach
@endif
@endif

<!-- ADVICE & FOLLOW-UP -->
@if(!empty($prescription_data['advice']) || !empty($prescription_data['followUp']))
<div class="section-title">Advice & Follow-Up</div>
<p><strong>Advice:</strong> {{ $prescription_data['advice'] ?? '-' }}</p>
<p><strong>Follow-Up:</strong> {{ $prescription_data['followUp'] ?? '-' }}</p>
@endif

{{-- <!-- SIGNATURE -->
<br><br>
<div class="footer">
    <strong>{{ $prescription->doctor_name ?? 'Dr. Name' }}</strong><br>
    <span class="small gray">Not valid for Medico Legal Purpose</span><br>
    <span class="small">Page {PAGE_NUM} of {PAGE_COUNT}</span>
</div>

<!-- DOMPDF FOOTER PAGE COUNT -->
<script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont("DejaVu Sans", "normal");
    $size = 9;
    $y = $pdf->get_height() - 10;
    $x = 35;
    $pdf->page_text($x, $y, "{{ $prescription->doctor_name ?? 'Dr. Name' }}", $font, $size, [0,0,0]);
    $pdf->page_text($pdf->get_width() - 130, $y, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, $size, [0,0,0]);
}
</script> --}}

</body>
</html>
