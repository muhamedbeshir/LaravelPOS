<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>كشف حساب مورد: {{ $supplier->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
        }
        .details {
            margin-bottom: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            padding: 5px;
            text-align: right;
        }
        .statement-table {
            width: 100%;
            border-collapse: collapse;
        }
        .statement-table th, .statement-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        .statement-table th {
            background-color: #f2f2f2;
        }
        .text-center { text-align: center; }
        .text-danger { color: #e74a3b; }
        .font-weight-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>كشف حساب مورد</h1>
            <h2>{{ $supplier->name }}</h2>
            @if($startDate || $endDate)
                <p>
                    <strong>الفترة من:</strong> {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : 'البداية' }}
                    <strong>إلى:</strong> {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('Y-m-d') : 'النهاية' }}
                </p>
            @endif
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>الرصيد الحالي:</th>
                    <td class="{{ $supplier->remaining_amount > 0 ? 'text-danger' : '' }} font-weight-bold">
                        {{ number_format($supplier->remaining_amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <table class="statement-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>البيان</th>
                    <th>مدين (فاتورة)</th>
                    <th>دائن (دفعة)</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statement as $transaction)
                <tr>
                    <td>{{ $transaction['transaction_date'] }}</td>
                    <td>{{ $transaction['description'] }}</td>
                    <td>{{ $transaction['debit'] > 0 ? number_format($transaction['debit'], 2) : '-' }}</td>
                    <td>{{ $transaction['credit'] > 0 ? number_format($transaction['credit'], 2) : '-' }}</td>
                    <td>{{ number_format($transaction['balance'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">لا توجد معاملات لعرضها.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html> 