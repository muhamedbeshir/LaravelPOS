<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الوردية: {{ $shift->shift_number }}</title>
    
    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="{{ asset('/assets/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/all.min.css') }}">
    
    <style>
        @media print {
            body {
                font-size: 12px;
            }
            .card {
                border: none !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                color: #000 !important;
                border-bottom: 1px solid #dee2e6 !important;
                padding: 0.5rem !important;
            }
            .table {
                font-size: 11px;
            }
            .print-footer {
                display: block;
                position: fixed;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                padding: 10px;
                border-top: 1px solid #dee2e6;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .report-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .store-info {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .report-date {
            font-size: 0.8rem;
            margin-bottom: 15px;
        }
        
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .data-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .data-label {
            font-weight: bold;
            width: 150px;
        }
        
        .data-value {
            flex: 1;
        }
        
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        table.report-table th,
        table.report-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: right;
        }
        
        table.report-table th {
            background-color: #f8f9fa;
        }
        
        .print-only {
            display: none;
        }
        
        @media print {
            .print-only {
                display: block;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center mb-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> طباعة التقرير
        </button>
        <a href="{{ route('reports.shifts.show', $shift) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> العودة للتقرير
        </a>
    </div>

    <div class="report-header">
        <h1>تقرير الوردية: {{ $shift->shift_number }}</h1>
        <div class="store-info">
            {{ config('app.name', 'نظام نقاط البيع') }}
        </div>
        <div class="report-date">
            تاريخ الطباعة: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <!-- معلومات الوردية -->
    <div class="section-title">معلومات الوردية</div>
    <div class="row">
        <div class="col-md-6">
            <div class="data-row">
                <div class="data-label">رقم الوردية:</div>
                <div class="data-value">{{ $shift->shift_number }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">الكاشير الرئيسي:</div>
                <div class="data-value">{{ $shift->mainCashier->name ?? 'غير محدد' }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">المبلغ الابتدائي:</div>
                <div class="data-value">{{ number_format($shift->opening_balance, 2) }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">الرصيد المتوقع:</div>
                <div class="data-value">{{ number_format($shift->expected_closing_balance, 2) }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="data-row">
                <div class="data-label">وقت البدء:</div>
                <div class="data-value">{{ $shift->start_time ? $shift->start_time->format('Y-m-d H:i:s') : '-' }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">وقت الإغلاق:</div>
                <div class="data-value">{{ $shift->end_time ? $shift->end_time->format('Y-m-d H:i:s') : 'مفتوحة' }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">الرصيد الفعلي:</div>
                <div class="data-value">{{ $shift->actual_closing_balance ? number_format($shift->actual_closing_balance, 2) : '-' }}</div>
            </div>
            <div class="data-row">
                <div class="data-label">الفرق:</div>
                <div class="data-value">{{ $shift->difference ? number_format($shift->difference, 2) . ' (' . $financialStats['difference_status'] . ')' : '-' }}</div>
            </div>
        </div>
    </div>

    @if($shift->notes)
    <div class="data-row">
        <div class="data-label">ملاحظات:</div>
        <div class="data-value">{{ $shift->notes }}</div>
    </div>
    @endif

    <!-- ملخص الإحصائيات -->
    <div class="section-title">ملخص الإحصائيات</div>
    <div class="row">
        <div class="col-md-6">
            <table class="report-table">
                <tr>
                    <th colspan="2">الإحصائيات المالية</th>
                </tr>
                <tr>
                    <td>إجمالي المبيعات</td>
                    <td>{{ number_format($financialStats['total_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>المبيعات النقدية</td>
                    <td>{{ number_format($financialStats['cash_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>المبيعات الآجلة</td>
                    <td>{{ number_format($financialStats['credit_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>مبيعات الفيزا</td>
                    <td>{{ number_format($financialStats['visa_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>مبيعات التحويلات المالية</td>
                    <td>{{ number_format($financialStats['transfer_sales'], 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المرتجعات</td>
                    <td>{{ number_format($financialStats['total_returns'], 2) }}</td>
                </tr>
                <tr>
                    <td>إجمالي المسحوبات</td>
                    <td>{{ number_format($financialStats['total_withdrawals'], 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="report-table">
                <tr>
                    <th colspan="2">الإحصائيات العددية</th>
                </tr>
                <tr>
                    <td>إجمالي العمليات</td>
                    <td>{{ $countStats['total_transactions'] }}</td>
                </tr>
                <tr>
                    <td>عدد المبيعات</td>
                    <td>{{ $countStats['sales_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد الفواتير</td>
                    <td>{{ $countStats['invoices_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد المعاملات النقدية</td>
                    <td>{{ $countStats['cash_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد المعاملات الآجلة</td>
                    <td>{{ $countStats['credit_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد معاملات الفيزا</td>
                    <td>{{ $countStats['visa_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد معاملات التحويلات</td>
                    <td>{{ $countStats['transfer_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد المرتجعات</td>
                    <td>{{ $countStats['returns_count'] }}</td>
                </tr>
                <tr>
                    <td>عدد المسحوبات</td>
                    <td>{{ $countStats['withdrawals_count'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- الموظفين في الوردية -->
    <div class="section-title">الموظفين في الوردية</div>
    @if($shift->users->count() > 0)
        <table class="report-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الموظف</th>
                    <th>وقت الانضمام</th>
                    <th>وقت المغادرة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shift->users as $index => $user)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('Y-m-d H:i:s') : '-' }}</td>
                        <td>{{ $user->pivot->left_at ? \Carbon\Carbon::parse($user->pivot->left_at)->format('Y-m-d H:i:s') : 'لم يغادر' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>لا يوجد موظفين مسجلين في هذه الوردية</p>
    @endif

    <!-- المسحوبات من الصندوق -->
    @if($withdrawals->count() > 0)
        <div class="section-title page-break">المسحوبات من الصندوق</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الوقت</th>
                    <th>المبلغ</th>
                    <th>الموظف</th>
                    <th>السبب</th>
                </tr>
            </thead>
            <tbody>
                @foreach($withdrawals as $index => $withdrawal)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $withdrawal->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ number_format($withdrawal->amount, 2) }}</td>
                        <td>{{ $withdrawal->user->name ?? 'غير محدد' }}</td>
                        <td>{{ $withdrawal->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">الإجمالي</th>
                    <th colspan="3">{{ number_format($withdrawals->sum('amount'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    <!-- جميع المعاملات -->
    <div class="section-title page-break">جميع المعاملات في الوردية</div>
    @if(count($allTransactions) > 0)
        <table class="report-table">
            <thead>
                <tr>
                    <th>المرجع</th>
                    <th>النوع</th>
                    <th>الوقت</th>
                    <th>المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>البائع/العميل</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allTransactions as $transaction)
                    <tr>
                        <td>{{ $transaction['reference'] }}</td>
                        <td>
                            @if($transaction['type'] == 'sale')
                                بيع
                            @elseif($transaction['type'] == 'invoice')
                                فاتورة
                            @elseif($transaction['type'] == 'return')
                                مرتجع
                            @endif
                        </td>
                        <td>{{ $transaction['created_at']->format('Y-m-d H:i:s') }}</td>
                        <td>
                            @if($transaction['type'] == 'return')
                                <span style="color: red;">-{{ number_format(abs($transaction['amount']), 2) }}</span>
                            @else
                                {{ number_format($transaction['amount'], 2) }}
                            @endif
                        </td>
                        <td>
                            @php
                                $paymentName = $transaction['payment_method'];
                                
                                if($transaction['type'] == 'return') {
                                    $paymentName = 'مرتجع';
                                } else {
                                    switch($transaction['payment_method']) {
                                        case 'cash':
                                            $paymentName = 'نقداً';
                                            break;
                                        case 'credit':
                                            $paymentName = 'آجل';
                                            break;
                                        case 'visa':
                                            $paymentName = 'فيزا';
                                            break;
                                        case 'transfer':
                                            $paymentName = 'تحويل';
                                            break;
                                    }
                                }
                            @endphp
                            {{ $paymentName }}
                        </td>
                        <td>{{ $transaction['name'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>لا توجد معاملات في هذه الوردية</p>
    @endif

    <div class="print-footer print-only">
        تم إنشاء هذا التقرير في {{ now()->format('Y-m-d H:i:s') }} | {{ config('app.name', 'نظام نقاط البيع') }}
    </div>

    <!-- Bootstrap 5 JS (for print button) -->
    <script src="{{ asset('/assets/bootstrap.bundle.min.js') }}"></script>
</body>
</html> 