@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>{{ __('تعديل الإيداع') }}</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('deposits.update', $deposit) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="amount" class="form-label">{{ __('المبلغ') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $deposit->amount) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="deposit_source_id" class="form-label">{{ __('مصدر الإيداع') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('deposit_source_id') is-invalid @enderror" id="deposit_source_id" name="deposit_source_id" required>
                                <option value="">{{ __('-- اختر مصدراً --') }}</option>
                                @foreach($sources as $id => $name)
                                    <option value="{{ $id }}" {{ old('deposit_source_id', $deposit->deposit_source_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('deposit_source_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">{{ __('الملاحظات') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $deposit->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        {{-- Keep the original user --}}
                        <input type="hidden" name="user_id" value="{{ $deposit->user_id }}">

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('deposits.index') }}" class="btn btn-outline-secondary me-2">{{ __('إلغاء') }}</a>
                            <button type="submit" class="btn btn-success">{{ __('حفظ التعديلات') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 