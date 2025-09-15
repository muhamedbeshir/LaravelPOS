@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-warehouse text-primary fa-2x me-2"></i>
                <h2 class="mb-0">إدارة المخزون</h2>
            </div>
            
            <div class="row">
                <!-- Inventory Summary Cards -->
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">إجمالي المنتجات</h6>
                                    <h3 class="mb-0 fw-bold">{{ \App\Models\Product::count() }}</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-box fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">المنتجات منخفضة المخزون</h6>
                                    <h3 class="mb-0 fw-bold text-warning">{{ count($lowStockProducts) }}</h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">المنتجات نفذت من المخزون</h6>
                                    <h3 class="mb-0 fw-bold text-danger">{{ \App\Models\Product::where('stock_quantity', '<=', 0)->count() }}</h3>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-ban fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">حركات المخزون اليوم</h6>
                                    <h3 class="mb-0 fw-bold text-success">{{ \App\Models\StockMovement::whereDate('created_at', date('Y-m-d'))->count() }}</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-exchange-alt fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- المنتجات منخفضة المخزون -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-2 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            المنتجات منخفضة المخزون
                        </h5>
                        <span class="badge bg-danger">{{ count($lowStockProducts) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">المنتج</th>
                                    <th>المخزون الحالي</th>
                                    <th>الحد الأدنى</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts as $product)
                                <tr class="{{ $product->stock_quantity <= 0 ? 'table-danger' : 'table-warning' }}">
                                    <td class="ps-3">{{ $product->name }}</td>
                                    <td>{{ number_format($product->stock_quantity, 2) }}</td>
                                    <td>{{ number_format($product->alert_quantity, 2) }}</td>
                                    <td>
                                        @if($product->stock_quantity <= 0)
                                            <span class="badge bg-danger">نفذ المخزون</span>
                                        @else
                                            <span class="badge bg-warning">منخفض</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                        <p class="text-muted mb-0">لا توجد منتجات منخفضة المخزون</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- حركات المخزون الأخيرة -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-2 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-history text-info me-2"></i>
                            حركات المخزون الأخيرة
                        </h5>
                        <span class="badge bg-info">{{ count($recentMovements) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">المنتج</th>
                                    <th>نوع الحركة</th>
                                    <th>الكمية</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $movement)
                                <tr>
                                    <td class="ps-3">{{ $movement->product ? $movement->product->name : 'منتج محذوف' }}</td>
                                    <td>
                                        @if($movement->movement_type == 'in')
                                            <span class="badge bg-success">وارد</span>
                                        @else
                                            <span class="badge bg-danger">منصرف</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($movement->quantity, 2) }}
                                        {{ $movement->unit_name }}
                                    </td>
                                    <td>
                                        {{ $movement->created_at->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-exchange-alt text-muted fa-2x mb-2"></i>
                                        <p class="text-muted mb-0">لا توجد حركات مخزون حديثة</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المخزون حسب المجموعات -->
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="fas fa-layer-group text-primary me-2"></i>
                            المخزون حسب المجموعات
                        </h5>
                        <span class="badge bg-primary">{{ count($categories) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($categories as $category)
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0 h-100 transition-hover">
                                <div class="card-body">
                                    <h6 class="card-title d-flex justify-content-between align-items-center mb-3">
                                        <span>{{ $category->name }}</span>
                                        <span class="badge bg-primary">{{ $category->products->count() }}</span>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>المنتج</th>
                                                    <th>المخزون</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($category->products as $product)
                                                <tr>
                                                    <td>{{ $product->name }}</td>
                                                    <td class="{{ $product->stock_quantity <= 0 ? 'text-danger' : '' }}">
                                                        {{ number_format($product->stock_quantity, 2) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #2563eb, #3b82f6);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #059669, #10b981);
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

.btn-light {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: #1f2937;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-light:hover {
    background: #ffffff;
    transform: translateY(-2px);
}

.table th, .table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}
</style>
@endpush
@endsection 