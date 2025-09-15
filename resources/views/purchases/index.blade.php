@extends('layouts.app')

@section('css')
<style>
    .status-badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: .75em;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 50rem; /* pill shape */
    }
    .status-completed {
        color: #198754;
        background-color: rgba(25, 135, 84, 0.1);
    }
    .status-pending {
        color: #ffc107;
        background-color: rgba(255, 193, 7, 0.1);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-receipt me-2 text-primary"></i>قائمة فواتير الشراء</h5>
                <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> إنشاء فاتورة جديدة
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Toolbar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="input-group input-group-sm" style="max-width: 300px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0" placeholder="بحث...">
                </div>
                <div class="btn-group" role="group">
                  
                    <a href="{{ route('purchases.expiry-check') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-exclamation-triangle me-1"></i> تنبيهات الصلاحية
                    </a>
                </div>
            </div>

            <!-- Purchases Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">#</th>
                            <th style="width: 15%;">رقم الفاتورة</th>
                            <th style="width: 20%;">المورد</th>
                            <th class="text-center" style="width: 10%;">التاريخ</th>
                            <th class="text-end" style="width: 10%;">الإجمالي</th>
                            <th class="text-end" style="width: 10%;">المدفوع</th>
                            <th class="text-end" style="width: 10%;">المتبقي</th>
                            <th class="text-center" style="width: 10%;">الحالة</th>
                            <th class="text-center" style="width: 10%;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                            <td><a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-decoration-none">{{ $purchase->invoice_number }}</a></td>
                            <td>{{ $purchase->supplier->name ?? 'غير محدد' }}</td>
                            <td class="text-center">{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ number_format($purchase->total_amount, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($purchase->paid_amount, 2) }}</td>
                            <td class="text-end fw-bold {{ $purchase->remaining_amount > 0 ? 'text-danger' : '' }}">{{ number_format($purchase->remaining_amount, 2) }}</td>
                            <td class="text-center">
                                @if($purchase->remaining_amount <= 0)
                                    <span class="status-badge status-completed">مكتمل</span>
                                @else
                                    <span class="status-badge status-pending">غير مكتمل</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" title="عرض"><i class="fas fa-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted"></i>
                                <h5 class="mt-3">لا توجد فواتير شراء</h5>
                                <p class="text-muted">ابدأ بإضافة فاتورة شراء جديدة لعرضها هنا.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($purchases->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $purchases->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                const rows = document.querySelectorAll('table tbody tr');
                rows.forEach(function(row) {
                    if (row.querySelector('td[colspan]')) {
                        return; // Don't filter the "empty" row
                    }
                    row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush 