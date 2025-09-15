@extends('layouts.app')

@section('title', 'تحليل أرباح المشتريات')

@section('content')
<div class="container-fluid py-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>تحليل أرباح المشتريات</h5>
                   
                    </div>
                </div>
                
                <div class="card-body py-2">
                    <!-- Filter section -->
                    <form method="GET" action="{{ route('reports.purchases-profit-analytics') }}" id="purchases-report-form" class="mb-3">
                        @csrf
                        <input type="hidden" name="generate_report" value="1">
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية البيانات</h5>
                            </div>
                            <div class="card-body py-3 bg-light">
                                <div class="row g-3">
                                    <!-- Period Report Fields - Start -->
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">من تاريخ</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date', date('Y-01-01')) }}">
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">إلى تاريخ</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">المورد</label>
                                        <select class="form-select" name="supplier_id">
                                            <option value="">كل الموردين</option>
                                            @if(isset($suppliers))
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">الفئة</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">كل الفئات</option>
                                            @if(isset($categories))
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">ترتيب المنتجات</label>
                                        <select class="form-select" name="products_order">
                                            <option value="purchase_total" {{ request('products_order', 'purchase_total') == 'purchase_total' ? 'selected' : '' }}>حسب قيمة المشتريات</option>
                                            <option value="profit" {{ request('products_order') == 'profit' ? 'selected' : '' }}>حسب الربح</option>
                                            <option value="quantity" {{ request('products_order') == 'quantity' ? 'selected' : '' }}>حسب الكمية</option>
                                        </select>
                                    </div>
                                    <input type="hidden" name="products_limit" value="all">
                                    <!-- Filter Fields - End -->

                                    <div class="col-12 text-center mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-chart-bar me-1"></i>إنشاء التقرير
                                        </button>
                                        <a href="{{ route('reports.purchases-profit-analytics') }}" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-redo me-1"></i>إعادة تعيين
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if(isset($summary) && request()->has('generate_report'))
                        @if(isset($summary['purchase_count']) && $summary['purchase_count'] == 0)
                        <!-- No data for the specified criteria -->
                        <div class="alert alert-warning text-center my-3 p-3">
                            <h4><i class="fas fa-exclamation-triangle me-2"></i>لا توجد بيانات للفترة المحددة</h4>
                            <p class="mb-0">لم يتم العثور على أي مشتريات تطابق معايير البحث التي حددتها. يرجى تغيير معايير البحث أو اختيار فترة زمنية مختلفة.</p>
                        </div>
                        @else
                        <!-- Enhanced Summary Statistics with Extra KPIs -->
                        <div class="card mb-2">
                            <div class="card-header bg-gradient-primary text-white py-2">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ملخص المشتريات</h5>
                            </div>
                            <div class="card-body py-2">
                                <div class="row g-2">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="card bg-gradient-primary text-white h-100 shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title">إجمالي المشتريات</h6>
                                                        <h3 class="mb-0">{{ number_format($summary['purchase_total'] ?? 0, 2) }}</h3>
                                                    </div>
                                                    <div class="rounded-circle bg-white p-2">
                                                        <i class="fas fa-shopping-cart fa-lg text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="card bg-gradient-success text-white h-100 shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title">إجمالي المبيعات</h6>
                                                        <h3 class="mb-0">{{ number_format($summary['sales_total'] ?? 0, 2) }}</h3>
                                                    </div>
                                                    <div class="rounded-circle bg-white p-2">
                                                        <i class="fas fa-money-bill fa-lg text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="card bg-gradient-info text-white h-100 shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title">إجمالي الربح</h6>
                                                        <h3 class="mb-0">{{ number_format($summary['total_profit'] ?? 0, 2) }}</h3>
                                                    </div>
                                                    <div class="rounded-circle bg-white p-2">
                                                        <i class="fas fa-chart-line fa-lg text-info"></i>
                                                    </div>
                                                </div>
                                                <p class="mb-0 small"><strong>{{ number_format($summary['profit_margin'] ?? 0, 1) }}%</strong> نسبة الربح</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="card bg-gradient-warning text-white h-100 shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="card-title">عدد فواتير المشتريات</h6>
                                                        <h3 class="mb-0">{{ number_format($summary['purchase_count'] ?? 0) }}</h3>
                                                    </div>
                                                    <div class="rounded-circle bg-white p-2">
                                                        <i class="fas fa-file-invoice-dollar fa-lg text-warning"></i>
                                                    </div>
                                                </div>
                                                <p class="mb-0 small">
                                                    <strong>{{ isset($topProducts) && count($topProducts) > 0 ? count($topProducts) : 0 }}</strong> 
                                                    منتج مختلف
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products Table Section -->
                        @if(isset($topProducts) && count($topProducts) > 0)
                        <div class="card mb-2">
                            <div class="card-header bg-gradient-primary text-white py-2">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>المنتجات حسب أرباح المشتريات ({{ count($topProducts) }})</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>اسم المنتج</th>
                                                <th>الكمية المشتراة</th>
                                                <th>إجمالي المشتريات</th>
                                                <th>إجمالي المبيعات</th>
                                                <th>إجمالي الربح</th>
                                                <th>نسبة الربح</th>
                                                <th>نسبة المباع</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducts as $index => $product)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $product->name ?? 'منتج غير معروف' }}</strong></td>
                                                <td>{{ number_format($product->total_quantity ?? 0) }}</td>
                                                <td>{{ number_format($product->purchase_total ?? 0, 2) }}</td>
                                                <td>{{ number_format($product->sales_total ?? 0, 2) }}</td>
                                                <td>{{ number_format($product->total_profit ?? 0, 2) }}</td>
                                                <td>
                                                    @php
                                                        $profitMargin = $product->purchase_total > 0 ? ($product->total_profit / $product->purchase_total) * 100 : 0;
                                                    @endphp
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $profitMargin >= 50 ? 'success' : ($profitMargin >= 30 ? 'info' : 'warning') }}" 
                                                                 role="progressbar" 
                                                                 style="width: {{ min(100, max(5, $profitMargin)) }}%"></div>
                                                        </div>
                                                        <span>{{ number_format($profitMargin, 1) }}%</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $soldPercentage = $product->total_quantity > 0 ? ($product->sold_quantity / $product->total_quantity) * 100 : 0;
                                                    @endphp
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $soldPercentage >= 80 ? 'success' : ($soldPercentage >= 50 ? 'info' : 'warning') }}" 
                                                                 role="progressbar" 
                                                                 style="width: {{ min(100, max(5, $soldPercentage)) }}%"></div>
                                                        </div>
                                                        <span>{{ number_format($soldPercentage, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-dark">
                                            <tr>
                                                <th colspan="2">الإجمالي</th>
                                                <th>{{ number_format($summary['total_quantity'] ?? 0) }}</th>
                                                <th>{{ number_format($summary['purchase_total'] ?? 0, 2) }}</th>
                                                <th>{{ number_format($summary['sales_total'] ?? 0, 2) }}</th>
                                                <th>{{ number_format($summary['total_profit'] ?? 0, 2) }}</th>
                                                <th>{{ number_format($summary['profit_margin'] ?? 0, 1) }}%</th>
                                                <th>-</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Supplier Performance Section -->
                        @if(isset($suppliers) && isset($supplierPerformance) && count($supplierPerformance) > 0)
                        <div class="card mb-2">
                            <div class="card-header bg-gradient-primary text-white py-2">
                                <h5 class="mb-0"><i class="fas fa-industry me-2"></i>أداء الموردين</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>اسم المورد</th>
                                                <th>عدد المنتجات</th>
                                                <th>إجمالي المشتريات</th>
                                                <th>إجمالي المبيعات</th>
                                                <th>إجمالي الربح</th>
                                                <th>نسبة الربح</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($supplierPerformance as $index => $supplier)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $supplier->name }}</strong></td>
                                                <td>{{ $supplier->products_count }}</td>
                                                <td>{{ number_format($supplier->purchase_total, 2) }}</td>
                                                <td>{{ number_format($supplier->sales_total, 2) }}</td>
                                                <td>{{ number_format($supplier->total_profit, 2) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                                 style="width: {{ min(100, max(5, $supplier->profit_margin)) }}%"></div>
                                                        </div>
                                                        <span>{{ number_format($supplier->profit_margin, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endif
                    @else
                    <!-- Initial state when no report has been generated yet -->
                    <div class="alert alert-info text-center my-3 p-3">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <h4>مرحباً بك في تقرير تحليل أرباح المشتريات</h4>
                        <p class="mb-2">هذا التقرير يساعدك على فهم أداء مشترياتك وتتبع الأرباح المحققة منها.</p>
                        <p>يرجى تحديد الفترة الزمنية والمعايير المطلوبة من النموذج أعلاه ثم الضغط على زر "إنشاء التقرير" لعرض البيانات.</p>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Print report
        const printButton = document.getElementById('print-report');
        if (printButton) {
            printButton.addEventListener('click', function() {
                window.print();
            });
        }

        // Export to Excel
        const exportButton = document.getElementById('export-excel');
        if (exportButton) {
            exportButton.addEventListener('click', function() {
                const form = document.getElementById('purchases-report-form');
                if (!form) return;
                
                const formData = new FormData(form);
                formData.append('export', 'excel');
                
                // Create a new form for export
                const exportForm = document.createElement('form');
                exportForm.method = 'GET';
                exportForm.action = form.action + '/export';
                
                // Add all form data as hidden inputs
                for (const [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    exportForm.appendChild(input);
                }
                
                document.body.appendChild(exportForm);
                exportForm.submit();
                document.body.removeChild(exportForm);
            });
        }

        // Submit button loading state
        const submitButton = document.querySelector('#purchases-report-form button[type="submit"]');
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري إنشاء التقرير...';
                this.classList.add('disabled');
                return true;
            });
        }
    });
</script>

<style>
/* Better print styling */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 0.5rem !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .bg-gradient-primary, .bg-gradient-success, .bg-gradient-info, .bg-gradient-warning {
        color: #000 !important;
        background: #f5f5f5 !important;
    }
    
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    .table th, .table td {
        border: 1px solid #ddd !important;
    }
    
    .table-dark {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
}

/* Custom gradient backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important;
}
</style>
@endsection 