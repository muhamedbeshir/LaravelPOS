@extends('layouts.app')

@section('title', 'تعديل بيانات الموظف')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            تعديل بيانات الموظف
                        </h5>
                        <div>
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-eye me-1"></i>
                                عرض التفاصيل
                            </a>
                            <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-list me-1"></i>
                                قائمة الموظفين
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('employees.update', $employee) }}" method="POST" class="row g-3">
                        @csrf
                        @method('PUT')
                        
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
                                               id="name" name="name" value="{{ old('name', $employee->name) }}" required>
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="employee_number" class="form-label">رقم الموظف <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('employee_number') is-invalid @enderror" 
                                               id="employee_number" name="employee_number" 
                                               value="{{ old('employee_number', $employee->employee_number) }}" required>
                                        @error('employee_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="national_id" class="form-label">الرقم القومي</label>
                                        <input type="text" class="form-control @error('national_id') is-invalid @enderror" 
                                               id="national_id" name="national_id" 
                                               value="{{ old('national_id', $employee->national_id) }}">
                                        @error('national_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
                                        @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">العنوان</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                                  id="address" name="address" rows="3">{{ old('address', $employee->address) }}</textarea>
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
                                        <select class="form-control @error('job_title_id') is-invalid @enderror" 
                                                id="job_title_id" name="job_title_id" required>
                                            <option value="">اختر المسمى الوظيفي</option>
                                            @foreach($jobTitles as $jobTitle)
                                            <option value="{{ $jobTitle->id }}" 
                                                    {{ old('job_title_id', $employee->job_title_id) == $jobTitle->id ? 'selected' : '' }}>
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
                                                   id="salary" name="salary" value="{{ old('salary', $employee->salary) }}" 
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
                                               value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" required>
                                        @error('hire_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">ملاحظات</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" name="notes" rows="3">{{ old('notes', $employee->notes) }}</textarea>
                                        @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" 
                                                   name="is_active" value="1" 
                                                   {{ old('is_active', $employee->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">موظف نشط</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>
                                حفظ التعديلات
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

    <!-- سجل المدفوعات -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        سجل المدفوعات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="paymentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>رقم المرجع</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->salaryPayments as $payment)
                                <tr>
                                    <td>{{ $payment->created_at->format('Y-m-d') }}</td>
                                    <td>{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->getPaymentMethodText() }}</td>
                                    <td>{{ $payment->reference_number ?: '-' }}</td>
                                    <td>{{ $payment->notes ?: '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">لا يوجد مدفوعات مسجلة</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- سجل الحضور والانصراف -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        سجل الحضور والانصراف
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="attendanceTable">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>وقت الحضور</th>
                                    <th>وقت الانصراف</th>
                                    <th>عدد الساعات</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->attendanceRecords->sortByDesc('check_in') as $record)
                                <tr>
                                    <td>{{ $record->check_in->format('Y-m-d') }}</td>
                                    <td>{{ $record->check_in->format('H:i:s') }}</td>
                                    <td>{{ $record->check_out ? $record->check_out->format('H:i:s') : '-' }}</td>
                                    <td>{{ $record->duration ?: '-' }}</td>
                                    <td>
                                        @if($record->check_out)
                                        <span class="badge bg-success">مكتمل</span>
                                        @else
                                        <span class="badge bg-warning text-dark">قيد العمل</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-clock fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">لا يوجد سجلات حضور</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('/assets/dataTables.bootstrap5.min.css') }}">
<style>
    .card-header {
        background-color: #f8f9fa;
    }
    .form-label {
        font-weight: 500;
    }
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/assets/dataTables.bootstrap5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة جداول البيانات
    $('#paymentsTable, #attendanceTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // تنسيق حقل رقم الهاتف
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) {
            value = value.slice(0, 11);
        }
        e.target.value = value;
    });

    // تنسيق حقل الرقم القومي
    const nationalIdInput = document.getElementById('national_id');
    nationalIdInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 14) {
            value = value.slice(0, 14);
        }
        e.target.value = value;
    });

    // تنسيق حقل الراتب
    const salaryInput = document.getElementById('salary');
    salaryInput.addEventListener('input', function(e) {
        if (e.target.value < 0) {
            e.target.value = 0;
        }
    });
});
</script>
@endpush
@endsection 