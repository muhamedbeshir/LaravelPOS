@extends('layouts.app')

@section('title', 'تقرير الوردية: ' . $shift->shift_number)

@section('content')
<div class="container-fluid">
    <!-- بطاقة معلومات الوردية -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">معلومات الوردية</h5>
                        <div>
                            <a href="{{ route('reports.shifts.print', $shift) }}" class="btn btn-light btn-sm" target="_blank">
                                <i class="fas fa-print"></i> طباعة التقرير
                            </a>
                            <a href="{{ route('reports.shifts.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right"></i> العودة للقائمة
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        if ($shift->is_closed) {
                            // للورديات المغلقة، استخدم البيانات المحفوظة
                            $expectedDrawer = $shift->expected_closing_balance;
                            $cashSales = $shift->invoices()->where('type', 'cash')->sum('total');
                            $deposits = $shift->total_deposits ?? 0;
                            $purchases = $shift->total_purchases ?? 0;
                            $expenses = $shift->total_expenses ?? 0;
                            $returns = $shift->returns_amount ?? 0;
                        } else {
                            // للورديات المفتوحة، احسب الرصيد الحالي
                            $cashSales = $shift->invoices()->where('type', 'cash')->sum('total');
                            $deposits = $shift->current_deposits_total ?? 0;
                            $purchases = $shift->current_purchases_total ?? 0;
                            $expenses = $shift->current_expenses_total ?? 0;
                            $returns = $shift->returns_amount ?? $shift->current_returns_total ?? 0;
                            
                            // Calculate expected drawer amount
                            $expectedDrawer = $shift->opening_balance + $cashSales + $deposits - $purchases - $expenses - $returns;
                        }
                    @endphp
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">رقم الوردية:</label>
                                <p>{{ $shift->shift_number }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">الكاشير الرئيسي:</label>
                                <p>{{ $shift->mainCashier->name ?? 'غير محدد' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">وقت البدء:</label>
                                <p>{{ $shift->start_time ? $shift->start_time->format('Y-m-d H:i:s') : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">وقت الإغلاق:</label>
                                <p>{{ $shift->end_time ? $shift->end_time->format('Y-m-d H:i:s') : 'مفتوحة' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">المبلغ الابتدائي:</label>
                                <p>{{ number_format($shift->opening_balance, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">الرصيد المتوقع في الدرج (نقدي):</label>
                                <p>{{ number_format($expectedDrawer, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">الرصيد الفعلي:</label>
                                <p>{{ $shift->actual_closing_balance ? number_format($shift->actual_closing_balance, 2) : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">الفرق:</label>
                                @php
                                    // للورديات المغلقة، استخدم البيانات المحفوظة
                                    $difference = $shift->is_closed ? 
                                        $shift->cash_shortage_excess : 
                                        ($shift->actual_closing_balance ? $shift->actual_closing_balance - $expectedDrawer : 0);
                                    $differenceStatus = '';
                                    if ($difference > 0) {
                                        $differenceStatus = 'زيادة';
                                    } elseif ($difference < 0) {
                                        $differenceStatus = 'عجز';
                                    } else {
                                        $differenceStatus = 'متطابق';
                                    }
                                @endphp
                                <p class="{{ $difference > 0 ? 'text-success' : ($difference < 0 ? 'text-danger' : '') }}">
                                    {{ $shift->actual_closing_balance ? number_format($difference, 2) . ' (' . $differenceStatus . ')' : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="fw-bold">ملاحظات:</label>
                                <p>{{ $shift->notes ?? 'لا توجد ملاحظات' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات المبيعات -->
    <div class="row">
        <!-- إحصائيات مالية -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">إحصائيات المبيعات المالية</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>إجمالي المبيعات</th>
                                    <td>{{ number_format($financialStats['total_sales'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>المبيعات النقدية</th>
                                    <td>{{ number_format($cashSales, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>المبيعات الآجلة</th>
                                    <td>{{ number_format($financialStats['credit_sales'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>مبيعات الفيزا</th>
                                    <td>{{ number_format($financialStats['visa_sales'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>مبيعات التحويلات المالية</th>
                                    <td>{{ number_format($financialStats['transfer_sales'], 2) }}</td>
                                </tr>
                                <tr class="table-danger">
                                    <th>إجمالي المرتجعات</th>
                                    <td>{{ number_format($returns, 2) }}</td>
                                </tr>
                                <tr class="table-warning">
                                    <th>إجمالي المسحوبات</th>
                                    <td>{{ number_format($financialStats['total_withdrawals'], 2) }}</td>
                                </tr>
                                <tr class="table-info">
                                    <th>إجمالي المشتريات</th>
                                    <td>{{ number_format($purchases, 2) }}</td>
                                </tr>
                                <tr class="table-danger">
                                    <th>إجمالي المصروفات</th>
                                    <td>{{ number_format($expenses, 2) }}</td>
                                </tr>
                                <tr class="table-success">
                                    <th>إجمالي الإيداعات</th>
                                    <td>{{ number_format($deposits, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- إحصائيات عددية -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">إحصائيات المبيعات العددية</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>إجمالي العمليات</th>
                                    <td>{{ $countStats['total_transactions'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد المبيعات</th>
                                    <td>{{ $countStats['sales_count'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد الفواتير</th>
                                    <td>{{ $countStats['invoices_count'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد المعاملات النقدية</th>
                                    <td>{{ $countStats['cash_count'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد المعاملات الآجلة</th>
                                    <td>{{ $countStats['credit_count'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد معاملات الفيزا</th>
                                    <td>{{ $countStats['visa_count'] }}</td>
                                </tr>
                                <tr>
                                    <th>عدد معاملات التحويلات</th>
                                    <td>{{ $countStats['transfer_count'] }}</td>
                                </tr>
                                <tr class="table-danger">
                                    <th>عدد المرتجعات</th>
                                    <td>{{ $countStats['returns_count'] }}</td>
                                </tr>
                                <tr class="table-warning">
                                    <th>عدد المسحوبات</th>
                                    <td>{{ $countStats['withdrawals_count'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المسحوبات من الصندوق -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">المسحوبات من الصندوق</h5>
                </div>
                <div class="card-body">
                    @if($withdrawals->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
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
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">الإجمالي</th>
                                        <th colspan="3">{{ number_format($withdrawals->sum('amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            لا توجد مسحوبات في هذه الوردية
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- آخر المعاملات -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">آخر 10 معاملات في الوردية</h5>
                </div>
                <div class="card-body">
                    @if(count($latestTransactions) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
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
                                    @foreach($latestTransactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction['reference'] }}</td>
                                            <td>
                                                @if($transaction['type'] == 'sale')
                                                    <span class="badge bg-primary">بيع</span>
                                                @elseif($transaction['type'] == 'invoice')
                                                    <span class="badge bg-success">فاتورة</span>
                                                @elseif($transaction['type'] == 'return')
                                                    <span class="badge bg-danger">مرتجع</span>
                                                @endif
                                            </td>
                                            <td>{{ $transaction['created_at']->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                @if($transaction['type'] == 'return')
                                                    <span class="text-danger">-{{ number_format(abs($transaction['amount']), 2) }}</span>
                                                @else
                                                    {{ number_format($transaction['amount'], 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = 'bg-secondary';
                                                    $paymentName = $transaction['payment_method'];
                                                    
                                                    if($transaction['type'] == 'return') {
                                                        $badgeClass = 'bg-danger';
                                                        $paymentName = 'مرتجع';
                                                    } else {
                                                        switch($transaction['payment_method']) {
                                                            case 'cash':
                                                                $badgeClass = 'bg-success';
                                                                $paymentName = 'نقداً';
                                                                break;
                                                            case 'credit':
                                                                $badgeClass = 'bg-secondary';
                                                                $paymentName = 'آجل';
                                                                break;
                                                            case 'visa':
                                                                $badgeClass = 'bg-primary';
                                                                $paymentName = 'فيزا';
                                                                break;
                                                            case 'transfer':
                                                                $badgeClass = 'bg-info';
                                                                $paymentName = 'تحويل';
                                                                break;
                                                        }
                                                    }
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $paymentName }}</span>
                                            </td>
                                            <td>{{ $transaction['name'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            لا توجد معاملات في هذه الوردية
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- الموظفين في الوردية -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">الموظفين في الوردية</h5>
                </div>
                <div class="card-body">
                    @if($shift->users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
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
                        </div>
                    @else
                        <div class="alert alert-info">
                            لا يوجد موظفين مسجلين في هذه الوردية
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // إضافة أي سكريبتات هنا عند الحاجة
    });
</script>
@endsection 