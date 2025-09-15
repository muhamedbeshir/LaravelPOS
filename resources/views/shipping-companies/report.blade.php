@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar text-primary"></i>
            تقرير شركات الشحن
        </h4>
        <a href="{{ route('shipping-companies.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> رجوع
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">إجمالي عدد الشركات</h5>
                    <h2 class="mb-0">{{ $companies->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">إجمالي عدد الشحنات</h5>
                    <h2 class="mb-0">{{ $totalDeliveries }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">إجمالي تكاليف الشحن</h5>
                    <h2 class="mb-0">{{ number_format($totalShippingCost, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">مقارنة شركات الشحن</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>اسم الشركة</th>
                            <th>عدد الشحنات</th>
                            <th>نسبة الشحنات</th>
                            <th>إجمالي تكاليف الشحن</th>
                            <th>متوسط تكلفة الشحنة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->delivery_transactions_count }}</td>
                            <td>
                                @if($totalDeliveries > 0)
                                    {{ number_format(($company->delivery_transactions_count / $totalDeliveries) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                            <td>{{ number_format($company->delivery_transactions_sum_shipping_cost ?? 0, 2) }}</td>
                            <td>
                                @if($company->delivery_transactions_count > 0)
                                    {{ number_format(($company->delivery_transactions_sum_shipping_cost ?? 0) / $company->delivery_transactions_count, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($company->is_active)
                                <span class="badge bg-success">نشط</span>
                                @else
                                <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('shipping-companies.show', $company) }}" class="btn btn-sm btn-info text-white" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">لا توجد شركات شحن</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">الرسم البياني لتوزيع الشحنات</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height:400px;">
                <canvas id="shipmentsChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('/assets/chart.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // بيانات الرسم البياني
        const ctx = document.getElementById('shipmentsChart').getContext('2d');
        
        const companies = @json($companies->pluck('name'));
        const deliveryCounts = @json($companies->pluck('delivery_transactions_count'));
        const shippingCosts = @json($companies->pluck('delivery_transactions_sum_shipping_cost'));
        
        // إنشاء الرسم البياني
        const shipmentsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: companies,
                datasets: [
                    {
                        label: 'عدد الشحنات',
                        data: deliveryCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'تكلفة الشحن',
                        data: shippingCosts,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'عدد الشحنات'
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'تكلفة الشحن'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush 