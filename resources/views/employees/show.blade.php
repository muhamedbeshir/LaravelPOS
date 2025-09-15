@extends('layouts.app')

@section('title', 'تفاصيل الموظف')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            تفاصيل الموظف
                        </h5>
                        <div>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-edit me-1"></i>
                                تعديل البيانات
                            </a>
                            <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-list me-1"></i>
                                قائمة الموظفين
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- البيانات الأساسية -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">البيانات الأساسية</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <tr>
                                            <th width="35%">اسم الموظف</th>
                                            <td>{{ $employee->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>رقم الموظف</th>
                                            <td>{{ $employee->employee_number }}</td>
                                        </tr>
                                        <tr>
                                            <th>الرقم القومي</th>
                                            <td>{{ $employee->national_id ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>رقم الهاتف</th>
                                            <td>{{ $employee->phone ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>العنوان</th>
                                            <td>{{ $employee->address ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>الحالة</th>
                                            <td>
                                                <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $employee->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
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
                                    <table class="table table-striped">
                                        <tr>
                                            <th width="35%">المسمى الوظيفي</th>
                                            <td>{{ $employee->jobTitle->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>الراتب الأساسي</th>
                                            <td>{{ number_format($employee->salary, 2) }} جنيه</td>
                                        </tr>
                                        <tr>
                                            <th>تاريخ التعيين</th>
                                            <td>{{ $employee->hire_date->format('Y-m-d') }}</td>
                                        </tr>
                                        <tr>
                                            <th>مدة الخدمة</th>
                                            <td>{{ $employee->hire_date->diffForHumans(['parts' => 2]) }}</td>
                                        </tr>
                                        <tr>
                                            <th>حالة الراتب ({{ $salaryDisplayFrequency === 'weekly' ? 'أسبوعي' : 'شهري' }})</th>
                                            <td>
                                                <span class="badge {{ $employee->current_period_payment ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $employee->current_period_payment ? 'تم الدفع' : 'لم يتم الدفع' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ملاحظات</th>
                                            <td>{{ $employee->notes ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات سريعة -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">إجمالي المدفوعات</h6>
                                            <h3 class="mb-0">{{ number_format($employee->salaryPayments->sum('amount'), 2) }}</h3>
                                        </div>
                                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">أيام الحضور</h6>
                                            <h3 class="mb-0">{{ $employee->attendanceRecords->count() }}</h3>
                                        </div>
                                        <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">متوسط ساعات العمل</h6>
                                            <h3 class="mb-0">{{ number_format($employee->averageWorkingHours(), 1) }}</h3>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">عدد الإشعارات</h6>
                                            <h3 class="mb-0">{{ $employee->notifications->count() }}</h3>
                                        </div>
                                        <i class="fas fa-bell fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- سجل السلف -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-hand-holding-usd me-2"></i>
                                            سلف الموظف
                                        </h6>
                                        <div>
                                            <a href="{{ route('employees.advances', $employee) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-list me-1"></i>
                                                عرض كل السلف
                                            </a>
                                            @can('create-employee-advances')
                                            <a href="{{ route('employee-advances.create') }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus-circle me-1"></i>
                                                إضافة سلفة جديدة
                                            </a>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @php
                                        $advances = $employee->advances()->orderByDesc('date')->limit(5)->get();
                                    @endphp
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="advancesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>التاريخ</th>
                                                    <th>المبلغ</th>
                                                    <th>المبلغ المخصوم</th>
                                                    <th>المتبقي</th>
                                                    <th>الحالة</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($advances as $advance)
                                                <tr>
                                                    <td>{{ $advance->date->format('Y-m-d') }}</td>
                                                    <td>{{ number_format($advance->amount, 2) }}</td>
                                                    <td>{{ number_format($advance->deducted_amount, 2) }}</td>
                                                    <td>{{ number_format($advance->remaining_amount, 2) }}</td>
                                                    <td>
                                                        @if($advance->status === 'pending')
                                                            <span class="badge bg-warning">معلقة</span>
                                                        @elseif($advance->status === 'partially_paid')
                                                            <span class="badge bg-info">مدفوعة جزئيًا</span>
                                                        @elseif($advance->status === 'paid')
                                                            <span class="badge bg-success">مدفوعة بالكامل</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('employee-advances.show', $advance) }}" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3 d-block"></i>
                                                        <p class="text-muted">لا يوجد سلف مسجلة</p>
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

                    <!-- سجل المدفوعات -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-money-bill-wave me-2"></i>
                                            سجل المدفوعات
                                        </h6>
                                        @if($employee->is_active)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                            <i class="fas fa-plus-circle me-1"></i>
                                            دفعة جديدة
                                        </button>
                                        @endif
                                    </div>
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
                                                @forelse($employee->salaryPayments->sortByDesc('created_at') as $payment)
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
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-clock me-2"></i>
                                            سجل الحضور والانصراف
                                        </h6>
                                        @if($employee->is_active)
                                        <div>
                                            @if(!$employee->isCheckedIn())
                                            <form action="{{ route('employees.check-in', $employee) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-sign-in-alt me-1"></i>
                                                    تسجيل حضور
                                                </button>
                                            </form>
                                            @else
                                            <form action="{{ route('employees.check-out', $employee) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-sign-out-alt me-1"></i>
                                                    تسجيل انصراف
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
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

                    <!-- الإشعارات -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-bell me-2"></i>
                                        الإشعارات
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="notificationsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>التاريخ</th>
                                                    <th>النوع</th>
                                                    <th>الرسالة</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($employee->notifications->sortByDesc('created_at') as $notification)
                                                <tr>
                                                    <td>{{ $notification->created_at->format('Y-m-d H:i:s') }}</td>
                                                    <td>
                                                        <span class="badge {{ $notification->getTypeClass() }}">
                                                            {{ $notification->getTypeText() }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $notification->message }}</td>
                                                    <td>
                                                        @if($notification->read_at)
                                                        <span class="badge bg-success">تم القراءة</span>
                                                        @else
                                                        <span class="badge bg-warning text-dark">جديد</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">
                                                        <i class="fas fa-bell fa-3x text-muted mb-3 d-block"></i>
                                                        <p class="text-muted">لا يوجد إشعارات</p>
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
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة دفعة -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة دفعة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('employees.pay-salary', $employee) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الموظف</label>
                        <input type="text" class="form-control" value="{{ $employee->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الراتب الأساسي</label>
                        <input type="text" class="form-control" value="{{ number_format($employee->salary, 2) }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">المبلغ المراد دفعه <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               value="{{ $employee->salary }}" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="cash">نقداً</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="check">شيك</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">رقم المرجع</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        حفظ الدفعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('/assets/dataTables.bootstrap5.min.css') }}">
<style>
    .card-header {
        background-color: #f8f9fa;
    }
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    .table th {
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/assets/dataTables.bootstrap5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة جداول البيانات
    $('#paymentsTable, #attendanceTable, #notificationsTable, #advancesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // التحقق من المبلغ المدخل
    const amountInput = document.getElementById('amount');
    amountInput.addEventListener('input', function(e) {
        if (e.target.value < 0) {
            e.target.value = 0;
        }
    });

    // إظهار/إخفاء حقل رقم المرجع
    const paymentMethodSelect = document.getElementById('payment_method');
    const referenceNumberInput = document.getElementById('reference_number');
    
    paymentMethodSelect.addEventListener('change', function() {
        if (this.value === 'cash') {
            referenceNumberInput.parentElement.style.display = 'none';
            referenceNumberInput.value = '';
        } else {
            referenceNumberInput.parentElement.style.display = 'block';
        }
    });
});
</script>
@endpush
@endsection 