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

/* Enhanced page background */
body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

/* Enhanced number formatting */
.number-display {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    direction: ltr;
    text-align: end;
    display: inline-block;
    min-width: 80px;
    font-size: 1.05rem;
}

.currency-symbol::after {
    content: ' ج.م';
    font-size: 0.85em;
    color: #6c757d;
    margin-right: 4px;
}

/* Card enhancements */
.card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: none;
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-bottom: 2px solid #e9ecef;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #495057;
    display: flex;
    align-items: center;
}

.card-header h5::before {
    content: '';
    width: 4px;
    height: 20px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 2px;
    margin-left: 10px;
}

/* Summary cards styling */
.summary-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #e3e6f0;
}

.summary-card .table td {
    padding: 12px 15px;
    border: none;
    font-size: 0.95rem;
}

.summary-card .table th {
    padding: 12px 15px;
    border: none;
    background: transparent;
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.9rem;
}

.summary-card .table tr:nth-child(even) {
    background-color: rgba(0, 123, 255, 0.03);
}

.summary-card .table tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
}

/* Transaction table improvements */
.transactions-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.transactions-table thead {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    color: white;
}

.transactions-table th {
    border: none;
    padding: 15px 12px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.transactions-table td {
    vertical-align: middle;
    padding: 12px;
    border-bottom: 1px solid #f1f3f4;
    transition: background-color 0.2s ease;
}

.transactions-table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: scale(1.005);
    transition: all 0.2s ease;
}

.transactions-table .amount-cell {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    text-align: end;
    direction: ltr;
    min-width: 120px;
    font-size: 1.05rem;
    padding: 12px 15px;
}

/* Enhanced badges */
.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Modal styling */
.modal-content {
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 1.5rem;
}

.modal-header .btn-close {
    filter: invert(1);
}

.modal .form-control[type=number] {
    text-align: end;
    direction: ltr;
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 12px 15px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.modal .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Status badges */
.status-indicator {
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Enhanced buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 10px 20px;
    transition: all 0.2s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: #212529;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
}

/* Balance summary styling */
.balance-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
}

.balance-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
    transition: background-color 0.2s ease;
}

.balance-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 6px;
    padding: 10px 8px;
}

.balance-row:last-child {
    border-bottom: none;
    font-weight: bold;
    background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 123, 255, 0.05) 100%);
    margin: 15px -20px -20px;
    padding: 15px 20px;
    border-radius: 0 0 12px 12px;
}

/* Icon enhancements */
.fas, .far {
    margin-left: 8px;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
}

/* Text color enhancements */
.text-success {
    color: #28a745 !important;
    font-weight: 600;
}

.text-danger {
    color: #dc3545 !important;
    font-weight: 600;
}

.text-primary {
    color: #007bff !important;
    font-weight: 600;
}

.text-warning {
    color: #ffc107 !important;
    font-weight: 600;
}

.text-info {
    color: #17a2b8 !important;
    font-weight: 600;
}

/* Page header enhancement */
.page-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .number-display {
        min-width: 60px;
        font-size: 0.9rem;
    }
    
    .transactions-table th,
    .transactions-table td {
        padding: 8px 6px;
        font-size: 0.85rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
}

/* Animation for loading */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}
</style>
<div class="container-fluid">
    <!-- Enhanced Page Header -->
    <div class="page-header mb-4">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle me-3">
                            <i class="fas fa-clock fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h1 class="mb-0 text-dark">{{ __('تفاصيل الوردية') }}</h1>
                            <p class="mb-0 text-muted">
                                <span class="fw-bold">#{{ $shift->shift_number }}</span> 
                                • {{ $shift->mainCashier->name }}
                                • {{ $shift->start_time->format('Y-m-d H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group" role="group">
                        <a href="{{ route('shifts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right me-1"></i> {{ __('العودة') }}
                        </a>
                        
                        @if($shift->is_closed)
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-print me-1"></i> {{ __('طباعة') }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('shifts.print', $shift) }}" class="dropdown-item" target="_blank">
                                            <i class="fas fa-file-alt me-1"></i> {{ __('طباعة تقرير الوردية') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('shifts.print', ['shift' => $shift->id, 'with_products' => 1]) }}" class="dropdown-item" target="_blank">
                                            <i class="fas fa-boxes me-1"></i> {{ __('طباعة مع الأصناف المباعة') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('shifts.print', $shift) }}" class="btn btn-info" target="_blank">
                                <i class="fas fa-print me-1"></i> {{ __('طباعة') }}
                            </a>
                        
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
                                <i class="fas fa-lock me-1"></i> {{ __('إغلاق الوردية') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .icon-circle {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.2));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
    }
    </style>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('معلومات الوردية') }}</h5>
                    <span class="badge {{ $shift->is_closed ? 'bg-danger' : 'bg-success' }} status-indicator">
                        <i class="fas {{ $shift->is_closed ? 'fa-lock' : 'fa-unlock' }} me-1"></i>
                        {{ $shift->is_closed ? __('مغلقة') : __('مفتوحة') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-hashtag text-primary"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('رقم الوردية') }}</span>
                                <span class="info-value">{{ $shift->shift_number }}</span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user text-success"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('الكاشير الرئيسي') }}</span>
                                <span class="info-value">{{ $shift->mainCashier->name }}</span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-play text-info"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('وقت البدء') }}</span>
                                <span class="info-value">{{ $shift->start_time->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-stop text-warning"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('وقت الإغلاق') }}</span>
                                <span class="info-value">{{ $shift->end_time ? $shift->end_time->format('Y-m-d H:i') : __('قيد التشغيل') }}</span>
                            </div>
                        </div>
                        
                        @if($shift->notes)
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-sticky-note text-secondary"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('ملاحظات البدء') }}</span>
                                <span class="info-value">{{ $shift->notes }}</span>
                            </div>
                        </div>
                        @endif
                        
                        @if($shift->closing_notes)
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clipboard text-danger"></i>
                            </div>
                            <div class="info-content">
                                <span class="info-label">{{ __('ملاحظات الإغلاق') }}</span>
                                <span class="info-value">{{ $shift->closing_notes }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <style>
            .info-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .info-item {
                display: flex;
                align-items: center;
                padding: 12px;
                background: rgba(0, 123, 255, 0.02);
                border-radius: 8px;
                border-left: 4px solid #007bff;
                transition: all 0.2s ease;
            }
            
            .info-item:hover {
                background: rgba(0, 123, 255, 0.05);
                transform: translateX(3px);
            }
            
            .info-icon {
                width: 40px;
                height: 40px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .info-content {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .info-label {
                font-size: 0.85rem;
                color: #6c757d;
                font-weight: 500;
                margin-bottom: 2px;
            }
            
            .info-value {
                font-size: 1rem;
                font-weight: 600;
                color: #495057;
            }
            </style>
            
            <div class="card shadow-sm mb-4 summary-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>{{ __('ملخص الأرصدة') }}</h5>
                </div>
                <div class="card-body">
                    <div class="balance-items">
                        <div class="balance-item opening">
                            <div class="balance-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('رصيد الإفتتاح') }}</span>
                                <span class="balance-value primary">{{ number_format($shift->opening_balance, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item positive">
                            <div class="balance-icon">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('إجمالي المبيعات') }}</span>
                                <span class="balance-value success">+{{ number_format($totalSales, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item positive">
                            <div class="balance-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('إجمالي الإيداعات') }}</span>
                                <span class="balance-value success">+{{ number_format($shift->current_deposits_total ?? 0, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item negative">
                            <div class="balance-icon">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('إجمالي المرتجعات') }}</span>
                                <span class="balance-value danger">-{{ number_format($shift->returns_amount ?? $shift->current_returns_total, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item negative">
                            <div class="balance-icon">
                                <i class="fas fa-minus-circle"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('إجمالي المسحوبات') }}</span>
                                @php $currentWithdrawals = $shift->is_closed ? $shift->total_withdrawals : $transactions->where('type', 'withdrawal')->sum('amount'); @endphp
                                <span class="balance-value danger">-{{ number_format($currentWithdrawals ?? 0, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item negative">
                            <div class="balance-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('إجمالي المصروفات') }}</span>
                                <span class="balance-value danger">-{{ number_format($shift->current_expenses_total ?? 0, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item total">
                            <div class="balance-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('الرصيد المتوقع في الدرج (نقدي)') }}</span>
                                @php
                                    if ($shift->is_closed) {
                                        $expected = $shift->expected_closing_balance;
                                    } else {
                                        $expected = $shift->opening_balance 
                                                    + $totalCashInDrawer
                                                    + ($shift->current_deposits_total ?? 0) 
                                                    - ($currentWithdrawals ?? 0) 
                                                    - ($shift->current_expenses_total ?? 0)
                                                    - ($shift->returns_amount ?? $shift->current_returns_total);
                                    }
                                @endphp
                                <span class="balance-value primary large">{{ number_format($expected, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        @if($shift->is_closed)
                        <div class="balance-item actual">
                            <div class="balance-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('الرصيد الفعلي عند الإغلاق') }}</span>
                                <span class="balance-value info">{{ number_format($shift->actual_closing_balance, 2) }} <small>ج.م</small></span>
                            </div>
                        </div>
                        
                        <div class="balance-item difference {{ $shift->cash_shortage_excess == 0 ? 'neutral' : ($shift->cash_shortage_excess > 0 ? 'positive' : 'negative') }}">
                            <div class="balance-icon">
                                <i class="fas {{ $shift->cash_shortage_excess == 0 ? 'fa-check-circle' : ($shift->cash_shortage_excess > 0 ? 'fa-arrow-up' : 'fa-arrow-down') }}"></i>
                            </div>
                            <div class="balance-content">
                                <span class="balance-label">{{ __('الفرق (العجز/الزيادة)') }}</span>
                                <span class="balance-value {{ $shift->cash_shortage_excess == 0 ? 'success' : ($shift->cash_shortage_excess > 0 ? 'success' : 'danger') }} large">
                                    {{ number_format($shift->cash_shortage_excess, 2) }} <small>ج.م</small>
                                    @if($shift->cash_shortage_excess != 0)
                                        <span class="badge {{ $shift->cash_shortage_excess > 0 ? 'bg-success' : 'bg-danger' }} ms-2">
                                            {{ $shift->cash_shortage_excess > 0 ? __('زيادة') : __('عجز') }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <style>
            .balance-items {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .balance-item {
                display: flex;
                align-items: center;
                padding: 15px;
                border-radius: 10px;
                transition: all 0.3s ease;
                border-left: 4px solid transparent;
            }
            
            .balance-item.opening {
                background: linear-gradient(135deg, rgba(108, 117, 125, 0.1), rgba(108, 117, 125, 0.05));
                border-left-color: #6c757d;
            }
            
            .balance-item.positive {
                background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
                border-left-color: #28a745;
            }
            
            .balance-item.negative {
                background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
                border-left-color: #dc3545;
            }
            
            .balance-item.total {
                background: linear-gradient(135deg, rgba(0, 123, 255, 0.15), rgba(0, 123, 255, 0.1));
                border-left-color: #007bff;
                border-width: 4px;
                margin: 10px 0;
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
            }
            
            .balance-item.actual {
                background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05));
                border-left-color: #17a2b8;
            }
            
            .balance-item.difference.neutral {
                background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.1));
                border-left-color: #28a745;
            }
            
            .balance-item.difference.positive {
                background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.1));
                border-left-color: #28a745;
            }
            
            .balance-item.difference.negative {
                background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.1));
                border-left-color: #dc3545;
            }
            
            .balance-item:hover {
                transform: translateX(5px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            .balance-icon {
                width: 50px;
                height: 50px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                font-size: 1.2rem;
            }
            
            .balance-icon i {
                margin: 0;
            }
            
            .balance-content {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .balance-label {
                font-size: 0.9rem;
                color: #6c757d;
                font-weight: 500;
                margin-bottom: 4px;
            }
            
            .balance-value {
                font-family: 'Courier New', monospace;
                font-size: 1.1rem;
                font-weight: 700;
                direction: ltr;
                text-align: end;
            }
            
            .balance-value.large {
                font-size: 1.3rem;
            }
            
            .balance-value.primary { color: #007bff; }
            .balance-value.success { color: #28a745; }
            .balance-value.danger { color: #dc3545; }
            .balance-value.info { color: #17a2b8; }
            
            .balance-value small {
                font-size: 0.8rem;
                color: #6c757d;
            }
            </style>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('الموظفين في الوردية') }}</h5>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('الاسم') }}</th>
                                    <th>{{ __('وقت الإنضمام') }}</th>
                                    <th>{{ __('وقت المغادرة') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shift->users as $user)
                                <tr>
                                    <td>
                                        {{ $user->name }}
                                        @if($user->id == $shift->main_cashier_id)<span class="badge bg-primary ms-1">{{ __('رئيسي') }}</span>@endif
                                    </td>
                                    <td>{{ $user->pivot->join_time ? $user->pivot->join_time->format('Y-m-d H:i') : '-' }}</td>
                                    <td>{{ $user->pivot->leave_time ? $user->pivot->leave_time->format('Y-m-d H:i') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
             <div class="card shadow-sm mb-4 summary-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>{{ __('ملخص المبيعات حسب طريقة الدفع') }}</h5>
                </div>
                <div class="card-body">
                     <table class="table table-sm table-borderless mb-0">
                        @foreach($paymentMethodsWithDetails as $method)
                            @php
                                $icon = 'fa-money-check';
                                $color = 'secondary';
                                $label = $method->payment_method;
                                
                                switch ($method->payment_method) {
                                    case 'cash':
                                        $icon = 'fa-money-bill-wave'; $color = 'success'; $label = __('نقداً');
                                        break;
                                    case 'visa':
                                        $icon = 'fa-credit-card'; $color = 'info'; $label = __('فيزا');
                                        break;
                                    case 'transfer':
                                        $icon = 'fa-exchange-alt'; $color = 'warning'; $label = __('تحويلات مالية');
                                        break;
                                    case 'credit':
                                        $icon = 'fa-calendar-alt'; $color = 'danger'; $label = __('آجل');
                                        break;
                                }
                            @endphp
                            <tr>
                                <th style="width: 40%;"><i class="fas {{ $icon }} text-{{ $color }} me-1"></i> {{ $label }}</th>
                                <td>
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="number-display currency-symbol fw-bold">{{ number_format($method->total_amount, 2) }}</span>
                                        @if($method->from_mixed > 0)
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                ({{ number_format($method->from_single, 2) }} {{ __('أساسي') }} + {{ number_format($method->from_mixed, 2) }} {{ __('من دفع متعدد') }})
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        <tr class="table-light fw-bold">
                            <th>{{ __('إجمالي المبيعات') }}</th>
                            <td>
                                <span class="badge bg-primary me-2">{{ $shift->invoices->count() }} {{ __('فاتورة') }}</span>
                                <span class="number-display currency-symbol text-primary fw-bold">{{ number_format($totalSales, 2) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4 summary-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-dolly-flatbed me-2"></i>{{ __('ملخص المبيعات حسب نوع الطلب') }}</h5>
                </div>
                <div class="card-body">
                     <table class="table table-sm table-borderless mb-0">
                        @php
                            $takeawayCount = DB::table('invoices')
                                ->where('shift_id', $shift->id)
                                ->where('order_type', 'takeaway')
                                ->whereIn('status', ['paid', 'completed'])
                                ->count();
                                
                            $deliveryCount = DB::table('invoices')
                                ->where('shift_id', $shift->id)
                                ->where('order_type', 'delivery')
                                ->whereIn('status', ['paid', 'completed'])
                                ->count();
                        @endphp
                        <tr>
                             <th><i class="fas fa-shopping-bag text-secondary me-1"></i> {{ __('تيك أواي') }}</th>
                             <td>
                                <span class="badge bg-primary me-2">{{ $takeawayCount }} {{ __('فاتورة') }}</span>
                                <span class="number-display currency-symbol">{{ number_format($salesByOrderType['takeaway'] ?? 0, 2) }}</span>
                             </td>
                        </tr>
                        <tr>
                             <th><i class="fas fa-motorcycle text-info me-1"></i> {{ __('دليفري') }}</th>
                             <td>
                                <span class="badge bg-primary me-2">{{ $deliveryCount }} {{ __('فاتورة') }}</span>
                                <span class="number-display currency-symbol">{{ number_format($salesByOrderType['delivery'] ?? 0, 2) }}</span>
                             </td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <th>{{ __('إجمالي المبيعات') }}</th>
                            <td>
                                <span class="badge bg-primary me-2">{{ $takeawayCount + $deliveryCount }} {{ __('فاتورة') }}</span>
                                <span class="number-display currency-symbol text-primary fw-bold">{{ number_format($totalSales, 2) }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
        </div> 
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('سجل الحركات المالية') }}</h5>
             @if(!$shift->is_closed)
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                    <i class="fas fa-minus-circle me-1"></i> {{ __('تسجيل سحب جديد') }}
                </button>
             @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 transactions-table">
                    <thead>
                        <tr>
                            <th>{{ __('الوقت') }}</th>
                            <th>{{ __('النوع') }}</th>
                            <th>{{ __('المرجع') }}</th>
                            <th>{{ __('العميل') }}</th>
                            <th>{{ __('نوع الطلب') }}</th>
                            <th>{{ __('طريقة الدفع') }}</th>
                            <th>{{ __('التفاصيل') }}</th>
                            <th class="text-end">{{ __('المبلغ') }}</th>
                            <th>{{ __('العمليات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction['created_at']->format('Y-m-d H:i:s') }}</td>
                            <td>
                                 @switch($transaction['type'])
                                    @case('invoice') <span class="badge bg-success">{{ $transaction['type_display'] }}</span> @break
                                    @case('withdrawal') <span class="badge bg-danger">{{ $transaction['type_display'] }}</span> @break
                                    @case('expense') <span class="badge bg-danger">{{ $transaction['type_display'] }}</span> @break
                                    @case('deposit') <span class="badge bg-info">{{ $transaction['type_display'] }}</span> @break
                                    @case('return') <span class="badge bg-danger">{{ $transaction['type_display'] ?? 'مرتجع' }}</span> @break
                                    @case('purchase_return') <span class="badge bg-success">{{ $transaction['type_display'] ?? 'مرتجع مشتريات' }}</span> @break
                                    @default <span class="badge bg-secondary">{{ $transaction['type_display'] }}</span>
                                @endswitch
                            </td>
                            <td>{{ $transaction['reference'] ?? '-' }}</td>
                            <td>
                                @if(isset($transaction['customer_name']))
                                    {{ $transaction['customer_name'] }}
                                @elseif(isset($transaction['supplier_name']))
                                    <span class="text-primary">{{ $transaction['supplier_name'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(isset($transaction['order_type']))
                                    @if($transaction['order_type'] == 'delivery')
                                        <span class="badge bg-info">{{ __('دليفري') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('تيك أواي') }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(isset($transaction['invoice_type']))
                                    @switch($transaction['invoice_type'])
                                        @case('cash')
                                            <span class="badge bg-success">{{ __('نقداً') }}</span>
                                            @break
                                        @case('credit')
                                            <span class="badge bg-warning text-dark">{{ __('آجل') }}</span>
                                            @break
                                        @case('visa')
                                            <span class="badge bg-info">{{ __('فيزا') }}</span>
                                            @break
                                        @case('transfer')
                                            <span class="badge bg-warning">{{ __('تحويلات مالية') }}</span>
                                            @break
                                        @case('card')
                                            <span class="badge bg-primary">{{ __('بطاقة') }}</span>
                                            @break
                                        @case('bank')
                                            <span class="badge bg-secondary">{{ __('تحويل بنكي') }}</span>
                                            @break
                                        @case('wallet')
                                            <span class="badge bg-primary">{{ __('محفظة') }}</span>
                                            @break
                                        @case('mixed')
                                        @case('multiple_payment')
                                            <span class="badge bg-dark">{{ __('دفع متعدد') }}</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $transaction['invoice_type'] }}</span>
                                    @endswitch
                                @elseif(isset($transaction['payment_method']))
                                    <span class="badge bg-primary">{{ $transaction['payment_method'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $transaction['details'] ?? '-' }}</td>
                            <td class="amount-cell {{ $transaction['css_class'] ?? '' }} fw-bold">
                                {{ in_array($transaction['type'], ['invoice', 'deposit', 'purchase_return']) ? '+' : '-' }} 
                                <span class="currency-symbol">{{ number_format($transaction['amount'], 2) }}</span>
                            </td>
                            <td class="text-center">
                                @if($transaction['type'] == 'invoice')
                                    @php $invId = intval(substr($transaction['id'], 4)); @endphp
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-primary view-invoice" data-id="{{ $invId }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('sales.invoices.print', $invId) }}" target="_blank" class="btn btn-info">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">{{ __('لا توجد حركات مالية لهذه الوردية') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(!$shift->is_closed)
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawModalLabel">{{ __('سحب جديد من الدرج') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('shifts.withdraw', $shift) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('المبلغ') }} <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('السبب') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('تأكيد السحب') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if(!$shift->is_closed)
    <div class="modal fade" id="closeShiftModal" tabindex="-1" aria-labelledby="closeShiftModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeShiftModalLabel">{{ __('إغلاق الوردية') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('shifts.close', $shift) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <p class="mb-1">{{ __('سيتم إغلاق الوردية رقم') }}: <strong>{{ $shift->shift_number }}</strong></p>
                            @php
                                $expectedDrawer = $shift->calculateExpectedBalance();
                            @endphp
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
                            <input class="form-check-input" type="checkbox" name="print_inventory" id="print_inventory" value="1">
                            <label class="form-check-label" for="print_inventory">
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

    <!-- Invoice Details Modal -->
    <div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة <span id="invoice-number"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-5" id="invoice-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="mt-2">جاري تحميل بيانات الفاتورة...</p>
                    </div>
                    
                    <div id="invoice-content" style="display: none;">
                        <!-- Invoice Header -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">معلومات الفاتورة</h6>
                                <p><strong>رقم الفاتورة:</strong> <span id="modal-invoice-number"></span></p>
                                <p><strong>التاريخ:</strong> <span id="modal-invoice-date"></span></p>
                                <p><strong>نوع الفاتورة:</strong> <span id="modal-invoice-type"></span></p>
                                <p><strong>نوع الطلب:</strong> <span id="modal-invoice-order-type"></span></p>
                                <p><strong>الحالة:</strong> <span id="modal-invoice-status"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">معلومات العميل</h6>
                                <p><strong>الاسم:</strong> <span id="modal-customer-name"></span></p>
                                <p><strong>الهاتف:</strong> <span id="modal-customer-phone"></span></p>
                                <p><strong>العنوان:</strong> <span id="modal-customer-address"></span></p>
                            </div>
                        </div>
                        
                        <!-- Invoice Items -->
                        <h6 class="fw-bold mb-3">منتجات الفاتورة</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>المنتج</th>
                                        <th>الوحدة</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>الخصم</th>
                                        <th>الإجمالي</th>
                                        <th>الربح</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-items">
                                    <!-- Items will be added dynamically -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Invoice Summary -->
                        <div class="row mt-4">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>المجموع قبل الخصم</th>
                                            <td id="modal-subtotal"></td>
                                        </tr>
                                        <tr>
                                            <th>الخصم</th>
                                            <td id="modal-discount"></td>
                                        </tr>
                                        <tr class="table-primary">
                                            <th>الإجمالي</th>
                                            <td id="modal-total"></td>
                                        </tr>
                                        <tr>
                                            <th>المدفوع</th>
                                            <td id="modal-paid"></td>
                                        </tr>
                                        <tr>
                                            <th>المتبقي</th>
                                            <td id="modal-remaining"></td>
                                        </tr>
                                        <tr class="table-success">
                                            <th>إجمالي الربح</th>
                                            <td id="modal-profit"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <a href="#" class="btn btn-primary" id="print-invoice-btn" target="_blank">
                        <i class="fas fa-print me-1"></i> طباعة الفاتورة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 

<!-- Multiple Payment Details Modal -->
<div class="modal fade" id="multiplePaymentModal" tabindex="-1" aria-labelledby="multiplePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="multiplePaymentModalLabel">تفاصيل فواتير الدفع المتعدد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="multiple-payment-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل بيانات الفواتير...</p>
                </div>
                
                <div id="multiple-payment-content" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>طرق الدفع</th>
                                    <th>الإجمالي</th>
                                    <th>العمليات</th>
                                </tr>
                            </thead>
                            <tbody id="multiple-payment-invoices">
                                <!-- Invoices will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // View Invoice Details
    $('body').on('click', '.view-invoice', function(e) {
        e.preventDefault();
        const invoiceId = $(this).data('id');
        
        // Reset modal content
        $('#invoice-loading').show();
        $('#invoice-content').hide();
        $('#invoice-items').empty();
        
        // Show modal
        $('#invoiceDetailsModal').modal('show');
        
        // Set the print button URL
        $('#print-invoice-btn').attr('href', `{{ url('sales/invoices') }}/${invoiceId}/print`);
        
        // Fetch invoice details
        $.ajax({
            url: `{{ url('api/sales/invoices') }}/${invoiceId}`,
            method: 'GET',
            success: function(response) {
                console.log('API Response:', response);
                if (response.success) {
                    const invoice = response.invoice;
                    
                    // Set invoice details
                    $('#invoice-number, #modal-invoice-number').text(invoice.invoice_number);
                    $('#modal-invoice-date').text(new Date(invoice.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    }));
                    
                    const invoiceType = invoice.type || invoice.invoice_type || 'cash';
                    $('#modal-invoice-type').text(invoiceType);
                    
                    $('#modal-invoice-order-type').text(invoice.order_type === 'takeaway' ? 'تيك أواي' : 'دليفري');
                    
                    let statusText = '';
                    if (invoice.status === 'completed') {
                        statusText = '<span class="badge bg-success">مكتملة</span>';
                    } else if (invoice.status === 'pending') {
                        statusText = '<span class="badge bg-warning">معلقة</span>';
                    } else if (invoice.status === 'canceled') {
                        statusText = '<span class="badge bg-danger">ملغية</span>';
                    }
                    $('#modal-invoice-status').html(statusText);
                    
                    // Set customer details
                    if (invoice.customer) {
                        $('#modal-customer-name').text(invoice.customer.name);
                        $('#modal-customer-phone').text(invoice.customer.phone || 'غير متوفر');
                        $('#modal-customer-address').text(invoice.customer.address || 'غير متوفر');
                    }
                    
                    // Set invoice items
                    if (invoice.items && invoice.items.length > 0) {
                        $.each(invoice.items, function(index, item) {
                            const quantity = Number(item.quantity || 0);
                            const unitPrice = Number(item.unit_price || 0);
                            const total = Number(item.total || 0);
                            const profit = Number(item.profit || 0);
                            const discountPercentage = Number(item.discount_percentage || 0);
                            const discountValue = Number(item.discount_value || 0);
                            
                            const discountText = discountPercentage > 0 
                                ? `${discountPercentage}%` 
                                : `${discountValue}`;
                                
                            const row = `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.product ? item.product.name : 'غير متوفر'}</td>
                                    <td>${item.unit ? item.unit.name : 'غير متوفر'}</td>
                                    <td>${quantity}</td>
                                    <td>${unitPrice.toFixed(2)}</td>
                                    <td>${discountText}</td>
                                    <td>${total.toFixed(2)}</td>
                                    <td>${profit.toFixed(2)}</td>
                                </tr>
                            `;
                            $('#invoice-items').append(row);
                        });
                    } else {
                        $('#invoice-items').html('<tr><td colspan="8" class="text-center">لا توجد منتجات</td></tr>');
                    }
                    
                    // Set invoice summary
                    const subtotal = Number(invoice.subtotal || 0);
                    const discountPercentage = Number(invoice.discount_percentage || 0);
                    const discountValue = Number(invoice.discount_value || 0);
                    const total = Number(invoice.total || 0);
                    const paidAmount = Number(invoice.paid_amount || 0);
                    const remainingAmount = invoice.remaining_amount !== undefined ? Number(invoice.remaining_amount) : Number(invoice.remaining || 0);
                    const profit = Number(invoice.profit || 0);
                    
                    $('#modal-subtotal').text(`${subtotal.toFixed(2)}`);
                    
                    let discountText = '';
                    if (discountPercentage > 0) {
                        discountText = `${discountPercentage}% (${discountValue.toFixed(2)})`;
                    } else if (discountValue > 0) {
                        discountText = `${discountValue.toFixed(2)}`;
                    } else {
                        discountText = '0.00';
                    }
                    $('#modal-discount').text(discountText);
                    
                    $('#modal-total').text(`${total.toFixed(2)}`);
                    $('#modal-paid').text(`${paidAmount.toFixed(2)}`);
                    $('#modal-remaining').text(`${remainingAmount.toFixed(2)}`);
                    $('#modal-profit').text(`${profit.toFixed(2)}`);
                    
                    // Hide loading, show content
                    $('#invoice-loading').hide();
                    $('#invoice-content').show();
                } else {
                    alert('حدث خطأ أثناء تحميل بيانات الفاتورة');
                }
            },
            error: function(xhr) {
                alert('حدث خطأ في الاتصال بالخادم');
                console.error('AJAX Error:', xhr.responseText);
                $('#invoiceDetailsModal').modal('hide');
            }
        });
    });
    
    // View Multiple Payment Details
    $('.view-multiple-payment-details').on('click', function(e) {
        e.preventDefault();
        
        // Reset modal content
        $('#multiple-payment-loading').show();
        $('#multiple-payment-content').hide();
        $('#multiple-payment-invoices').empty();
        
        // Fetch multiple payment invoices
        $.ajax({
            url: `{{ url('api/shifts') }}/{{ $shift->id }}/multiple-payment-invoices`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const invoices = response.invoices;
                    
                    if (invoices && invoices.length > 0) {
                        $.each(invoices, function(index, invoice) {
                            // Format payment methods
                            let paymentMethodsHtml = '';
                            if (invoice.payments && invoice.payments.length > 0) {
                                paymentMethodsHtml = invoice.payments.map(payment => {
                                    let methodName = '';
                                    switch(payment.method) {
                                        case 'cash': methodName = 'نقداً'; break;
                                        case 'visa': methodName = 'فيزا'; break;
                                        case 'transfer': methodName = 'تحويل'; break;
                                        default: methodName = payment.method;
                                    }
                                    return `<div><span class="badge bg-info">${methodName}</span> <span class="fw-bold">${Number(payment.amount).toFixed(2)}</span></div>`;
                                }).join('');
                            } else {
                                paymentMethodsHtml = '<span class="badge bg-warning">غير محدد</span>';
                            }
                            
                            const row = `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${invoice.invoice_number}</td>
                                    <td>${new Date(invoice.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: '2-digit',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</td>
                                    <td>${invoice.customer ? invoice.customer.name : 'عميل نقدي'}</td>
                                    <td>${paymentMethodsHtml}</td>
                                    <td>${Number(invoice.total).toFixed(2)}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="#" class="btn btn-primary view-invoice" data-id="${invoice.id}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ url('sales/invoices') }}/${invoice.id}/print" target="_blank" class="btn btn-info">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            $('#multiple-payment-invoices').append(row);
                        });
                    } else {
                        $('#multiple-payment-invoices').html('<tr><td colspan="7" class="text-center">لا توجد فواتير دفع متعدد</td></tr>');
                    }
                    
                    // Hide loading, show content
                    $('#multiple-payment-loading').hide();
                    $('#multiple-payment-content').show();
                } else {
                    alert('حدث خطأ أثناء تحميل بيانات الفواتير');
                    $('#multiplePaymentModal').modal('hide');
                }
            },
            error: function(xhr) {
                alert('حدث خطأ في الاتصال بالخادم');
                console.error('AJAX Error:', xhr.responseText);
                $('#multiplePaymentModal').modal('hide');
            }
        });
    });
});
</script>
@endpush 