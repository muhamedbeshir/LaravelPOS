@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ __('فتح وردية جديدة') }}</h2>
        <a href="{{ route('shifts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-right me-1"></i> {{ __('العودة إلى قائمة الورديات') }}
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('بيانات الوردية الجديدة') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('shifts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('المبلغ الابتدائي في الدرج') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="opening_balance" step="0.01" min="0" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}" required>
                                    @error('opening_balance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('الكاشير الرئيسي') }}</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('إضافة موظفين للوردية') }}</label>
                            <select name="users[]" class="form-select select2" multiple>
                                @foreach($users as $user)
                                    @if($user->id != Auth::id())
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('اختر الموظفين المشاركين في هذه الوردية') }}</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('ملاحظات') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ __('فتح الوردية') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            @if($lastShift)
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('معلومات آخر وردية') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>{{ __('رقم الوردية') }}</th>
                            <td>{{ $lastShift->shift_number }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('الكاشير') }}</th>
                            <td>{{ $lastShift->mainCashier->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('تاريخ الإغلاق') }}</th>
                            <td>{{ $lastShift->end_time->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('المبلغ الختامي') }}</th>
                            <td>{{ number_format($lastShift->actual_closing_balance, 2) }}</td>
                        </tr>
                    </table>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('shifts.show', $lastShift) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye me-1"></i> {{ __('عرض التفاصيل') }}
                        </a>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <h5>{{ __('لا توجد ورديات سابقة') }}</h5>
                <p class="mb-0">{{ __('هذه هي أول وردية في النظام.') }}</p>
            </div>
            @endif
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('تعليمات') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled ps-3">
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('ادخل المبلغ الموجود في الدرج في بداية الوردية.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('يمكنك إضافة موظفين آخرين للوردية ليتمكنوا من استخدام نقطة البيع.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('لا يمكن فتح أكثر من وردية في نفس الوقت.') }}</li>
                        <li class="mb-2"><i class="fas fa-info-circle text-info me-2"></i> {{ __('سيتم تسجيل جميع عمليات البيع ضمن الوردية المفتوحة.') }}</li>
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