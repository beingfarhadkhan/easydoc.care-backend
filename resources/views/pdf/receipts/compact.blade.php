<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $receipt->receipt_no }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .receipt-box {
            width: 98%;
            border: 1px solid #000;
            padding: 1%;
        }

        .center {
            text-align: center;
        }

        .top-title {
            font-size: 14px;
            font-weight: bold;
        }

        .sub-title {
            font-size: 12px;
        }

        .row {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .col {
            display: table-cell;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .dotted {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 200px;
            padding: 0 5px;
        }

        .amount {
            margin-top: 20px;
            font-weight: bold;
        }

        .signature {
            margin-top: 30px;
            text-align: right;
        }

        .footer {
            font-size: 10px;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="receipt-box">

    {{-- HEADER --}}
    <div class="center">
        <div class="top-title">{{ $clinic->name }}</div>
        <div class="sub-title">{{ $doctorName }}</div>
        {{-- <div class="sub-title">079927</div> --}}
    </div>

    <br>

    {{-- RECEIPT INFO --}}
    <div class="row">
        <div class="col">
            <strong>ReceiptNo.:</strong> {{ $receipt['receipt_no'] }}
        </div>
        <div class="col right">
            {{-- <strong>Date:</strong> {{ $receipt->created_at ? \Carbon\Carbon::parse($receipt->created_at)->format('M d, Y') : now()->format('M d, Y') }} --}}
            <strong>Date:</strong> {{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') : now()->format('M d, Y') }}
        </div>
    </div>

    <br>

    {{-- CONTENT --}}
    <p>
        Received with thanks from
        <span class="dotted">{{ $patient->name }}, {{ $patient->gender == 1 ? 'M' : ($patient->gender == 2 ? 'F' : '') }}, {{ $patient->age }}</span>
    </p>
                @php
                    $subTotal = 0;
                    if(!empty($particulars) && is_array($particulars)){
                        foreach($particulars as $p){
                            if(is_array($p)){
                                $q = floatval($p['quantity'] ?? 0);
                                $fee = floatval($p['service_fee'] ?? 0);
                                $disc = floatval($p['discount_percent'] ?? 0);
                                $subTotal += $q * $fee * (1 - ($disc / 100));
                            }
                        }
                    }
                @endphp
    <p>
        a sum of ₹
        <span class="dotted">{{ number_format($subTotal - ($additional_discount ?? 0), 2) }}</span>
        via
        <span class="dotted">
            {{-- {{ $receipt['payment_mode'] }} --}}
             @if($payment_mode)
            {{ collect($payment_mode)->pluck('type')->map(function($type) {
                return $type == 1 ? 'Cash' : ($type == 2 ? 'UPI' : ($type == 3 ? 'Razorpay' : ''));
            })->join(', ') }}
            @endif
        </span>

    </p>

    <p>
        towards
        <span class="dotted">
            @if($particulars)
            {{ collect($particulars)->pluck('service_name')->join(', ') }}
            @endif
        </span>
    </p>

    {{-- AMOUNT --}}
    <div class="row amount">
        <div class="col">
            Amount: ₹ {{ number_format($subTotal - ($additional_discount ?? 0), 2) }}
        </div>
        <div class="col right">
            Signature
        </div>
    </div>

    {{-- SIGNATURE --}}
    <div class="signature">
        {{ $docSign }}
        <b>{{ $doctorName ?? 'Doctor Name' }}</b>

    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Generated on {{ $receipt['created_at'] ?? date('d/m/Y') }}
    </div>

</div>

</body>
</html>
