@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>تحليل تغييرات الأسعار</h5>
                    <div>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i> رجوع للمنتجات
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="product-select">اختر المنتج</label>
                                <select id="product-select" class="form-select">
                                    <option value="">كل المنتجات</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="price-type-select">نوع السعر</label>
                                <select id="price-type-select" class="form-select">
                                    <option value="سعر رئيسي">سعر رئيسي</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="time-range-select">النطاق الزمني</label>
                                <select id="time-range-select" class="form-select">
                                    <option value="30">آخر 30 يوم</option>
                                    <option value="60">آخر 60 يوم</option>
                                    <option value="90">آخر 90 يوم</option>
                                    <option value="180">آخر 6 أشهر</option>
                                    <option value="365">آخر سنة</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="analyze-btn" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> تحليل
                                </button>
                        </div>
                    </div>

                    <div id="loading-spinner" class="text-center my-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="mt-2">جاري تحليل البيانات...</p>
                    </div>

                    <div id="analytics-results" class="d-none">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">رسم بياني لتغييرات الأسعار</h5>
                                    </div>
                                <div class="card-body">
                                        <div style="height: 400px;">
                                            <canvas id="price-chart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">إحصائيات التغييرات</h5>
                                    </div>
                                <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                عدد التغييرات
                                                <span class="badge bg-primary rounded-pill" id="changes-count">0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                متوسط التغيير
                                                <span class="badge bg-info rounded-pill" id="average-change">0%</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                أعلى زيادة
                                                <span class="badge bg-success rounded-pill" id="highest-increase">0%</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                أعلى انخفاض
                                                <span class="badge bg-danger rounded-pill" id="highest-decrease">0%</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">توزيع التغييرات</h5>
                        </div>
                                <div class="card-body">
                                        <div style="height: 200px;">
                                            <canvas id="changes-distribution-chart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">ملخص التغييرات</h5>
                        </div>
                                <div class="card-body">
                                        <div id="changes-summary">
                                            <p class="text-muted text-center">اختر منتج لعرض ملخص التغييرات</p>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">سجل تغييرات الأسعار</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <thead>
                                        <tr>
                                            <th>المنتج</th>
                                            <th>الوحدة</th>
                                            <th>نوع السعر</th>
                                            <th>السعر القديم</th>
                                            <th>السعر الجديد</th>
                                            <th>نسبة التغيير</th>
                                                        <th>تاريخ التغيير</th>
                                        </tr>
                                    </thead>
                                                <tbody id="price-changes-table">
                                                    <!-- سيتم ملء هذا الجدول بواسطة JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="no-data-message" class="text-center my-5">
                        <i class="fas fa-chart-area fa-4x text-muted mb-3"></i>
                        <h4>لا توجد بيانات للعرض</h4>
                        <p class="text-muted">اختر المنتج والنطاق الزمني واضغط على زر التحليل لعرض البيانات</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('/assets/chart.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product-select');
    const priceTypeSelect = document.getElementById('price-type-select');
    const timeRangeSelect = document.getElementById('time-range-select');
    const analyzeBtn = document.getElementById('analyze-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    const analyticsResults = document.getElementById('analytics-results');
    const noDataMessage = document.getElementById('no-data-message');
    
    let priceChart = null;
    let distributionChart = null;

    analyzeBtn.addEventListener('click', function() {
        loadData();
    });

    function loadData() {
        // Show loading spinner
        loadingSpinner.classList.remove('d-none');
        analyticsResults.classList.add('d-none');
        noDataMessage.classList.add('d-none');

        // Prepare query parameters
        const params = new URLSearchParams({
            product_id: productSelect.value,
            price_type: priceTypeSelect.value,
            time_range: timeRangeSelect.value
        });

        // Fetch data from the server
        fetch(`{{ route('products.get-price-analytics-data') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                loadingSpinner.classList.add('d-none');

        if (data.error) {
                    alert('خطأ: ' + data.error);
                    noDataMessage.classList.remove('d-none');
            return;
        }

                // Check if there's data to display
                if (!data.changes || data.changes.length === 0) {
                    noDataMessage.classList.remove('d-none');
                    return;
                }

                // Display the results
                analyticsResults.classList.remove('d-none');
                renderPriceChart(data.historyData);
                renderStatistics(data.statistics);
                renderDistributionChart(data.changesDistribution);
                renderChangesSummary(data.changes);
                renderChangesTable(data.changes);
            })
            .catch(error => {
                loadingSpinner.classList.add('d-none');
                noDataMessage.classList.remove('d-none');
                console.error('Error fetching price analytics data:', error);
                alert('حدث خطأ أثناء تحميل البيانات');
            });
    }

    function renderPriceChart(historyData) {
        const ctx = document.getElementById('price-chart').getContext('2d');
        
        if (priceChart) {
            priceChart.destroy();
        }
        
        priceChart = new Chart(ctx, {
        type: 'line',
        data: {
                labels: historyData.dates,
                datasets: historyData.datasets
        },
        options: {
            responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                },
                plugins: {
                legend: {
                    position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                }
            }
        }
    });
}

    function renderStatistics(statistics) {
        document.getElementById('changes-count').textContent = statistics.changesCount;
        document.getElementById('average-change').textContent = `${statistics.averageChange.toFixed(2)}%`;
        document.getElementById('highest-increase').textContent = `${statistics.highestIncrease.toFixed(2)}%`;
        document.getElementById('highest-decrease').textContent = `${statistics.highestDecrease.toFixed(2)}%`;
    }

    function renderDistributionChart(changesDistribution) {
        const ctx = document.getElementById('changes-distribution-chart').getContext('2d');
        
        if (distributionChart) {
            distributionChart.destroy();
        }
        
        distributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['زيادة', 'انخفاض', 'ثابت'],
            datasets: [{
                    data: [
                        changesDistribution.increases,
                        changesDistribution.decreases,
                        changesDistribution.unchanged
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#6c757d'
                    ]
            }]
        },
        options: {
            responsive: true,
                maintainAspectRatio: false,
            plugins: {
                legend: {
                        position: 'bottom'
                }
            }
        }
    });
}

    function renderChangesSummary(changes) {
        const summaryElement = document.getElementById('changes-summary');
        
        if (changes.length === 0) {
            summaryElement.innerHTML = '<p class="text-muted text-center">لا توجد تغييرات لعرضها</p>';
            return;
        }
        
        let firstPrice = null;
        let lastPrice = null;
        let totalChangePercentage = 0;
        
        if (changes.length >= 2) {
            // Get the oldest and newest changes
            const sortedChanges = [...changes].sort((a, b) => 
                new Date(a.created_at) - new Date(b.created_at)
            );
            
            firstPrice = sortedChanges[0].old_price;
            lastPrice = sortedChanges[sortedChanges.length - 1].new_price;
            
            totalChangePercentage = ((lastPrice - firstPrice) / firstPrice) * 100;
        } else {
            // Only one change
            firstPrice = changes[0].old_price;
            lastPrice = changes[0].new_price;
            totalChangePercentage = changes[0].change_percentage;
        }
        
        const changeDirection = totalChangePercentage > 0 ? 'زيادة' : totalChangePercentage < 0 ? 'انخفاض' : 'ثابت';
        const changeClass = totalChangePercentage > 0 ? 'text-success' : totalChangePercentage < 0 ? 'text-danger' : 'text-secondary';
        
                    // Ensure firstPrice and lastPrice are numbers
            const formattedFirstPrice = typeof firstPrice === 'number' ? firstPrice.toFixed(2) : '0.00';
            const formattedLastPrice = typeof lastPrice === 'number' ? lastPrice.toFixed(2) : '0.00';
            
            summaryElement.innerHTML = `
            <div class="text-center">
                <h2 class="mb-3 ${changeClass}">${Math.abs(totalChangePercentage).toFixed(2)}%</h2>
                <p>اتجاه التغيير: <strong>${changeDirection}</strong></p>
                <p>السعر المبدئي: <strong>${formattedFirstPrice}</strong></p>
                <p>السعر النهائي: <strong>${formattedLastPrice}</strong></p>
            </div>
        `;
    }

    function renderChangesTable(changes) {
        const tableBody = document.getElementById('price-changes-table');
        tableBody.innerHTML = '';
        
        changes.forEach(change => {
            const row = document.createElement('tr');
            
            // Ensure all price values are properly formatted as numbers
            const oldPrice = typeof change.old_price === 'number' ? change.old_price.toFixed(2) : '0.00';
            const newPrice = typeof change.new_price === 'number' ? change.new_price.toFixed(2) : '0.00';
            const changePercentage = typeof change.change_percentage === 'number' ? change.change_percentage.toFixed(2) : '0.00';
            
            const changePercentageClass = change.change_percentage > 0 
                ? 'text-success' 
                : change.change_percentage < 0 
                    ? 'text-danger' 
                    : '';
            
            const changePercentageSymbol = change.change_percentage > 0 
                ? '+' 
                : change.change_percentage < 0 
                    ? '' 
                    : '';
            
            row.innerHTML = `
                <td>${change.product_name}</td>
                <td>${change.unit_name}</td>
                <td>${change.price_type}</td>
                <td>${oldPrice}</td>
                <td>${newPrice}</td>
                <td class="${changePercentageClass}">${changePercentageSymbol}${changePercentage}%</td>
                <td>${new Date(change.created_at).toLocaleString('ar-EG')}</td>
            `;
            
            tableBody.appendChild(row);
        });
    }
});
</script>
@endpush