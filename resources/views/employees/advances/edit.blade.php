@extends('layouts.app')

@section('title', 'تعديل السلفة')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2 class="mb-0">تعديل السلفة #{{ $employeeAdvance->id }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">الموظفين</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-advances.index') }}">سلف الموظفين</a></li>
                    <li class="breadcrumb-item active" aria-current="page">تعديل السلفة</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('employee-advances.update', $employeeAdvance) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employee_id" class="form-label required">الموظف</label>
                            <input type="text" class="form-control" value="{{ $employeeAdvance->employee->name }}" readonly>
                            <input type="hidden" name="employee_id" value="{{ $employeeAdvance->employee_id }}">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="amount" class="form-label required">مبلغ السلفة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $employeeAdvance->amount) }}" required>
                                <span class="input-group-text">ج.م</span>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date" class="form-label required">تاريخ السلفة</label>
                            <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $employeeAdvance->date->format('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="repayment_date" class="form-label">تاريخ السداد المتوقع</label>
                            <input type="date" id="repayment_date" name="repayment_date" class="form-control @error('repayment_date') is-invalid @enderror" value="{{ old('repayment_date', $employeeAdvance->repayment_date ? $employeeAdvance->repayment_date->format('Y-m-d') : '') }}">
                            @error('repayment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="checkbox" id="is_deducted_from_salary" name="is_deducted_from_salary" class="form-check-input" value="1" {{ old('is_deducted_from_salary', $employeeAdvance->is_deducted_from_salary) ? 'checked' : '' }}>
                            <label for="is_deducted_from_salary" class="form-check-label">خصم من الراتب</label>
                            <small class="form-text text-muted d-block">إذا تم تحديد هذا الخيار، سيتم خصم السلفة تلقائيًا من الراتب عند دفعه.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $employeeAdvance->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('employee-advances.show', $employeeAdvance) }}" class="btn btn-secondary me-2">إلغاء</a>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 