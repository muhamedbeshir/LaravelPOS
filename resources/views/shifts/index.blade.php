@extends('layouts.app')

@section('content')
<style>
/* Remove spinner arrows from number inputs */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

/* Improved number formatting and table styling */
.shifts-table th,
.shifts-table td {
    white-space: nowrap;
    padding: 12px 8px;
    vertical-align: middle;
}

.shifts-table .text-end {
    text-align: end !important;
}

.shifts-table .number-cell {
    font-family: 'Courier New', monospace;
    font-weight: 500;
    text-align: end;
    direction: ltr;
}

.shifts-table .currency::before {
    content: '';
    margin-left: 4px;
}

.shifts-table .currency::after {
    content: ' ج.م';
    font-size: 0.85em;
    color: #6c757d;
}

/* Status badges */
.status-badge {
    font-size: 0.8rem;
    padding: 4px 8px;
}

/* Compact column layout */
.shifts-table .compact-cell {
    font-size: 0.85rem;
    line-height: 1.3;
}

/* Modal number inputs */
.modal .form-control[type=number] {
    text-align: end;
    direction: ltr;
    font-family: 'Courier New', monospace;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .shifts-table th,
    .shifts-table td {
        padding: 8px 4px;
        font-size: 0.85rem;
    }
}
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ __('إدارة الورديات') }}</h2>
        <div>
            @php
                $currentOpenShift = \App\Models\Shift::getCurrentOpenShift();
            @endphp
            
            @if($currentOpenShift)
                <a href="{{ route('shifts.show', $currentOpenShift) }}" class="btn btn-info me-2">
                    <i class="fas fa-cash-register me-1"></i> {{ __('الوردية الحالية') }}
                </a>
            @else
                <a href="{{ route('shifts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> {{ __('فتح وردية جديدة') }}
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('سجل الورديات') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 shifts-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('رقم الوردية') }}</th>
                            <th>{{ __('الكاشير') }}</th>
                            <th>{{ __('وقت البدء') }}</th>
                            <th>{{ __('وقت الإغلاق') }}</th>
                            <th class="text-center">{{ __('المبلغ الابتدائي') }}</th>
                            <th class="text-center">{{ __('المبيعات') }}</th>
                            <th class="compact-cell text-center">{{ __('كاش/آجل') }}</th>
                            <th class="compact-cell text-center">{{ __('دليفري/تيك أواي') }}</th>
                            <th class="number-cell currency">{{ __('فيزا') }}</th>
                            <th class="number-cell currency">{{ __('تحويلات مالية') }}</th>
                            <th class="number-cell currency text-danger">{{ __('المرتجعات') }}</th>
                            <th class="text-center">{{ __('المصروفات') }}</th>
                            <th class="text-center">{{ __('الإيداعات') }}</th>
                            <th class="text-center">{{ __('الرصيد المتوقع في الدرج (نقدي)') }}</th>
                            <th>{{ __('الحالة') }}</th>
                            <th>{{ __('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        @php
                            $cashSales = $shift->invoices()->where('type', 'cash')->sum('total');
                            $creditSales = $shift->invoices()->where('type', 'credit')->sum('total');
                            $visaSales = $shift->invoices()->where('type', 'visa')->sum('total');
                            $transferSales = $shift->invoices()->where('type', 'transfer')->sum('total');
                            $deliverySales = $shift->invoices()->where('order_type', 'delivery')->sum('total');
                            $takeawaySales = $shift->invoices()->where('order_type', 'takeaway')->sum('total');
                            $returns = $shift->returns_amount ?? $shift->current_returns_total ?? 0;
                            $expenses = $shift->current_expenses_total ?? 0;
                            $deposits = $shift->current_deposits_total ?? 0;
                            
                            // Calculate expected drawer amount
                            $expectedDrawer = $shift->opening_balance + $cashSales + $deposits - $expenses - $returns;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $shift->shift_number }}</td>
                            <td>{{ $shift->mainCashier->name }}</td>
                            <td>{{ $shift->start_time ? $shift->start_time->format('Y-m-d H:i') : '-' }}</td>
                            <td>{{ $shift->end_time ? $shift->end_time->format('Y-m-d H:i') : '-' }}</td>
                            <td class="number-cell currency">{{ number_format($shift->opening_balance, 2) }}</td>
                            <td class="number-cell currency">{{ number_format($cashSales + $creditSales, 2) }}</td>
                            <td class="compact-cell text-center">
                                <small class="d-block text-success">{{ __('كاش') }}: <span class="number-cell">{{ number_format($cashSales, 2) }}</span></small>
                                <small class="d-block text-warning">{{ __('آجل') }}: <span class="number-cell">{{ number_format($creditSales, 2) }}</span></small>
                            </td>
                            <td class="compact-cell text-center">
                                <small class="d-block text-info">{{ __('دليفري') }}: <span class="number-cell">{{ number_format($deliverySales, 2) }}</span></small>
                                <small class="d-block text-secondary">{{ __('تيك أواي') }}: <span class="number-cell">{{ number_format($takeawaySales, 2) }}</span></small>
                            </td>
                            <td class="number-cell currency">{{ number_format($visaSales, 2) }}</td>
                            <td class="number-cell currency">{{ number_format($transferSales, 2) }}</td>
                            <td class="number-cell currency text-danger">{{ number_format($returns, 2) }}</td>
                            <td class="number-cell currency">{{ number_format($expenses, 2) }}</td>
                            <td class="number-cell currency">{{ number_format($deposits, 2) }}</td>
                            <td class="number-cell currency fw-bold text-primary">{{ number_format($expectedDrawer, 2) }}</td>
                            <td>
                                @if($shift->is_closed)
                                    <span class="badge bg-danger status-badge">{{ __('مغلقة') }}</span>
                                @else
                                    <span class="badge bg-success status-badge">{{ __('مفتوحة') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('shifts.show', $shift) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if(!$shift->is_closed)
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#closeShiftModal{{ $shift->id }}">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    
                                    <!-- Modal for closing shift -->
                                    <div class="modal fade" id="closeShiftModal{{ $shift->id }}" tabindex="-1" aria-labelledby="closeShiftModalLabel{{ $shift->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="closeShiftModalLabel{{ $shift->id }}">{{ __('إغلاق الوردية') }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('shifts.close', $shift) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <p class="mb-1">{{ __('سيتم إغلاق الوردية رقم') }}: <strong>{{ $shift->shift_number }}</strong></p>
                                                            <p class="mb-0">{{ __('المبلغ المتوقع في الدرج الآن:') }} <strong class="number-cell">{{ number_format($expectedDrawer, 2) }} ج.م</strong></p>
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label class="form-label">{{ __('المبلغ الفعلي في الدرج') }} <span class="text-danger">*</span></label>
                                                            <input type="number" name="actual_closing_balance" step="0.01" min="0" class="form-control" required>
                                                        </div>
                                                        
                                                        <div class="form-group mb-3">
                                                            <label class="form-label">{{ __('ملاحظات الإغلاق') }}</label>
                                                            <textarea name="closing_notes" class="form-control" rows="3"></textarea>
                                                        </div>
                                                        
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="print_inventory" id="print_inventory_{{ $shift->id }}" value="1">
                                                            <label class="form-check-label" for="print_inventory_{{ $shift->id }}">
                                                                {{ __('طباعة تقرير جرد الأصناف المباعة') }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                                                        <button type="submit" class="btn btn-danger">{{ __('إغلاق الوردية') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <a href="{{ route('shifts.print', $shift) }}" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="15" class="text-center">{{ __('لا توجد ورديات') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $shifts->links() }}
        </div>
    </div>
</div>
@endsection 