@extends('layouts.app')

@section('title', 'الموظفين')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الموظفين</h2>
        <div>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة موظف جديد
            </a>
            <a href="{{ route('employees.salaries.index') }}" class="btn btn-success">
                <i class="fas fa-dollar-sign me-1"></i>
                إدارة الرواتب
            </a>
            <a href="{{ route('employees.attendance.index') }}" class="btn btn-info text-white">
                <i class="fas fa-clock me-1"></i>
                الحضور والانصراف
            </a>
            <a href="{{ route('employees.reports') }}" class="btn btn-warning text-white">
                <i class="fas fa-chart-bar me-1"></i>
                التقارير
            </a>
            <a href="{{ route('employees.export') }}" class="btn btn-secondary">
                <i class="fas fa-file-export me-1"></i>
                تصدير الموظفين
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- أدوات التصفية -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nameFilter">البحث بالاسم</label>
                        <input type="text" class="form-control" id="nameFilter" placeholder="اسم الموظف أو الرقم">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="jobFilter">الوظيفة</label>
                        <select class="form-control" id="jobFilter">
                            <option value="">الكل</option>
                            @foreach($employees->pluck('jobTitle.name')->unique() as $job)
                            <option value="{{ $job }}">{{ $job }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="statusFilter">الحالة</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">الكل</option>
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="employeesTable">
                    <thead class="table-light">
                        <tr>
                            <th>الموظف</th>
                            <th>الوظيفة</th>
                            <th>الراتب</th>
                            <th>حالة الراتب</th>
                            <th>الحضور اليوم</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                        <tr>
                            <td>
                                <strong>{{ $employee->name }}</strong>
                                <br>
                                <small class="text-muted">{{ $employee->employee_number }}</small>
                            </td>
                            <td>{{ $employee->jobTitle->name }}</td>
                            <td>{{ number_format($employee->salary, 2) }}</td>
                            <td>
                                @php
                                    $paymentStatus = false;
                                    if ($salaryDisplayFrequency === 'weekly') {
                                        $startOfWeek = now()->startOfWeek();
                                        $endOfWeek = now()->endOfWeek();
                                        $paymentStatus = $employee->salaryPayments()->whereBetween('payment_date', [$startOfWeek, $endOfWeek])->exists();
                                    } else {
                                        $paymentStatus = $employee->getSalaryPaymentForMonth(now()->year, now()->month);
                                    }
                                @endphp
                                <span class="badge {{ $paymentStatus ? 'bg-success' : 'bg-danger' }}">
                                    {{ $paymentStatus ? 'تم الدفع' : 'لم يتم الدفع' }}
                                </span>
                            </td>
                            <td>
                                @if($employee->getTodayAttendance())
                                    <span class="badge bg-success">حاضر</span>
                                @else
                                    <span class="badge bg-danger">غير مسجل</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('employees.toggle-active', $employee) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $employee->is_active ? 'btn-success' : 'btn-danger' }}">
                                        @if($employee->is_active)
                                        <i class="fas fa-check-circle me-1"></i> نشط
                                        @else
                                        <i class="fas fa-times-circle me-1"></i> غير نشط
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info text-white show-employee-details" 
                                            data-employee-id="{{ $employee->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($employee->is_active)
                                    <button type="button" class="btn btn-sm btn-success pay-salary" 
                                            data-employee-id="{{ $employee->id }}"
                                            data-employee-name="{{ $employee->name }}"
                                            data-salary="{{ $employee->salary }}">
                                        <i class="fas fa-dollar-sign"></i>
                                    </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-danger delete-employee" 
                                            data-employee-id="{{ $employee->id }}"
                                            data-employee-name="{{ $employee->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">لا يوجد موظفين مضافين</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal تفاصيل الموظف -->
<div class="modal fade" id="employeeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الموظف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>البيانات الأساسية</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>اسم الموظف</th>
                                <td id="employeeName"></td>
                            </tr>
                            <tr>
                                <th>رقم الموظف</th>
                                <td id="employeeNumber"></td>
                            </tr>
                            <tr>
                                <th>الوظيفة</th>
                                <td id="employeeJob"></td>
                            </tr>
                            <tr>
                                <th>العنوان</th>
                                <td id="employeeAddress"></td>
                            </tr>
                            <tr>
                                <th>الرقم القومي</th>
                                <td id="employeeNationalId"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>بيانات الراتب</h5>
                        <table class="table table-striped">
                            <tr>
                                <th>الراتب الأساسي</th>
                                <td id="employeeSalary"></td>
                            </tr>
                            <tr>
                                <th>حالة الراتب ({{ $salaryDisplayFrequency === 'weekly' ? 'أسبوعي' : 'شهري' }})</th>
                                <td id="employeeSalaryStatus"></td>
                            </tr>
                            <tr>
                                <th>تاريخ آخر دفعة</th>
                                <td id="lastPaymentDate"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>سجل الحضور والانصراف</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>وقت الحضور</th>
                                    <th>وقت الانصراف</th>
                                    <th>عدد الساعات</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal دفع الراتب -->
<div class="modal fade" id="paySalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">دفع راتب الموظف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="salaryForm">
                    <div class="mb-3">
                        <label class="form-label">الموظف</label>
                        <input type="text" class="form-control" id="paymentEmployeeName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الراتب الأساسي</label>
                        <input type="text" class="form-control" id="paymentBaseSalary" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">المبلغ المراد دفعه</label>
                        <input type="number" class="form-control" id="paymentAmount" required min="0.01" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">طريقة الدفع</label>
                        <select class="form-control" id="paymentMethod" required>
                            <option value="cash">نقداً</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="check">شيك</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="referenceNumber" class="form-label">رقم المرجع</label>
                        <input type="text" class="form-control" id="referenceNumber">
                    </div>
                    <div class="mb-3">
                        <label for="paymentNotes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="paymentNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveSalaryPayment">
                    <i class="fas fa-save me-1"></i>
                    حفظ الدفعة
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تأكيد الحذف -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد حذف الموظف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف الموظف: <strong id="deleteEmployeeName"></strong>؟</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    هذا الإجراء لا يمكن التراجع عنه
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i>
                    تأكيد الحذف
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('/assets/dataTables.bootstrap5.min.css') }}">
<style>
    .badge {
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/assets/dataTables.bootstrap5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة DataTable
    const table = $('#employeesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // عرض تفاصيل الموظف
    const employeeDetailsModal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
    
    $('.show-employee-details').on('click', function() {
        const employeeId = $(this).data('employee-id');
        
        $.ajax({
            url: '{{ route("employees.show", ["employee" => "REPLACE_ID"]) }}'.replace('REPLACE_ID', employeeId),
            method: 'GET',
            success: function(data) {
                const employee = data.employee;
                
                // تحديث البيانات الأساسية
                $('#employeeName').text(employee.name);
                $('#employeeNumber').text(employee.employee_number);
                $('#employeeJob').text(employee.job_title.name);
                $('#employeeAddress').text(employee.address || '-');
                $('#employeeNationalId').text(employee.national_id || '-');
                
                // تحديث بيانات الراتب
                $('#employeeSalary').text(Number(employee.salary).toLocaleString('ar-SA', { minimumFractionDigits: 2 }));
                $('#employeeSalaryStatus').html(`
                    <span class="badge ${data.currentMonthPayment ? 'bg-success' : 'bg-danger'}">
                        ${data.currentMonthPayment ? 'تم الدفع' : 'لم يتم الدفع'}
                    </span>
                `);
                
                // تحديث سجل الحضور
                const attendanceTable = $('#attendanceTable tbody');
                attendanceTable.empty();
                
                employee.attendance_records.forEach(record => {
                    attendanceTable.append(`
                        <tr>
                            <td>${new Date(record.check_in).toLocaleDateString('ar-SA')}</td>
                            <td>${new Date(record.check_in).toLocaleTimeString('ar-SA')}</td>
                            <td>${record.check_out ? new Date(record.check_out).toLocaleTimeString('ar-SA') : '-'}</td>
                            <td>${record.duration || '-'}</td>
                        </tr>
                    `);
                });
                
                employeeDetailsModal.show();
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحميل تفاصيل الموظف');
            }
        });
    });

    // دفع الراتب
    const paySalaryModal = new bootstrap.Modal(document.getElementById('paySalaryModal'));
    let selectedEmployeeId = null;
    
    $('.pay-salary').on('click', function() {
        selectedEmployeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        const salary = $(this).data('salary');
        
        $('#paymentEmployeeName').val(employeeName);
        $('#paymentBaseSalary').val(Number(salary).toLocaleString('ar-SA', { minimumFractionDigits: 2 }));
        $('#paymentAmount').val(salary).attr('max', salary);
        
        paySalaryModal.show();
    });
    
    $('#saveSalaryPayment').on('click', function() {
        const amount = $('#paymentAmount').val();
        const paymentMethod = $('#paymentMethod').val();
        const referenceNumber = $('#referenceNumber').val();
        const notes = $('#paymentNotes').val();
        
        if (!amount || amount <= 0) {
            alert('الرجاء إدخال مبلغ صحيح');
            return;
        }
        
        $.ajax({
            url: '{{ route("employees.pay-salary", ["employee" => "REPLACE_ID"]) }}'.replace('REPLACE_ID', selectedEmployeeId),
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                amount: amount,
                payment_method: paymentMethod,
                reference_number: referenceNumber,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    paySalaryModal.hide();
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حفظ الدفعة');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'الرجاء تصحيح الأخطاء التالية:\n';
                    Object.keys(errors).forEach(key => {
                        errorMessage += `${errors[key]}\n`;
                    });
                    alert(errorMessage);
                } else {
                    alert('حدث خطأ أثناء حفظ الدفعة');
                }
            }
        });
    });

    // حذف الموظف
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    let employeeToDelete = null;
    
    $('.delete-employee').on('click', function() {
        employeeToDelete = $(this).data('employee-id');
        $('#deleteEmployeeName').text($(this).data('employee-name'));
        deleteModal.show();
    });
    
    $('#confirmDelete').on('click', function() {
        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route("employees.destroy", ["employee" => "REPLACE_ID"]) }}'.replace('REPLACE_ID', employeeToDelete)
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': $('meta[name="csrf-token"]').attr('content')
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_method',
            'value': 'DELETE'
        }));
        
        form.appendTo('body').submit();
    });

    // تصفية البيانات
    function applyFilters() {
        const nameFilter = $('#nameFilter').val().toLowerCase();
        const jobFilter = $('#jobFilter').val();
        const statusFilter = $('#statusFilter').val();
        
        table.rows().every(function() {
            const row = this.node();
            const name = $(row).find('td:first').text().toLowerCase();
            const job = $(row).find('td:nth-child(2)').text();
            const isActive = $(row).find('.btn-success').length > 0;
            
            let showRow = true;
            
            if (nameFilter && !name.includes(nameFilter)) {
                showRow = false;
            }
            
            if (jobFilter && job !== jobFilter) {
                showRow = false;
            }
            
            if (statusFilter !== '') {
                const status = statusFilter === '1';
                if (isActive !== status) {
                    showRow = false;
                }
            }
            
            this.nodes().to$().toggle(showRow);
        });
        
        table.draw();
    }

    // تطبيق الفلاتر عند التغيير
    $('#nameFilter, #jobFilter, #statusFilter').on('change keyup', applyFilters);
});
</script>
@endpush
@endsection 