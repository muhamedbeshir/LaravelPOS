<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرتجع المشتريات - {{ $purchaseReturn->return_number }}</title>
    <style>
        body {
            font-family: 'XB Riyaz', 'Traditional Arabic', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .header img {
            max-height: 60px;
        }
        .header h1 {
            margin: 5px 0;
            color: #444;
        }
        .info-section {
            margin-bottom: 20px;
            clear: both;
        }
        .info-box {
            width: 48%;
            float: right;
            margin-bottom: 20px;
        }
        .info-box:nth-child(2) {
            float: left;
        }
        .info-box h3 {
            margin: 0 0 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
        }
        .total-row th {
            text-align: left;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists($companyLogo))
            <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
        @endif
        <h1>{{ $companyName }}</h1>
        <h2>مرتجع المشتريات</h2>
    </div>

    <div class="info-section clearfix">
        <div class="info-box">
            <h3>معلومات المرتجع</h3>
            <table>
                <tr>
                    <th>رقم المرتجع:</th>
                    <td>{{ $purchaseReturn->return_number }}</td>
                </tr>
                <tr>
                    <th>تاريخ المرتجع:</th>
                    <td>{{ $purchaseReturn->return_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th>نوع المرتجع:</th>
                    <td>
                        @if($purchaseReturn->return_type == 'full')
                            مرتجع كامل
                        @elseif($purchaseReturn->return_type == 'partial')
                            مرتجع جزئي
                        @else
                            مرتجع مباشر
                        @endif
                    </td>
                </tr>
                @if($purchaseReturn->purchase)
                <tr>
                    <th>فاتورة المشتريات الأصلية:</th>
                    <td>{{ $purchaseReturn->purchase->invoice_number }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="info-box">
            <h3>معلومات المورد</h3>
            <table>
                <tr>
                    <th>اسم المورد:</th>
                    <td>{{ $purchaseReturn->supplier->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>رقم الهاتف:</th>
                    <td>{{ $purchaseReturn->supplier->phone ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>اسم الشركة:</th>
                    <td>{{ $purchaseReturn->supplier->company_name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>الموظف المستلم:</th>
                    <td>{{ $purchaseReturn->employee->name ?? 'غير محدد' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="info-section">
        <h3>الأصناف المرتجعة</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>سعر الشراء</th>
                    <th>الإجمالي</th>
                    <th>السبب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseReturn->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'غير محدد' }}</td>
                    <td>{{ $item->unit->name ?? 'غير محدد' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->purchase_price, 2) }}</td>
                    <td>{{ number_format($item->quantity * $item->purchase_price, 2) }}</td>
                    <td>{{ $item->reason ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center">لا توجد أصناف</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <th colspan="5">المجموع:</th>
                    <th>{{ number_format($purchaseReturn->total_amount, 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($purchaseReturn->notes)
    <div class="info-section">
        <h3>ملاحظات</h3>
        <p>{{ $purchaseReturn->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>تم إنشاء هذا المستند بتاريخ: {{ $date }}</p>
    </div>
</body>
</html> 