@extends('layouts.app')

@section('title', 'التقرير المالي الشامل')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">التقرير المالي الشامل</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.financial-summary') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">من تاريخ</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">إلى تاريخ</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نطاق زمني سريع</label>
                            <select class="form-select" id="quick-range">
                                <option value="">اختر...</option>
                                <option value="today">اليوم</option>
                                <option value="yesterday">أمس</option>
                                <option value="this_week">الأسبوع الحالي</option>
                                <option value="last_7">آخر 7 أيام</option>
                                <option value="this_month">الشهر الحالي</option>
                                <option value="last_month">الشهر الماضي</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> عرض التقرير
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ملخص البيانات -->
    <div class="row mb-4">
        <!-- إجمالي المبيعات -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body bg-success bg-opacity-10 text-center">
                    <h5 class="card-title">إجمالي المبيعات</h5>
                    <h2 class="mb-0">{{ number_format($salesData['total'], 2) }}</h2>
                    <p class="text-muted">عدد الفواتير: {{ number_format($salesData['count']) }}</p>
                </div>
            </div>
        </div>
        
        <!-- إجمالي المصروفات -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body bg-danger bg-opacity-10 text-center">
                    <h5 class="card-title">إجمالي المصروفات</h5>
                    <h2 class="mb-0">{{ number_format($profitData['total_expenses_with_salaries'], 2) }}</h2>
                    <p class="text-muted">
                        مصروفات عامة: {{ number_format($expensesData['total'], 2) }}
                        @if($salariesData['count_as_expenses'])
                            <br>رواتب: {{ number_format($salariesData['total'], 2) }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
        
        <!-- الربح قبل المصروفات -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body bg-info bg-opacity-10 text-center">
                    <h5 class="card-title">الربح قبل المصروفات</h5>
                    <h2 class="mb-0">{{ number_format($profitData['gross_profit'], 2) }}</h2>
                    <p class="text-muted">نسبة الربح: {{ number_format(($profitData['gross_profit'] / max($salesData['total'], 1)) * 100, 2) }}%</p>
                </div>
            </div>
        </div>
        
        <!-- صافي الربح -->
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body bg-primary bg-opacity-10 text-center">
                    <h5 class="card-title">صافي الربح</h5>
                    <h2 class="mb-0 {{ $profitData['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($profitData['net_profit'], 2) }}
                    </h2>
                    <p class="text-muted">نسبة الربح الصافي: {{ number_format($profitData['profit_margin'], 2) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- تفاصيل البيانات -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تفاصيل المبيعات والأرباح</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <tbody>
                                <tr>
                                    <th>إجمالي المبيعات</th>
                                    <td class="text-end">{{ number_format($salesData['total'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>عدد الفواتير</th>
                                    <td class="text-end">{{ number_format($salesData['count']) }}</td>
                                </tr>
                                <tr>
                                    <th>متوسط قيمة الفاتورة</th>
                                    <td class="text-end">{{ number_format($salesData['average'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>إجمالي الربح (قبل المصروفات)</th>
                                    <td class="text-end">{{ number_format($profitData['gross_profit'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>نسبة الربح من المبيعات</th>
                                    <td class="text-end">{{ number_format(($profitData['gross_profit'] / max($salesData['total'], 1)) * 100, 2) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">تفاصيل المصروفات والأرباح الصافية</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <tbody>
                                <tr>
                                    <th>المصروفات العامة</th>
                                    <td class="text-end">{{ number_format($expensesData['total'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>مصروفات الرواتب</th>
                                    <td class="text-end">
                                        @if($salariesData['count_as_expenses'])
                                            {{ number_format($salariesData['total'], 2) }}
                                        @else
                                            <span class="text-muted">غير محسوبة كمصروفات</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>إجمالي المصروفات</th>
                                    <td class="text-end">{{ number_format($profitData['total_expenses_with_salaries'], 2) }}</td>
                                </tr>
                                <tr>
                                    <th>صافي الربح (بعد المصروفات)</th>
                                    <td class="text-end {{ $profitData['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($profitData['net_profit'], 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>نسبة الربح الصافي</th>
                                    <td class="text-end {{ $profitData['profit_margin'] < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($profitData['profit_margin'], 2) }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="row mb-4">
        <!-- رسم بياني للمبيعات اليومية -->
        <div class="col-md-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">المبيعات والأرباح اليومية</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- رسم بياني للمصروفات حسب الفئة -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">المصروفات حسب الفئة</h5>
                </div>
                <div class="card-body">
                    <canvas id="expenseCategoriesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- زر الطباعة -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> طباعة التقرير
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const startInput = document.getElementById('start_date');
    const endInput   = document.getElementById('end_date');
    const quick      = document.getElementById('quick-range');
    if(!quick) return;
    const pad = n=>String(n).padStart(2,'0');
    const fmt = d=>`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    quick.addEventListener('change', function(){
        const val=this.value; if(!val) return;
        const today=new Date(); let s=new Date(); let e=new Date();
        switch(val){
            case 'today': break;
            case 'yesterday': s.setDate(today.getDate()-1); e.setDate(today.getDate()-1); break;
            case 'this_week': const diff=(today.getDay()||7)-1; s.setDate(today.getDate()-diff); break;
            case 'last_7': s.setDate(today.getDate()-6); break;
            case 'this_month': s=new Date(today.getFullYear(),today.getMonth(),1); break;
            case 'last_month': s=new Date(today.getFullYear(),today.getMonth()-1,1); e=new Date(today.getFullYear(),today.getMonth(),0); break;
        }
        startInput.value=fmt(s); endInput.value=fmt(e);
    });
})();
</script>
<script src="{{ asset('/assets/chart.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // رسم بياني للمبيعات اليومية
        const salesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: @json($dailySalesData['dates']),
                datasets: [
                    {
                        label: 'المبيعات',
                        data: @json($dailySalesData['sales']),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'الأرباح',
                        data: @json($dailySalesData['profits']),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // رسم بياني للمصروفات حسب الفئة
        const expenseCtx = document.getElementById('expenseCategoriesChart').getContext('2d');
        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: @json($expenseCategories['labels']),
                datasets: [{
                    data: @json($expenseCategories['data']),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)',
                        'rgba(83, 102, 255, 0.7)',
                        'rgba(40, 159, 64, 0.7)',
                        'rgba(210, 199, 199, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    @media print {
        .card {
            break-inside: avoid;
        }
        .no-print {
            display: none;
        }
        body {
            padding: 20px;
        }
    }
</style>
@endpush 