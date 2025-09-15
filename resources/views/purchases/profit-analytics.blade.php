@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تحليل الربحية</h3>
                </div>
                <div class="card-body">
                    <!-- إحصائيات عامة -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">إجمالي الربح المتوقع</span>
                                    <span class="info-box-number total-profit">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">متوسط نسبة الربح</span>
                                    <span class="info-box-number avg-profit-percentage">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- رسم بياني للربحية -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">المنتجات الأكثر ربحية</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="profitChart" style="height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">أعلى 10 منتجات ربحية</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>المنتج</th>
                                                    <th>الربح</th>
                                                    <th>النسبة</th>
                                                </tr>
                                            </thead>
                                            <tbody id="top-products">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('/assets/chart.js') }}"></script>
<script>
    $(document).ready(function() {
        // جلب البيانات من الخادم
        $.get('{{ route("purchases.profit-analytics") }}', function(data) {
            updateProfitAnalytics(data);
        });

        function updateProfitAnalytics(data) {
            // تحديث الإحصائيات العامة
            let totalProfit = data.reduce((sum, item) => sum + item.total_profit, 0);
            let avgPercentage = data.reduce((sum, item) => sum + item.avg_profit_percentage, 0) / data.length;

            $('.total-profit').text(totalProfit.toFixed(2));
            $('.avg-profit-percentage').text(avgPercentage.toFixed(2) + '%');

            // تحديث جدول أعلى المنتجات
            let tbody = $('#top-products');
            tbody.empty();
            data.forEach(item => {
                tbody.append(`
                    <tr>
                        <td>${item.product.name}</td>
                        <td>${item.total_profit.toFixed(2)}</td>
                        <td>${item.avg_profit_percentage.toFixed(2)}%</td>
                    </tr>
                `);
            });

            // إنشاء الرسم البياني
            let ctx = document.getElementById('profitChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.product.name),
                    datasets: [{
                        label: 'الربح المتوقع',
                        data: data.map(item => item.total_profit),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>
@endpush 