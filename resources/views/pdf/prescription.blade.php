<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Prescription - PDF</title>
    <style>
        @page {
            margin-top: 12mm;
            margin-bottom: 14mm;
            margin-left: 10mm;
            margin-right: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11.5px;
            line-height: .75;
            color: #000;
            letter-spacing: 0.2px;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 2px;
            margin-top: -6px;
        }
        .header h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            color: #222;
        }
        .sub-header {
            font-size: 9px;
            color: #555;
        }

        hr { display:none; }

        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            margin-bottom: 2px;
            padding: 0;
            line-height: 1.2;
            align-items: center
    }

        /* TABLES */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            font-size: 10.5px;
            table-layout: fixed;
            line-height: 1px;
    }
        th, td {
            border: 1px solid #bfbfbf;
            padding: 3px 4px;
            vertical-align: middle;
            text-align: left;
            line-height: 1.25;
    }
        th {
            background-color: #f7f7e8;
            border: 1px solid #bfbfbf;
            font-weight: bold;
    }

        /* LIST SECTIONS */
        .item-block p {
            margin: 3px 0;
        }

        /* MULTI-COLUMN LAYOUT */
        .two-col {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .two-col div {
            width: 50%;
        }
         .company-info {
            font-size: 13px;
            color: #333;
            text-align: right
        }

        /* FOOTER */
        .footer {
        position: fixed;
        bottom: 4px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 9px;
        color: #555;
    }
    </style>
</head>
<body>

    <!-- HEADER -->

    <table class="header-table no-border-table" style="border: none;">
        <tr>
            <td style="width:50%; border: none;">
                @if($clinic && $clinic->logo_url)
                    <img src="{{ public_path('clinic_logo/' . $clinic->logo_url) }}" alt="{{ $clinic->name }}" style="height:100px;"><br>
                @else
                    <img src="{{ public_path('logo_img/demo.png') }}" alt="" style="height:100px;"><br>
                @endif
            </td>
            <td class="company-info" style="width:50%; border: none;">
                <strong>Dr. {{ $prescription->doctor_name ?? 'Doctor Name' }}</strong><br>
                {{-- {{ $prescription->doctor_specialization ?? 'Specialization' }}<br>
                {{ $prescription->doctor_registration_no ?? 'Reg. No.' }}<br><br> --}}
                <strong>{{ $clinic->name ?? 'Clinic Name' }}</strong><br>
                <b>Address:</b> {{ $clinic->address ?? 'Address' }}<br>
                    {{ $clinic->city ?? '' }} {{ $clinic->state ?? '' }} {{ $clinic->zip ?? '' }}<br>
                <b>Phone:</b> {{ $clinic->phone ?? '' }}<br>
                <b>Email:</b> {{ $clinic->email ?? '' }}
            </td>
        </tr>
    </table>
{{-- <div class="header">
    <h2>{{ $prescription->doctor_name ?? 'Dr. Name' }}</h2>
    <div class="sub-header">Not valid for Medico Legal Purpose</div>
</div> --}}

<hr>

<!-- PATIENT DETAILS -->

<p>
    <strong>{{ $patient['name'] ?? 'Patient Name' }}</strong>,
    {{ $patient['gender'] ?? '' }},
    {{ $patient['age'] ?? '' }} years
    <span style="float:right">{{ \Carbon\Carbon::parse($prescription->created_at ?? now())->format('d/m/Y, H:i') }}</span>
</p>
<p><strong>UHID:</strong> {{ $prescription->uhid ?? '—' }}</p>

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
@if(!empty($prescription_data['vitals']))
<div class="section-title">Vitals</div>
@php
    $vitals = $prescription_data['vitals'];
@endphp
<p>
    SPO2: {{ $vitals['spo2'] ?? '' }} |
    Height: {{ $vitals['height'] ?? '' }} cm |
    Weight: {{ $vitals['weight'] ?? '' }} kg |
    BMI: {{ $vitals['bmi'] ?? '' }} |
    BP: {{ $vitals['bloodPressure'] ?? (($vitals['systolicBP'] ?? '') . '/' . ($vitals['diastolicBP'] ?? '')) }}
</p>


@endif

<!-- SYMPTOMS -->
@if(!empty($prescription_data['symptoms']))
<div class="section-title">Symptoms</div>
@foreach($prescription_data['symptoms'] as $list_item)
<p>{{ $list_item['name'] }} (Since: {{ $list_item['since'] ?? '-' }} | Severity: {{ $list_item['severity'] ?? '-' }})</p>
@endforeach
@endif

<!-- Chief Complaintsgit branch -M main -->
@if(!empty($prescription_data['chiefComplaints']))
<div class="section-title">Chief Complaints</div>
@foreach($prescription_data['chiefComplaints'] as $list_item)
<p>{{ $list_item['name'] }} (Since: {{ $list_item['since'] ?? '-' }} | Severity: {{ $list_item['severity'] ?? '-' }})</p>
@endforeach
@endif

<!-- DIAGNOSIS -->
@if(!empty($prescription_data['diagnosis']))
<div class="section-title">Diagnosis</div>
@foreach($prescription_data['diagnosis'] as $list_item)
<p>{{ $list_item['name'] }} (Since: {{ $list_item['since'] ?? '-' }} | Status: {{ $list_item['condition'] ?? '-' }})</p>
@endforeach
@endif

<!-- PRESCRIPTION -->
@if(!empty($prescription_data['medications']))
{{-- <div class="section-title">Prescription</div> --}}
<table>
    <tr>
        <th colspan="6" style="text-align:center;">Prescription</th>
    </tr>
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
<p>{{ $inj['name'] ?? '-' }} - {{ $inj['route'] ?? '-' }} ({{ $inj['dose'] ?? '-' }})</p>
@endforeach
@endif
<!---->

<!-- LAB TESTS / INVESTIGATIONS -->
@if(!empty($prescription_data['investigations']))
<div class="section-title">Investigations</div>
@foreach($prescription_data['investigations'] as $inv)
<p>{{ $inv['name'] }} - {{ $inv['test'] }} ({{ $inv['status'] }}) {{ $inv['notes'] }}</p>
@endforeach
@endif

<!-- EXAMINATION -->
@if(!empty($prescription_data['examination']))
<div class="section-title">Examination Findings</div>
@foreach($prescription_data['examination'] as $exam)
<p>{{ $exam['name'] }} | {{ $exam['observation'] }}</p>
@endforeach
@endif


<!-- EYE REPORT -->
@if(!empty($prescription_data['eyeReport']))
{{-- <div style="page-break-before: always;"></div> --}}
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

<!-- PRESCRIBED LAB TESTS -->
@if(!empty($prescription_data['prescribedLabTests']))
<div class="section-title">Prescribed Lab Tests</div>
@foreach($prescription_data['prescribedLabTests'] as $test)
<p>{{ $test['name'] ?? '-' }} (On: {{ $test['on'] ?? '-' }} | Repeat: {{ $test['repeat'] ?? '-' }})</p>
@endforeach
@endif

<!-- INVESTIGATIVE READINGS -->
@if(!empty($prescription_data['investigativeReadings']))
<div class="section-title">Investigative Readings</div>
@foreach($prescription_data['investigativeReadings'] as $read)
<p>{{ $read['name'] ?? '-' }} : {{ $read['value'] ?? '-' }} [{{ $read['flag'] ?? '' }}] - {{ $read['date'] ?? '' }}</p>
@endforeach
@endif

<!-- NOTES -->
@if(!empty($prescription_data['notes']))
<div class="section-title">Notes</div>
<p>{{ $prescription_data['notes'] }}</p>
@endif

<!-- DENTAL PROCEDURES -->
@if(!empty($prescription_data['dentalProcedures']))
<div class="section-title">Dental Procedures</div>
<table>
<tr>
<th>#</th><th>Procedure</th><th>Teeth</th><th>Surfaces</th><th>Visits</th><th>Date</th>
</tr>
@foreach($prescription_data['dentalProcedures'] as $i => $proc)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $proc['procedure'] ?? '-' }}</td>
<td>{{ $proc['teeth'] ?? '-' }}</td>
<td>{{ $proc['surfaces'] ?? '-' }}</td>
<td>{{ $proc['visits'] ?? '-' }}</td>
<td>{{ $proc['date'] ?? '-' }}</td>
</tr>
@endforeach
</table>
@endif

<!-- ADVANCED EYE SECTIONS -->
@if(!empty($prescription_data['visualAcuity']))
<div class="section-title">Visual Acuity Test</div>
<table>
<tr><th>EYE</th><th>UCDVA</th><th>UCNVA</th><th>PH</th><th>BCDVA</th><th>BCNVA</th></tr>
@foreach($prescription_data['visualAcuity'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['ucdva'] }}</td>
<td>{{ $row['ucnva'] }}</td>
<td>{{ $row['ph'] }}</td>
<td>{{ $row['bcdva'] }}</td>
<td>{{ $row['bcnva'] }}</td>
</tr>
@endforeach
</table>
@endif

@if(!empty($prescription_data['subjectiveRefraction']))
<div class="section-title">Subjective Refraction</div>
<table>
<tr><th>EYE</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>ADD</th><th>DVA</th><th>NVA</th></tr>
@foreach($prescription_data['subjectiveRefraction'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['sph'] }}</td>
<td>{{ $row['cyl'] }}</td>
<td>{{ $row['axis'] }}</td>
<td>{{ $row['add'] }}</td>
<td>{{ $row['dva'] }}</td>
<td>{{ $row['nva'] }}</td>
</tr>
@endforeach
</table>
@endif

@if(!empty($prescription_data['autoRefraction']))
<div class="section-title">Auto Refraction</div>
<table>
<tr><th>EYE</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>ADD</th><th>DVA</th><th>NVA</th></tr>
@foreach($prescription_data['autoRefraction'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['sph'] }}</td>
<td>{{ $row['cyl'] }}</td>
<td>{{ $row['axis'] }}</td>
<td>{{ $row['add'] }}</td>
<td>{{ $row['dva'] }}</td>
<td>{{ $row['nva'] }}</td>
</tr>
@endforeach
</table>
@endif

@if(!empty($prescription_data['currentGlass']))
<div class="section-title">Current Glass Prescription</div>
<table>
<tr><th>EYE</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>ADD</th><th>DVA</th><th>NVA</th></tr>
@foreach($prescription_data['currentGlass'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['sph'] }}</td>
<td>{{ $row['cyl'] }}</td>
<td>{{ $row['axis'] }}</td>
<td>{{ $row['add'] }}</td>
<td>{{ $row['dva'] }}</td>
<td>{{ $row['nva'] }}</td>
</tr>
@endforeach
</table>
@endif

@if(!empty($prescription_data['finalGlass']))
<div class="section-title">Final Glass Prescription</div>
<table>
<tr><th>EYE</th><th>SPH</th><th>CYL</th><th>AXIS</th><th>ADD</th><th>DVA</th><th>NVA</th></tr>
@foreach($prescription_data['finalGlass'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['sph'] }}</td>
<td>{{ $row['cyl'] }}</td>
<td>{{ $row['axis'] }}</td>
<td>{{ $row['add'] }}</td>
<td>{{ $row['dva'] }}</td>
<td>{{ $row['nva'] }}</td>
</tr>
@endforeach
</table>
@endif

<!-- IOP -->
@if(!empty($prescription_data['iop']))
<div class="section-title">IOP</div>
<table>
<tr><th>EYE</th><th>NCT</th><th>GAT</th><th>CCT</th><th>CIOP</th></tr>
@foreach($prescription_data['iop'] as $row)
<tr>
<td>{{ $row['eye'] }}</td>
<td>{{ $row['nct'] }}</td>
<td>{{ $row['gat'] }}</td>
<td>{{ $row['cct'] }}</td>
<td>{{ $row['ciop'] }}</td>
</tr>
@endforeach
</table>
@endif


<!-- ADVICE -->
@if(!empty($prescription_data['advice']))
<div class="section-title">Advice</div>
<p>{{ $prescription_data['advice'] }}</p>
@endif

<!-- FOLLOWUP -->
@if(!empty($prescription_data['followUp']))
<div class="section-title">Follow-Up</div>
<p>{{ $prescription_data['followUp'] }}</p>
@endif

</body>
</html>
