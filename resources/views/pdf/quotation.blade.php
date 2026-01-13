<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Quotation #{{ $quotation->id }}</title>
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

        .terms {
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
            <h2>QUOTATION</h2>
            <p><strong>Date:</strong> {{ $quotation->created_at->format('Y-m-d') }}</p>
            <p><strong>Quotation #:</strong> {{ $quotation->custom_quotation_id ?? 'Q-' . $quotation->id }}</p>
            <p><strong>Valid Till:</strong>
                {{ $quotation->valid_till ? $quotation->valid_till->format('Y-m-d') : 'N/A' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="customer-info">
        <h4>Quote To:</h4>
        @php $org = $quotation->organization_snapshot; @endphp
        <p><strong>{{ $org['organization_name'] ?? 'N/A' }}</strong></p>
        <p>{{ $org['address'] ?? '' }}</p>
        <p>Phone: {{ $org['phone'] ?? '' }} | Email: {{ $org['email'] ?? '' }}</p>
        @if(isset($org['contact_person_name']))
            <p>Attn: {{ $org['contact_person_name'] }}</p>
        @endif
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
            @php $grandTotal = 0; @endphp
            @foreach($quotation->products as $item)
                @php
                    $total = $item->quantity * $item->custom_price;
                    $grandTotal += $total;
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
        <p><strong>Total Amount: ${{ number_format($grandTotal, 2) }}</strong></p>
    </div>

    @if($quotation->terms_and_conditions)
        <div class="terms">
            <h4>Terms & Conditions</h4>
            <p>{!! nl2br(e($quotation->terms_and_conditions)) !!}</p>
        </div>
    @endif

</body>

</html>