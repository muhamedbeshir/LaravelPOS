@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ __('تعديل الوردية') }} #{{ $shift->shift_number }}</h2>
        <div>
            <a href="{{ route('shifts.show', $shift) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i> {{ __('العودة إلى تفاصيل الوردية') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('بيانات الوردية') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('shifts.update', $shift) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('رقم الوردية') }}</label>
                                    <input type="text" class="form-control" value="{{ $shift->shift_number }}" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('الكاشير الرئيسي') }}</label>
                                    <input type="text" class="form-control" value="{{ $shift->mainCashier->name }}" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('وقت البدء') }}</label>
                                    <input type="text" class="form-control" value="{{ $shift->start_time->format('Y-m-d H:i') }}" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('المبلغ الابتدائي') }}</label>
                                    <input type="text" class="form-control" value="{{ number_format($shift->opening_balance, 2) }}" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('الموظفين في الوردية') }}</label>
                            <select name="users[]" class="form-select select2" multiple>
                                @foreach($users as $user)
                                    @if($user->id != $shift->main_cashier_id)
                                        <option value="{{ $user->id }}" {{ in_array($user->id, $shiftUsers) ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('يمكنك إضافة أو إزالة موظفين من الوردية') }}</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('ملاحظات') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $shift->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ __('حفظ التغييرات') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('معلومات الوردية الحالية') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>{{ __('المبيعات النقدية') }}</th>
                            <td>{{ number_format($shift->cash_sales, 2) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('المسحوبات') }}</th>
                            <td>{{ number_format($shift->withdrawal_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('الرصيد المتوقع') }}</th>
                            <td>{{ number_format($shift->calculateExpectedBalance(), 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('تعليمات') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled ps-3">
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('يمكنك إضافة موظفين جدد للوردية أو إزالة الموظفين الحاليين.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('لا يمكن تغيير الكاشير الرئيسي للوردية.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-danger me-2"></i> {{ __('لا يمكن تعديل المبلغ الابتدائي أو وقت بدء الوردية.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('يمكنك إضافة ملاحظات إضافية للوردية.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: "{{ __('اختر الموظفين') }}",
            allowClear: true
        });
    });
</script>
@endpush 