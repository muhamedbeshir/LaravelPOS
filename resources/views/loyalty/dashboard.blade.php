@extends('layouts.app')

@section('title', 'لوحة تحكم نقاط الولاء')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">🎯 لوحة تحكم نقاط الولاء</h1>
                    <p class="text-muted mb-0">إدارة ومتابعة نظام نقاط الولاء</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('loyalty.settings') }}" class="btn btn-outline-primary">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                    <a href="{{ route('loyalty.customers') }}" class="btn btn-outline-info">
                        <i class="fas fa-users"></i> العملاء
                    </a>
                    <a href="{{ route('loyalty.transactions') }}" class="btn btn-outline-warning">
                        <i class="fas fa-history"></i> المعاملات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status Alert -->
    @if(!$settings->is_active)
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle me-3"></i>
        <div>
            <strong>تنبيه:</strong> نظام نقاط الولاء غير مفعل حالياً. 
            <a href="{{ route('loyalty.settings') }}" class="alert-link">تفعيل النظام</a>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">إجمالي النقاط المُمنوحة</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_points_awarded']) }}</h3>
                            <small class="opacity-75">نقطة</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-award"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">إجمالي النقاط المُستبدلة</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_points_redeemed']) }}</h3>
                            <small class="opacity-75">نقطة</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">العملاء النشطون</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_customers_with_points']) }}</h3>
                            <small class="opacity-75">عميل</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">قيمة النقاط المُستبدلة</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_amount_redeemed'], 2) }}</h3>
                            <small class="opacity-75">جنيه</small>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        تطور النقاط خلال الـ 6 أشهر الماضية
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="pointsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog text-secondary me-2"></i>
                        إعدادات النظام الحالية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">طريقة كسب النقاط:</span>
                            <span class="badge bg-primary">{{ $settings->earning_method_label }}</span>
                        </div>
                        
                        @if($settings->earning_method === 'per_invoice')
                            <small class="text-muted">{{ $settings->points_per_invoice }} نقطة لكل فاتورة</small>
                        @elseif($settings->earning_method === 'per_amount')
                            <small class="text-muted">{{ $settings->points_per_amount }} نقطة لكل جنيه</small>
                        @else
                            <small class="text-muted">{{ $settings->points_per_product }} نقطة لكل منتج</small>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">معدل التحويل:</span>
                            <span class="fw-bold">{{ $settings->points_to_currency_rate }} نقطة = 1 جنيه</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">الحد الأدنى للاستبدال:</span>
                            <span class="fw-bold">{{ $settings->min_points_for_redemption }} نقطة</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">السماح بخصم 100%:</span>
                            <span class="badge {{ $settings->allow_full_discount ? 'bg-success' : 'bg-danger' }}">
                                {{ $settings->allow_full_discount ? 'مُفعل' : 'غير مُفعل' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('loyalty.settings') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> تعديل الإعدادات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions and Top Customers -->
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history text-warning me-2"></i>
                        أحدث المعاملات
                    </h5>
                    <a href="{{ route('loyalty.transactions') }}" class="btn btn-outline-primary btn-sm">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentTransactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>العميل</th>
                                        <th>النوع</th>
                                        <th>النقاط</th>
                                        <th>القيمة</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <div class="avatar-initial bg-label-primary rounded-circle">
                                                        {{ substr($transaction->customer->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <span>{{ $transaction->customer->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $transaction->type === 'earned' ? 'success' : 'warning' }}">
                                                {{ $transaction->type_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold {{ $transaction->type === 'earned' ? 'text-success' : 'text-warning' }}">
                                                {{ $transaction->type === 'earned' ? '+' : '-' }}{{ number_format($transaction->points) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->amount)
                                                {{ number_format($transaction->amount, 2) }} جنيه
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $transaction->created_at->diffForHumans() }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted">لا توجد معاملات حتى الآن</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        أفضل العملاء
                    </h5>
                    <a href="{{ route('loyalty.customers') }}" class="btn btn-outline-primary btn-sm">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($topCustomers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($topCustomers as $index => $customer)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        @if($index < 3)
                                            <i class="fas fa-medal text-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'dark') }}"></i>
                                        @else
                                            <span class="badge bg-light text-dark">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $customer->name }}</h6>
                                        <small class="text-muted">{{ $customer->phone }}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-primary">{{ number_format($customer->total_loyalty_points) }}</span>
                                    <br>
                                    <small class="text-muted">نقطة</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted">لا يوجد عملاء بنقاط حتى الآن</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('/assets/chart.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Statistics Chart
    const ctx = document.getElementById('pointsChart').getContext('2d');
    
    const monthlyData = @json($monthlyStats);
    
    // Prepare chart data
    const labels = monthlyData.map(item => {
        const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                       'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        return months[item.month - 1] + ' ' + item.year;
    });
    
    const pointsEarned = monthlyData.map(item => item.points_earned || 0);
    const pointsRedeemed = monthlyData.map(item => item.points_redeemed || 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'النقاط المُمنوحة',
                data: pointsEarned,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'النقاط المُستبدلة',
                data: pointsRedeemed,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'الشهر'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'عدد النقاط'
                    },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            }
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.avatar {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
}

.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-initial {
    color: #fff;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-label-primary {
    background-color: rgba(105, 108, 255, 0.16);
    color: #696cff;
}

.card {
    transition: transform 0.15s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.fs-1 {
    font-size: 4rem !important;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.opacity-75 {
    opacity: 0.75 !important;
}
</style>
@endpush
@endsection 