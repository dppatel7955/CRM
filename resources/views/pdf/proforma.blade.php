<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Proforma Invoice #{{ $proforma->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            color: #333;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .company-info {
            float: left;
        }

        .invoice-info {
            float: right;
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .customer-info {
            margin-bottom: 30px;
            float: left;
            width: 48%;
        }

        .shipping-info {
            margin-bottom: 30px;
            float: right;
            width: 48%;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .totals {
            text-align: right;
        }

        .notes {
            font-size: 12px;
            color: #666;
            margin-top: 30px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="company-info">
            <h3>My Company Name</h3>
            <p>123 Business Street<br>City, State, Zip<br>Phone: (123) 456-7890</p>
        </div>
        <div class="invoice-info">
            <h2>PROFORMA INVOICE</h2>
            <p><strong>Date:</strong> {{ $proforma->created_at->format('Y-m-d') }}</p>
            <p><strong>PI #:</strong> PI-{{ $proforma->id }}</p>
            <p><strong>PO Number:</strong> {{ $proforma->po_number ?? 'N/A' }}</p>
            <p><strong>PO Date:</strong> {{ $proforma->po_date ? $proforma->po_date->format('Y-m-d') : 'N/A' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div>
        <div class="customer-info">
            <h4>Bill To:</h4>
            @php $org = $proforma->organization_snapshot; @endphp
            <p><strong>{{ $org['organization_name'] ?? 'N/A' }}</strong></p>
            <p>{{ $org['address'] ?? '' }}</p>
            <p>Phone: {{ $org['phone'] ?? '' }} | Email: {{ $org['email'] ?? '' }}</p>
        </div>
        <div class="shipping-info">
            <h4>Ship To:</h4>
            <p>{!! nl2br(e($proforma->shipping_address ?? 'Same as Billing')) !!}</p>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="width: 80px;">Qty</th>
                <th style="width: 100px;">Unit Price</th>
                <th style="width: 100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $subTotal = 0; @endphp
            @foreach($proforma->products as $item)
                @php
                    $total = $item->quantity * $item->custom_price;
                    $subTotal += $total;
                    $prod = $item->product_snapshot;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $prod['product_name'] ?? 'Item' }}</strong><br>
                        <small>{{ $prod['model_name'] ?? '' }}</small>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->custom_price, 2) }}</td>
                    <td>${{ number_format($total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Subtotal: ${{ number_format($subTotal, 2) }}</p>

        @php $finalTotal = $subTotal; @endphp

        @if(!empty($proforma->charges))
            @foreach($proforma->charges as $charge)
                @php 
                            $amount = floatval($charge['amount']);
                    $finalTotal += $amount;
                @endphp
                    <p>{{ $charge['title'] }}: ${{ number_format($amount, 2) }}</p>
            @endforeach
        @endif
    
        @php
            $percentage = $proforma->invoice_percentage;
            $payable = ($finalTotal * $percentage) / 100;
        @endphp

    <p><strong>Total: ${{ number_format($finalTotal, 2) }}</strong></p>
    
        @if($percentage < 100)
                <p>Invoice Percentage: {{ $percentage }}%</p>
            <p style="font-size: 1.2em;"><strong>Payable Amount: ${{ number_format($payable, 2) }}</strong></p>
        @endif
  </di  v>

        <div class="notes">
    <h4>Bank Details</h4>
    <p>
Bank: Example Bank | Account: 123456789 | IFSC: EXAM0001234</p>
</div>

</body>
</html>
