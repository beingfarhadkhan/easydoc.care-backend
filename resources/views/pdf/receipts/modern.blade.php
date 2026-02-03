<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $receipt->receipt_no }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 6px 8px;
            border: 1px solid #bfbfbf;
            vertical-align: top;
        }

        .no-border, .no-border td {
            border: none !important;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-weight: bold;
        }

        .company-info {
            font-size: 13px;
            color: #333;
            text-align: right
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            color: #444;
            text-align: left;
        }

        .company-name {
            text-align: right;
        }

        .invoice-meta {
            font-size: 13px;
            text-align: left;
            margin-top: 5px;
        }

        .bill-patient-table th {
            /* background-color: #f7f7e8; */
            border: 1px solid #bfbfbf;
        }

        .info-table th {
            /* background-color: #f7f7e8; */
            border: 1px solid #bfbfbf;
        }

        .particulars-table th {
            /* background-color: #f7f7e8; */
            border: 1px solid #bfbfbf;
        }

        .highlight {
            /* background-color: #f7f7e8; */
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            text-align: center;
            color: #666;
        }

        .divider {
            height: 2px;
            background-color: #d6d4a0;
            margin: 8px 0 16px 0;
        }
        .invoice-title {
            /* background-color: #f7f7e8; */
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            /* padding: 10px 0; */
        }
    </style>
</head>
<body>

    <!-- HEADER -->

    <table class="header-table no-border-table" style="border: none;">
        <tr>
            <td style="width:50%; border: none;">
                @if($clinic && $clinic->logo_url)
                    <img src="{{ public_path('clinic_logo/' . $clinic->logo_url) }}" alt="{{ $clinic->name }}" style="width:100px;"><br>
                @else
                    <img src="{{ public_path('logo_img/demo.png') }}" alt="" style="width:100px;"><br>
                @endif
            </td>
            <td class="company-info" style="width:50%; border: none;">
                <strong>{{ $accountName->display_name ?? '' }}</strong><br>
                <b>Address:</b> {{ $accountName->address ?? '' }}<br>
                    {{ $accountName->city ?? '' }}, {{ $accountName->state ?? '' }}, {{ $accountName->zip ?? '' }}<br>
                    {{-- {{ $accountName->country ?? '' }}<br> --}}
                <b>Phone:</b> {{ $accountPhone ?? '' }}<br>
                @if(!empty($accountName->gst))
                <b>GSTIN:</b> {{ $accountName->gst }}
                @endif
            </td>
        </tr>
    </table>
    {{-- <div class="divider"></div> --}}
    <!-- META DATA -->
    <table class="meta-table" style="margin-top:10px;">
        <tr><td colspan="2"><div class="invoice-title">Receipt</div></td></tr>
        <tr>
            <td style="width:70%"><b>Receipt No.:</b>{{ $receipt->receipt_no }}</td>
            <td style="width:30%"><b>Date:</b> {{ $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') : now()->format('M d, Y') }}<br></td>
            {{-- <td style="width:30%"><b>Date:</b> {{ $receipt->created_at ? \Carbon\Carbon::parse($receipt->created_at)->format('M d, Y') : now()->format('M d, Y') }}<br></td> --}}
        </tr>
    </table>
    {{-- <table class="header-table no-border">
        <tr>
            
            <td class="invoice-title">
                <span style="color:#1f4e79;font-weight:bold;font-size:26px;">Your</span>
                <span style="color:#e6ac00;font-weight:bold;font-size:26px;">Logo</span><br>
                <span style="color:#444;font-size:22px;">INVOICE</span><br>
                <div class="invoice-meta">
                    DATE: {{ $receipt->created_at ? \Carbon\Carbon::parse($receipt->created_at)->format('M d, Y') : now()->format('M d, Y') }}<br>
                    INVOICE #: {{ $receipt->receipt_no }}
                </div>
            </td>
            <td class="company-name">
                 <strong>{{ $accountName->display_name ?? '-' }}</strong><br>
                <span class="company-info">                   
                    {{ $accountName->address ?? 'Address' }}<br>
                    {{ $accountName->city ?? '' }} {{ $accountName->state ?? '' }} {{ $accountName->zip ?? '' }}<br>
                    {{ $accountName->country ?? '' }}<br>
                    {{ $accountName->phone ?? '' }}<br>
                    Gst:{{ $accountName->gst ?? '' }}                    
                </span>
            </td>
        </tr>
    </table> --}}

    {{-- <div class="divider"></div> --}}

    <!-- BILL TO & PATIENT -->
    <table class="bill-patient-table" style="margin-top: 15px;">
        {{-- <tr style="width: 100%">
            <th><span style="color:#444;font-size:22px;">INVOICE</span><br></th>
        </tr>
        <tr>
            <td>
                INVOICE #: {{ $receipt->receipt_no }}
            </td>
            <td>
                DATE: {{ $receipt->created_at ? \Carbon\Carbon::parse($receipt->created_at)->format('M d, Y') : now()->format('M d, Y') }}<br>
            </td>
        </tr> --}}

        <tr style="width:100%;">
            <th style="text-align: left">Billed To:</th>
        </tr>
        <tr>
            <td>
                <b>Name:</b>{{ $patient->name ?? $patient->full_name ?? '-' }}<br>
                <b>Phone:</b>{{ $patient->phone ?? '' }}<br>
                <b>Address:</b>{{ $patient->address ?? 'Address' }} {{ $patient->city ?? '' }}<br> {{ $patient->state ?? '' }} {{ $patient->zip ?? '' }}
            </td>
        </tr>
        
    </table>

    <!-- PARTICULARS TABLE -->
    <table class="particulars-table" style="margin-top:20px;">
        <thead>
            <th colspan="6" style="background: none;">Description of Services & Products</th>
            <tr>
                <th>S No.</th>
                <th>Service Name</th>
                <th>Quantity</th>
                <th>Service Fee</th>
                <th>Discount %</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($particulars))
                    @foreach($particulars as  $index =>$p)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">
                                {{ is_array($p) ? ($p['service_name'] ?? '') : $p }}
                            </td>
                            <td class="text-center">
                                {{ is_array($p) ? ($p['quantity'] ?? '') : $p }}
                            </td>
                            <td class="text-center">
                                {{ is_array($p) ? ($p['service_fee'] ?? '') : $p }}
                            </td>
                            <td class="text-center">
                                {{ is_array($p) ? ($p['discount_percent'] ?? '') : $p }}
                            </td>
                            <td class="text-center">
                                {{ is_array($p)
                                    ? ($p['quantity'] * $p['service_fee'] * (1 - ($p['discount_percent'] / 100)))
                                    : $p
                                }}
                            </td>
                        </tr>

                    @endforeach
            @else
                <tr><td colspan="7" class="text-center">No data available</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="highlight">
                <td colspan="5" class="text-right">Total</td>
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

                    $receiptTotal = $subTotal - $additional_discount ?? 0;
                    // $balanceAmount = $receiptTotal - $advance_amount;

                    $currentPaid = 0;
                    if(is_array($payment_mode)){
                        foreach($payment_mode as $m){
                            $currentPaid += floatval($m['amount'] ?? 0);
                        }
                    }
                    
                    $totalPaid = ($advance_amount ?? 0) + $currentPaid;
                    $balanceAmount = $receiptTotal - $totalPaid;

                @endphp
                <td class="text-center">₹ {{ number_format($subTotal, 2) }}</td>

            </tr>
            <tr >
                <td colspan="5" class="text-right">Additional Discount</td>
                <td class="text-center">₹ {{ $additional_discount ?? '-' }}</td>
                
            </tr>
            <tr class="highlight">
                <td colspan="5" class="text-right">Receipt Total</td>
                <td class="text-center">₹ {{ number_format($receiptTotal, 2) }}</td>
            </tr>
            @if($advance_amount > 0)
                <tr>
                    <td colspan="5" class="text-right">
                        Advance Paid
                    </td>
                    <td class="text-center">₹ {{ number_format($advance_amount, 2) }}</td>
                </tr>
            @endif

           @if($currentPaid > 0)
                <tr>
                    <td colspan="5" class="text-right">Payment Received</td>
                    <td class="text-center">₹ {{ number_format($currentPaid, 2) }}</td>
                </tr>
            @endif

            <tr class="highlight">
                <td colspan="5" class="text-right">Total Paid</td>
                <td class="text-center">₹ {{ number_format($totalPaid, 2) }}</td>
            </tr>

            @if($balanceAmount > 0)
                <tr class="highlight">
                    <td colspan="5" class="text-right">Balance Payable</td>
                    <td class="text-center">₹ {{ number_format($balanceAmount, 2) }}</td>
                </tr>
            @elseif($balanceAmount < 0)
                <tr class="highlight">
                    <td colspan="5" class="text-right">Refund Amount</td>
                    <td class="text-center">₹ {{ number_format(abs($balanceAmount), 2) }}</td>
                </tr>
            @else
                <tr class="highlight">
                    <td colspan="5" class="text-right">Balance</td>
                    <td class="text-center">₹ 0.00</td>
                </tr>
            @endif

        </tfoot>
    </table>

    <!-- PAYMENT MODE -->
    {{-- <table class="particulars-table" style="margin-top:20px;">
        <thead>
            <tr class="highlight">
                <th>Payment Type</th>
                <th>Amount</th>
                <th>Transaction ID</th>
            </tr>
        </thead>
        <tbody>
            @if(is_array($payment_mode) && count($payment_mode) > 0)
                @foreach($payment_mode as $m)
                    <tr>
                        <td>{{ $m['type'] ?? '-' }}</td>
                        <td>{{ $m['amount'] ?? '-' }}</td>
                        <td>{{ $m['transaction_id'] ?? '-' }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="3" class="text-center">No payment details</td></tr>
            @endif
        </tbody>
    </table> --}}

    <!-- STATUS / REMARKS -->
    <table class="info-table" style="margin-top:20px;">
        <tr class="highlight">
            <th>Remarks</th>
        </tr>
        <tr>
            <td>{{ $receipt->remarks}}</td>
        </tr>
    </table>

    <div class="footer">
        <table style="width: 100%; margin-top: 10px; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: left; vertical-align: top;">
                    <div>
                        {{-- <div> {{ $docSign ?? '' }}</div> --}}
                        {{-- <div style="margin-bottom: 5px;">_______________________</div> --}}
                        {{-- <div><b>Dr. {{ $doctorName ?? 'Doctor Name' }}</b></div> --}}
                        {{-- <div><b>{{ $clinic->name ?? 'Clinic Name' }}</b></div>
                        <div>{{ $clinic->address ?? '' }}</div>
                        <div>{{ $clinic->city ?? '' }}, {{ $clinic->state ?? '' }} {{ $clinic->zip ?? '' }}</div> --}}
                        @if($clinic->show_doctor_signature == 1 || $clinic->show_doctor_name == 1)

                            @if($clinic->show_doctor_signature == 1 && !empty($docSign))
                                <div>{{ $docSign }}</div>
                                <div style="margin-bottom: 5px;">_______________________</div>
                            @endif

                            @if($clinic->show_doctor_name == 1)
                                <div><b>{{ $doctorName ?? 'Doctor Name' }}</b></div>
                            @endif

                            <div><b>{{ $clinic->name ?? 'Clinic Name' }}</b></div>
                            <div>{{ $clinic->address ?? '' }}</div>
                            <div>{{ $clinic->city ?? '' }}, {{ $clinic->state ?? '' }} {{ $clinic->zip ?? '' }}</div>

                        @endif

                    </div>
                </td>
                <td style="width: 50%; border: none;"></td>
            </tr>
        </table>

         @if(!empty($clinic->additional_content))
            <div class=" text-center" style="margin-top:5px;">
                {{ $clinic->additional_content }}
            </div>
        @endif

        <p style="margin-top: 5px; font-size: 12px; color: #666;">
            Thank you for your visit.
        </p>
        @if($clinic->show_doctor_signature == 0 && $clinic->show_doctor_name == 0)
            <div class="footer-note text-center" style="margin-top:5px;">
                ** This is a computer generated invoice and does not require a signature **
            </div>
        @endif
    </div>

</body>
</html>

