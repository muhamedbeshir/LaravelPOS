<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير العملاء</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-info {
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
        .summary {
            text-align: left;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>تقرير العملاء</h2>
    </div>

    <div class="report-info">
        <p><strong>نوع التقرير:</strong> {{ $type === 'credit' ? 'العملاء الآجلين' : 'جميع العملاء' }}</p>
        @if($startDate && $endDate)
        <p><strong>الفترة:</strong> من {{ $startDate }} إلى {{ $endDate }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم العميل</th>
                <th>رقم الهاتف</th>
                <th>نوع العميل</th>
                <th>عدد الفواتير</th>
                <th>إجمالي المبيعات</th>
                <th>إجمالي المدفوعات</th>
                <th>الرصيد المتبقي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->payment_type }}</td>
                <td>{{ $customer->invoices_count }}</td>
                <td>{{ $customer->invoices_sum_total_amount ?? 0 }}</td>
                <td>{{ $customer->payments_sum_amount ?? 0 }}</td>
                <td>{{ $customer->credit_balance }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>إجمالي عدد العملاء:</strong> {{ $customers->count() }}</p>
        <p><strong>إجمالي المبيعات:</strong> {{ $customers->sum('invoices_sum_total_amount') }}</p>
        <p><strong>إجمالي المدفوعات:</strong> {{ $customers->sum('payments_sum_amount') }}</p>
        <p><strong>إجمالي الأرصدة المتبقية:</strong> {{ $customers->sum('credit_balance') }}</p>
    </div>
</body>
</html> 