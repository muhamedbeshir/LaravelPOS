<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مرتجعات المبيعات</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .summary-item {
            text-align: center;
            flex: 1;
        }
        
        .summary-item h3 {
            margin: 0;
            color: #dc3545;
            font-size: 18px;
        }
        
        .summary-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        th {
            background-color: #343a40;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            color: white;
            border-radius: 3px;
        }
        
        .badge-secondary { background-color: #6c757d; }
        .badge-primary { background-color: #007bff; }
        .badge-info { background-color: #17a2b8; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        
        .text-danger { color: #dc3545 !important; }
        .text-muted { color: #6c757d !important; }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير مرتجعات المبيعات</h1>
        <p>من تاريخ: {{ $startDate }} إلى تاريخ: {{ $endDate }}</p>
        <p>تم إنشاء التقرير في: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h3>{{ number_format($totalReturns) }}</h3>
            <p>إجمالي المرتجعات</p>
        </div>
        <div class="summary-item">
            <h3>{{ number_format($totalAmount, 2) }}</h3>
            <p>إجمالي قيمة المرتجعات (ج.م)</p>
        </div>
        <div class="summary-item">
            <h3>{{ $totalReturns > 0 ? number_format($totalAmount / $totalReturns, 2) : '0.00' }}</h3>
            <p>متوسط قيمة المرتجع (ج.م)</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>رقم المرتجع</th>
                <th>تاريخ الإرجاع</th>
                <th>رقم الفاتورة</th>
                <th>تاريخ الفاتورة</th>
                <th>اسم العميل</th>
                <th>هاتف العميل</th>
                <th>نوع الإرجاع</th>
                <th>مبلغ الإرجاع (ج.م)</th>
                <th>المستخدم</th>
                <th>رقم الوردية</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesReturns as $return)
            <tr>
                <td><span class="badge badge-secondary">#{{ $return->id }}</span></td>
                <td>{{ \Carbon\Carbon::parse($return->return_date)->format('Y-m-d H:i') }}</td>
                <td>
                    @if($return->invoice_id)
                        <span class="badge badge-primary">#{{ $return->invoice_id }}</span>
                    @else
                        <span class="text-muted">لا يوجد</span>
                    @endif
                </td>
                <td>
                    @if($return->invoice_date)
                        {{ \Carbon\Carbon::parse($return->invoice_date)->format('Y-m-d H:i') }}
                    @else
                        <span class="text-muted">غير محدد</span>
                    @endif
                </td>
                <td>{{ $return->customer_name ?? 'غير محدد' }}</td>
                <td>{{ $return->customer_phone ?? 'غير محدد' }}</td>
                <td>
                    <span class="badge 
                        @switch($return->return_type)
                            @case('item') badge-info @break
                            @case('full_invoice') badge-danger @break
                            @case('partial_invoice') badge-warning @break
                            @default badge-secondary
                        @endswitch
                    ">
                        @switch($return->return_type)
                            @case('item') إرجاع صنف @break
                            @case('full_invoice') إرجاع فاتورة كاملة @break
                            @case('partial_invoice') إرجاع جزئي @break
                            @default {{ $return->return_type }}
                        @endswitch
                    </span>
                </td>
                <td><strong class="text-danger">{{ number_format($return->total_returned_amount, 2) }}</strong></td>
                <td>{{ $return->user_name ?? 'غير محدد' }}</td>
                <td>
                    @if($return->shift_id)
                        <span class="badge badge-info">#{{ $return->shift_id }}</span>
                    @else
                        <span class="text-muted">غير محدد</span>
                    @endif
                </td>
                <td>{{ $return->notes ?? 'لا توجد' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 20px;">
                    <span class="text-muted">لا توجد مرتجعات في الفترة المحددة</span>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة المتاجر - جميع الحقوق محفوظة</p>
    </div>
</body>
</html>