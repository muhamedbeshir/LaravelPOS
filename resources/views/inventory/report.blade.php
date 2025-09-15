@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-chart-bar text-primary fa-2x me-2"></i>
                <h2 class="mb-0">تقرير حركة المخزون</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية البيانات</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.report') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label fw-bold">من تاريخ</label>
                            <input type="date" class="form-control shadow-sm" id="start_date" name="start_date" 
                                   value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label fw-bold">إلى تاريخ</label>
                            <input type="date" class="form-control shadow-sm" id="end_date" name="end_date" 
                                   value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="product_id" class="form-label fw-bold">المنتج</label>
                            <select class="form-select shadow-sm" id="product_id" name="product_id">
                                <option value="">كل المنتجات</option>
                                @foreach($products as $product)
                                <option value="{{ $product->id }}" 
                                        {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="movement_type" class="form-label fw-bold">نوع الحركة</label>
                            <select class="form-select shadow-sm" id="movement_type" name="movement_type">
                                <option value="">كل الحركات</option>
                                <option value="in" {{ request('movement_type') == 'in' ? 'selected' : '' }}>
                                    وارد
                                </option>
                                <option value="out" {{ request('movement_type') == 'out' ? 'selected' : '' }}>
                                    صادر
                                </option>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-filter me-1"></i>
                                تصفية
                            </button>
                            <a href="{{ route('inventory.report') }}" class="btn btn-secondary px-4 py-2">
                                <i class="fas fa-redo me-1"></i>
                                إعادة ضبط
                            </a>
                            <a href="{{ route('inventory.export-report') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success px-4 py-2 ms-2">
                                <i class="fas fa-file-export me-1"></i>
                                تصدير CSV
                            </a>
                    
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- بطاقات إحصائية ملخصة -->
        <div class="col-md-12 mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-gradient-primary text-white transition-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">إجمالي الحركات</h6>
                                    <h3 class="mb-0">{{ $movements->total() }}</h3>
                                </div>
                                <div class="bg-white rounded-circle p-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-exchange-alt fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-gradient-success text-white transition-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">الوارد</h6>
                                    <h3 class="mb-0">{{ $movements->where('movement_type', 'in')->count() }}</h3>
                                </div>
                                <div class="bg-white rounded-circle p-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-arrow-circle-down fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-gradient-danger text-white transition-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">الصادر</h6>
                                    <h3 class="mb-0">{{ $movements->where('movement_type', 'out')->count() }}</h3>
                                </div>
                                <div class="bg-white rounded-circle p-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-arrow-circle-up fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-gradient-info text-white transition-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">المنتجات المتأثرة</h6>
                                    <h3 class="mb-0">{{ $movements->pluck('product_id')->unique()->count() }}</h3>
                                </div>
                                <div class="bg-white rounded-circle p-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-boxes fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>سجل حركة المخزون</h5>
                    <span class="badge bg-primary px-3 py-2">{{ $movements->total() }} حركة</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">التاريخ</th>
                                    <th>المنتج</th>
                                    <th>نوع الحركة</th>
                                    <th>الكمية</th>
                                    <th>قبل</th>
                                    <th>بعد</th>
                                    <th>المرجع</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                <tr>
                                    <td class="ps-3">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ optional($movement->product)->name ?? '—' }}</td>
                                    <td>
                                        @if($movement->movement_type == 'in')
                                            <span class="badge bg-success">وارد</span>
                                        @else
                                            <span class="badge bg-danger">صادر</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($movement->quantity, 2) }}
                                        {{ $movement->unit_name }}
                                    </td>
                                    <td>{{ number_format($movement->before_quantity, 2) }}</td>
                                    <td>{{ number_format($movement->after_quantity, 2) }}</td>
                                    <td>
                                        @php
                                            $purchaseClass = App\Models\Purchase::class;
                                            $invoiceClass  = App\Models\Invoice::class;
                                        @endphp
                                        @if($movement->reference_type === $purchaseClass && $movement->reference)
                                            <a href="{{ route('purchases.show', $movement->reference_id) }}" class="text-decoration-none">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-shopping-cart me-1"></i>
                                                    مشتريات #{{ $movement->reference->invoice_number ?? $movement->reference_id }}
                                                </span>
                                            </a>
                                        @elseif($movement->reference_type === $invoiceClass && $movement->reference)
                                            <a href="{{ route('sales.invoices.print', $movement->reference_id) }}" target="_blank" class="text-decoration-none">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-receipt me-1"></i>
                                                    مبيعات #{{ $movement->reference->invoice_number ?? $movement->reference_id }}
                                                </span>
                                            </a>
                                        @elseif($movement->reference_type == 'App\\Models\\StockAdjustment')
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-edit me-1"></i>
                                                تعديل مخزون
                                            </span>
                                        @elseif($movement->reference_type == 'stock_count')
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-clipboard-list me-1"></i>
                                                جرد مخزون
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $movement->notes }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-exclamation-circle text-muted fa-3x mb-3"></i>
                                        <p class="text-muted">لم يتم العثور على أي حركات للمخزون</p>
                                        <a href="{{ route('inventory.adjustment') }}" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-plus-circle me-1"></i> إضافة تعديل مخزون
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center p-3">
                        {{ $movements->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
html[dir="rtl"] .table th,
html[dir="rtl"] .table td {
    text-align: right;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #2563eb, #3b82f6);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #059669, #10b981);
}

.bg-gradient-danger {
    background: linear-gradient(45deg, #dc2626, #ef4444);
}

.bg-gradient-info {
    background: linear-gradient(45deg, #0284c7, #0ea5e9);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.transition-hover {
    transition: all 0.2s;
}

.transition-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.table th, .table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* طباعة التقرير */
@media print {
    .card-header, .btn, .pagination, .nav, .navbar, .sidebar, footer {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    body {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .col-md-12 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديث التاريخ تلقائيًا عند التغيير
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', () => {
            input.form.submit();
        });
    });

    // تحديث المنتج ونوع الحركة تلقائيًا عند التغيير
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', () => {
            select.form.submit();
        });
    });
});
</script>
@endpush
@endsection 