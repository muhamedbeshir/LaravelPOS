@extends('layouts.app')

@section('title', 'تقارير الورديات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">بحث تقارير الورديات</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.shifts.search') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_date">من تاريخ</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_date">إلى تاريخ</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                                <a href="{{ route('reports.shifts.index') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-redo"></i> إعادة تعيين
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">نتائج البحث</h5>
                </div>
                <div class="card-body">
                    @if($shifts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>رقم الوردية</th>
                                        <th>الكاشير الرئيسي</th>
                                        <th>تاريخ البدء</th>
                                        <th>تاريخ الإغلاق</th>
                                        <th>إجمالي المبيعات</th>
                                        <th>الرصيد المتوقع في الدرج (نقدي)</th>
                                        <th>الفرق</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shifts as $shift)
                                        @php
                                            if ($shift->is_closed) {
                                                // للورديات المغلقة، استخدم البيانات المحفوظة
                                                $expectedDrawer = $shift->expected_closing_balance;
                                                $difference = $shift->cash_shortage_excess;
                                            } else {
                                                // للورديات المفتوحة، احسب الرصيد الحالي
                                                $cashSales = $shift->invoices()->where('type', 'cash')->sum('total');
                                                $deposits = $shift->current_deposits_total ?? 0;
                                                $purchases = $shift->current_purchases_total ?? 0;
                                                $expenses = $shift->current_expenses_total ?? 0;
                                                $returns = $shift->returns_amount ?? $shift->current_returns_total ?? 0;
                                                
                                                // حساب الرصيد المتوقع في الدرج
                                                $expectedDrawer = $shift->opening_balance + $cashSales + $deposits - $purchases - $expenses - $returns;
                                                
                                                // حساب الفرق
                                                $difference = $shift->actual_closing_balance ? $shift->actual_closing_balance - $expectedDrawer : 0;
                                            }
                                            
                                            // إجمالي المبيعات (كل الأنواع)
                                            $totalSales = $shift->invoices()->sum('total');
                                        @endphp
                                        <tr>
                                            <td>{{ $shift->shift_number }}</td>
                                            <td>{{ $shift->mainCashier->name ?? 'غير محدد' }}</td>
                                            <td>{{ $shift->start_time ? $shift->start_time->format('Y-m-d H:i') : '-' }}</td>
                                            <td>{{ $shift->end_time ? $shift->end_time->format('Y-m-d H:i') : 'مفتوحة' }}</td>
                                            <td>{{ number_format($totalSales, 2) }}</td>
                                            <td>{{ number_format($expectedDrawer, 2) }}</td>
                                            <td>
                                                @if($shift->actual_closing_balance)
                                                    <span class="{{ $difference > 0 ? 'text-success' : ($difference < 0 ? 'text-danger' : '') }}">
                                                        {{ number_format($difference, 2) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('reports.shifts.show', $shift) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> عرض
                                                </a>
                                                <a href="{{ route('reports.shifts.print', $shift) }}" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="fas fa-print"></i> طباعة
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            لا توجد ورديات مطابقة لمعايير البحث
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
        // تنسيق التاريخ
        $('#start_date, #end_date').datepicker({
            format: 'yyyy-mm-dd',
            rtl: true,
            language: 'ar',
            autoclose: true
        });
    });
</script>
@endsection 