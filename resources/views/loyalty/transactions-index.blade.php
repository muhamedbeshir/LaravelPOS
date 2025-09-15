@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-history text-warning"></i>
                        معاملات نقاط الولاء
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('loyalty.dashboard') }}" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line"></i> لوحة التحكم
                        </a>
                        <a href="{{ route('loyalty.customers') }}" class="btn btn-outline-info">
                            <i class="fas fa-users"></i> العملاء
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                                <div class="form-group">
                                    <label for="type_filter">نوع المعاملة</label>
                                    <select name="type_filter" id="type_filter" class="form-control">
                                        <option value="">جميع المعاملات</option>
                                        <option value="earned" {{ request('type_filter') == 'earned' ? 'selected' : '' }}>ربح نقاط</option>
                                        <option value="redeemed_balance" {{ request('type_filter') == 'redeemed_balance' ? 'selected' : '' }}>استبدال إلى رصيد</option>
                                        <option value="redeemed_discount" {{ request('type_filter') == 'redeemed_discount' ? 'selected' : '' }}>استبدال لخصم</option>
                                        <option value="adjusted" {{ request('type_filter') == 'adjusted' ? 'selected' : '' }}>تعديل يدوي</option>
                                        <option value="reset" {{ request('type_filter') == 'reset' ? 'selected' : '' }}>إعادة تعيين</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="customer_id">العميل</label>
                                    <select name="customer_id" id="customer_id" class="form-control">
                                        <option value="">جميع العملاء</option>
                                        @foreach(\App\Models\Customer::where('id', '!=', 1)->orderBy('name')->get() as $customer)
                                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="date_from">من تاريخ</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>

                                <div class="form-group">
                                    <label for="date_to">إلى تاريخ</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> بحث
                                    </button>
                                    <a href="{{ route('loyalty.transactions') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> مسح
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">إجمالي المعاملات</h6>
                                            <h4>{{ $transactions->total() }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-list fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">نقاط مكتسبة</h6>
                                            <h4>{{ number_format((int) $transactions->where('type', 'earned')->sum('points')) }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-plus fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">نقاط مستبدلة</h6>
                                            <h4>{{ number_format(abs((int) $transactions->whereIn('type', ['redeemed_balance', 'redeemed_discount'])->sum('points'))) }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-minus fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">الصفحة الحالية</h6>
                                            <h4>{{ $transactions->currentPage() }} من {{ $transactions->lastPage() }}</h4>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>نوع المعاملة</th>
                                    <th>النقاط</th>
                                    <th>المبلغ</th>
                                    <th>المستخدم</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <div class="text-sm">
                                                {{ $transaction->created_at->format('Y-m-d') }}
                                                <br>
                                                <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($transaction->customer)
                                                <a href="{{ route('loyalty.customers', ['customer_id' => $transaction->customer->id]) }}" class="text-decoration-none">
                                                    {{ $transaction->customer->name }}
                                                </a>
                                                @if($transaction->customer->phone)
                                                    <br><small class="text-muted">{{ $transaction->customer->phone }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">عميل محذوف</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($transaction->type)
                                                @case('earned')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-plus"></i> ربح نقاط
                                                    </span>
                                                    @break
                                                @case('redeemed_balance')
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-wallet"></i> استبدال رصيد
                                                    </span>
                                                    @break
                                                @case('redeemed_discount')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-percent"></i> استبدال خصم
                                                    </span>
                                                    @break
                                                @case('adjusted')
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-edit"></i> تعديل يدوي
                                                    </span>
                                                    @break
                                                @case('reset')
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-redo"></i> إعادة تعيين
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $transaction->type }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($transaction->points > 0)
                                                <span class="text-success fw-bold">+{{ number_format($transaction->points) }}</span>
                                            @elseif($transaction->points < 0)
                                                <span class="text-danger fw-bold">{{ number_format($transaction->points) }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->amount)
                                                <span class="fw-bold">{{ number_format($transaction->amount, 2) }} ريال</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->user)
                                                {{ $transaction->user->name }}
                                            @else
                                                <span class="text-muted">نظام</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->metadata)
                                                @php
                                                    $metadata = is_string($transaction->metadata) ? json_decode($transaction->metadata, true) : $transaction->metadata;
                                                @endphp
                                                @if(isset($metadata['invoice_number']))
                                                    <small>فاتورة: {{ $metadata['invoice_number'] }}</small>
                                                @elseif(isset($metadata['reason']))
                                                    <small>{{ $metadata['reason'] }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <br>
                                            لا توجد معاملات للعرض
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($transactions->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $transactions->appends(request()->query())->links() }}
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
$(document).ready(function() {
    // Auto-submit form when filters change
    $('#type_filter, #customer_id').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>
@endsection 