@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('تقرير مرتجعات المشتريات') }}</h5>
            <div>
                <a href="{{ route('purchase-returns.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> {{ __('إضافة مرتجع جديد') }}
                </a>
                <button class="btn btn-info" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> {{ __('طباعة التقرير') }}
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- بداية قسم الفلاتر -->
            <div class="mb-4 filter-section">
                <form action="{{ route('purchase-returns.report') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('المورد') }}</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">{{ __('الكل') }}</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('من تاريخ') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('إلى تاريخ') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('الوردية') }}</label>
                        <select name="shift_id" class="form-select">
                            <option value="">{{ __('الكل') }}</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>
                                    {{ $shift->shift_number }} ({{ $shift->start_time->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> {{ __('عرض النتائج') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- نهاية قسم الفلاتر -->
            
            <!-- بداية ملخص التقرير -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">{{ __('إجمالي عدد المرتجعات') }}</h5>
                            <h3>{{ $totalCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">{{ __('إجمالي قيمة المرتجعات') }}</h5>
                            <h3>{{ number_format($totalAmount, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">{{ __('متوسط قيمة المرتجع') }}</h5>
                            <h3>{{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- نهاية ملخص التقرير -->
            
            <!-- جدول المرتجعات -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('رقم المرتجع') }}</th>
                            <th>{{ __('التاريخ') }}</th>
                            <th>{{ __('المورد') }}</th>
                            <th>{{ __('فاتورة المشتريات الأصلية') }}</th>
                            <th>{{ __('الوردية') }}</th>
                            <th>{{ __('المبلغ') }}</th>
                            <th>{{ __('النوع') }}</th>
                            <th>{{ __('العمليات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseReturns as $purchaseReturn)
                            <tr>
                                <td>{{ $purchaseReturn->id }}</td>
                                <td>{{ $purchaseReturn->return_number }}</td>
                                <td>{{ $purchaseReturn->return_date ? $purchaseReturn->return_date->format('Y-m-d') : '' }}</td>
                                <td>{{ $purchaseReturn->supplier->name }}</td>
                                <td>{{ $purchaseReturn->purchase ? $purchaseReturn->purchase->invoice_number : __('مرتجع مباشر') }}</td>
                                <td>{{ $purchaseReturn->shift ? $purchaseReturn->shift->shift_number : __('غير مرتبط بوردية') }}</td>
                                <td class="text-success fw-bold">{{ number_format($purchaseReturn->total_amount, 2) }}</td>
                                <td>
                                    @if($purchaseReturn->return_type == 'full')
                                        <span class="badge bg-danger">{{ __('مرتجع كامل') }}</span>
                                    @elseif($purchaseReturn->return_type == 'partial')
                                        <span class="badge bg-warning">{{ __('مرتجع جزئي') }}</span>
                                    @else
                                        <span class="badge bg-info">{{ __('مرتجع مباشر') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('purchase-returns.show', $purchaseReturn->id) }}" class="btn btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('purchase-returns.pdf', $purchaseReturn->id) }}" class="btn btn-secondary" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-exclamation-circle text-warning mb-2 fa-2x d-block"></i>
                                    {{ __('لا توجد مرتجعات مطابقة للفلاتر المحددة') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- التصفح -->
            <div class="d-flex justify-content-center mt-4">
                {{ $purchaseReturns->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, .filter-section, .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: white !important;
        color: black !important;
        border-bottom: 1px solid #ddd !important;
    }
    body {
        background-color: white !important;
    }
}
</style>
@endsection 