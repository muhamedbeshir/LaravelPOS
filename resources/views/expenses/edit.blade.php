@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>{{ __('تعديل المصروف') }}</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('expenses.update', $expense) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="amount" class="form-label">{{ __('المبلغ') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $expense->amount) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="expense_category_id" class="form-label">{{ __('فئة المصروف') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('expense_category_id') is-invalid @enderror" id="expense_category_id" name="expense_category_id" required>
                                <option value="">{{ __('-- اختر فئة --') }}</option>
                                @foreach($categories as $id => $name)
                                    <option value="{{ $id }}" {{ old('expense_category_id', $expense->expense_category_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">{{ __('الملاحظات') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $expense->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        {{-- Keep the original user --}}
                        <input type="hidden" name="user_id" value="{{ $expense->user_id }}">

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary me-2">{{ __('إلغاء') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('حفظ التعديلات') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 