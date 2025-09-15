<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فواتير العميل</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
        }
        .total {
            text-align: left;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>فواتير العميل</h2>
    </div>

    <div class="customer-info">
        <p><strong>اسم العميل:</strong> {{ $customer->name }}</p>
        <p><strong>رقم الهاتف:</strong> {{ $customer->phone }}</p>
        @if($customer->notes)
        <p><strong>ملاحظات:</strong> {{ $customer->notes }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>التاريخ</th>
                <th>إجمالي المبلغ</th>
                <th>حالة الدفع</th>
                <th>عدد المنتجات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->id }}</td>
                <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
                <td>{{ $invoice->total_amount }}</td>
                <td>{{ $invoice->payment_status }}</td>
                <td>{{ $invoice->items->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <p><strong>إجمالي الفواتير:</strong> {{ $invoices->sum('total_amount') }}</p>
        <p><strong>عدد الفواتير:</strong> {{ $invoices->count() }}</p>
    </div>
</body>
</html> 