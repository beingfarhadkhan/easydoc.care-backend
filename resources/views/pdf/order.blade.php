<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #{{ $order->order_no }}</title>
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
        }
        .no-border td { border: none; }
        .company-info { text-align: right; }
        .invoice-title {
            /* background-color: #f7f7e8; */
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .highlight {
            /* background-color: #f7f7e8; */
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>

<!-- HEADER -->
<table class="no-border">
    <tr>
        <td style="width:50%">
            {{-- @if($clinic && $clinic->logo_url)
                <img src="{{ public_path('clinic_logo/'.$clinic->logo_url) }}" height="100px">
            @endif --}}
             @if($clinic && $clinic->logo_url)
                <img src="{{ public_path('clinic_logo/' . $clinic->logo_url) }}" alt="{{ $clinic->name }}" style="width:100px;"><br>
            @else
                <img src="{{ public_path('logo_img/demo.png') }}" alt="" style="width:100px;"><br>
            @endif
        </td>
        <td class="company-info">
            <strong>{{ $accountName->display_name ?? '' }}</strong><br>
            {{ $accountName->address ?? '' }}<br>
            {{ $accountName->city ?? '' }}, {{ $accountName->state ?? '' }} {{ $accountName->zip ?? '' }}<br>
            Phone: {{ $accountPhone ?? '' }}<br>
            GST: {{ $accountName->gst ?? '' }}
        </td>
    </tr>
</table>

<!-- META -->
<table style="margin-top:10px;">
    <tr>
        <td colspan="2" class="invoice-title">Receipt</td>
    </tr>
    <tr>
        <td><b>Order No:</b> {{ $order->order_no }}</td>
        <td class="text-right"><b>Date:</b> {{ $order->created_at->format('M d, Y') }}</td>
    </tr>
</table>

<!-- PATIENT -->
<table style="margin-top:15px;">
    <tr class="highlight">
        <th>Patient Details</th>
    </tr>
    <tr>
        <td>
            <b>Name:</b> {{ $patient->name ?? '-' }}<br>
            <b>Phone:</b> {{ $patient->phone ?? '-' }}<br>
            <b>Address:</b> @php
                                $addr = json_decode($order->address, true);
                            @endphp

                            {{ implode(', ', array_filter([
                                $addr['address_line_1'] ?? null,
                                $addr['address_line_2'] ?? null,
                                $addr['city'] ?? null,
                                $addr['state'] ?? null,
                                $addr['country'] ?? null,
                                $addr['pincode'] ?? null,
                            ])) }}
            {{-- {{json_decode($order->address)->address_line_1 ?? '' }} --}}
        </td>
    </tr>
</table>

<!-- PARTICULARS -->
<table style="margin-top:20px;">
    <thead>
        <tr class="highlight">
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Sub Total</th>
        </tr>
    </thead>
    <tbody>
        @php $subTotal = 0; @endphp
        @foreach($particulars as $p)
            @php
                $line = ($p['quantity'] ?? 0) * ($p['price'] ?? 0);
                $subTotal += $line;
            @endphp
            <tr>
                <td>{{ $p['service_name'] ?? '' }}</td>
                <td class="text-center">{{ $p['quantity'] ?? 0 }}</td>
                <td class="text-center">₹ {{ $p['price'] ?? 0 }}</td>
                <td class="text-center">₹ {{ number_format($line,2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="highlight">
            <td colspan="3" class="text-right">Total</td>
            <td class="text-center">₹ {{ number_format($subTotal,2) }}</td>
        </tr>
        <tr>
            <td colspan="3" class="text-right">Advance Paid</td>
            <td class="text-center">₹ {{ $order->advance_amount ?? 0 }}</td>
        </tr>
        <tr class="highlight">
            <td colspan="3" class="text-right">Balance Amount</td>
            <td class="text-center">₹ {{ $order->balance_amount ?? 0 }}</td>
        </tr>
    </tfoot>
</table>

<!-- REMARKS -->
<table style="margin-top:20px;">
    <tr class="highlight">
        <th>Remarks</th>
    </tr>
    <tr>
        <td>{{ $order->remarks ?? '-' }}</td>
    </tr>
</table>

<!-- FOOTER -->
<div class="footer">
    <table class="no-border">
        <tr>
            <td>
                _______________________<br>
                <b>{{ $clinic->name ?? '' }}</b><br>
                {{ $clinic->address ?? '' }}
            </td>
        </tr>
    </table>
    <p>Thank you for your order.</p>
</div>

</body>
</html>
