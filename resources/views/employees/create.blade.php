@extends('layouts.app')

@section('title', 'إضافة موظف جديد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        إضافة موظف جديد
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('employees.store') }}" method="POST" id="employeeForm">
                        @csrf
                        
                        <div class="row">
                            <!-- البيانات الأساسية -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">البيانات الأساسية</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">اسم الموظف <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                   id="name" name="name" value="{{ old('name') }}" required>
                                            @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="employee_number" class="form-label">رقم الموظف <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('employee_number') is-invalid @enderror" 
                                                   id="employee_number" name="employee_number" value="{{ old('employee_number') }}" required>
                                            @error('employee_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="national_id" class="form-label">الرقم القومي</label>
                                            <input type="text" class="form-control @error('national_id') is-invalid @enderror" 
                                                   id="national_id" name="national_id" value="{{ old('national_id') }}">
                                            @error('national_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">رقم الهاتف</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                                   id="phone" name="phone" value="{{ old('phone') }}">
                                            @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="address" class="form-label">العنوان</label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                                      id="address" name="address" rows="3">{{ old('address') }}</textarea>
                                            @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- بيانات الوظيفة -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">بيانات الوظيفة</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="job_title_id" class="form-label">المسمى الوظيفي <span class="text-danger">*</span></label>
                                            <select class="form-select @error('job_title_id') is-invalid @enderror" 
                                                    id="job_title_id" name="job_title_id" required>
                                                <option value="">اختر المسمى الوظيفي</option>
                                                @foreach($jobTitles as $jobTitle)
                                                <option value="{{ $jobTitle->id }}" 
                                                        data-base-salary="{{ $jobTitle->base_salary }}"
                                                        {{ old('job_title_id') == $jobTitle->id ? 'selected' : '' }}>
                                                    {{ $jobTitle->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('job_title_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="salary" class="form-label">الراتب الأساسي ({{ $salaryDisplayFrequency === 'weekly' ? 'أسبوعي' : 'شهري' }}) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control @error('salary') is-invalid @enderror" 
                                                       id="salary" name="salary" value="{{ old('salary') }}" 
                                                       step="0.01" min="0" required>
                                                <span class="input-group-text">جنيه</span>
                                            </div>
                                            @error('salary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="hire_date" class="form-label">تاريخ التعيين <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror" 
                                                   id="hire_date" name="hire_date" 
                                                   value="{{ old('hire_date', date('Y-m-d')) }}" required>
                                            @error('hire_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="role" class="form-label">الدور الوظيفي <span class="text-danger">*</span></label>
                                            <select class="form-select @error('role') is-invalid @enderror" 
                                                    id="role" name="role" required>
                                                <option value="cashier" {{ old('role') == 'cashier' ? 'selected' : '' }}>كاشير</option>
                                                <option value="delivery" {{ old('role') == 'delivery' ? 'selected' : '' }}>مندوب توصيل</option>
                                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>مدير</option>
                                            </select>
                                            @error('role')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="notes" class="form-label">ملاحظات</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                            @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>
                                حفظ البيانات
                            </button>
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary px-5 ms-2">
                                <i class="fas fa-times me-2"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('employeeForm');
    const jobTitleSelect = document.getElementById('job_title_id');
    const salaryInput = document.getElementById('salary');

    // تحديث الراتب الأساسي عند اختيار المسمى الوظيفي
    jobTitleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const baseSalary = selectedOption.dataset.baseSalary;
            salaryInput.value = baseSalary;
        }
    });

    // التحقق من صحة البيانات قبل الإرسال
    form.addEventListener('submit', function(e) {
        let isValid = true;
        let firstInvalidField = null;

        // التحقق من الحقول المطلوبة
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
                if (!firstInvalidField) firstInvalidField = field;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        // التحقق من صحة الراتب
        if (salaryInput.value <= 0) {
            isValid = false;
            salaryInput.classList.add('is-invalid');
            if (!firstInvalidField) firstInvalidField = salaryInput;
        }

        if (!isValid) {
            e.preventDefault();
            firstInvalidField.focus();
            alert('يرجى ملء جميع الحقول المطلوبة بشكل صحيح');
        }
    });
});
</script>
@endpush

@endsection 