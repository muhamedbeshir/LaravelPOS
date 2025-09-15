@extends('layouts.app')

@section('title', 'إدارة الرواتب')

@section('content')
<div class="container-fluid">
    <!-- مكان لعرض رسائل النجاح والخطأ -->
    <div id="alert-container" class="mb-3"></div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            إدارة الرواتب
                        </h5>
                        <div>
                            <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-list me-1"></i>
                                قائمة الموظفين
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- أدوات التصفية -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="jobTitleFilter" class="form-label">المسمى الوظيفي</label>
                                <select class="form-control" id="jobTitleFilter">
                                    <option value="">الكل</option>
                                    @foreach($jobTitles as $jobTitle)
                                    <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="salaryStatusFilter" class="form-label">حالة الراتب</label>
                                <select class="form-control" id="salaryStatusFilter">
                                    <option value="">الكل</option>
                                    <option value="paid">تم الدفع</option>
                                    <option value="unpaid">لم يتم الدفع</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="monthFilter" class="form-label">الشهر</label>
                                <input type="month" class="form-control" id="monthFilter" value="{{ date('Y-m') }}">
                            </div>
                        </div>
                        <input type="hidden" name="salary_display_frequency" id="salaryDisplayFrequency" value="{{ $settings->salary_display_frequency ?? 'monthly' }}">
                        <input type="hidden" name="next_payment_date" id="nextPaymentDate" value="{{ $settings->next_payment_date ?? '' }}">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search" class="form-label">بحث</label>
                                <input type="text" class="form-control" id="search" placeholder="اسم الموظف أو الرقم">
                            </div>
                        </div>
                    </div>

                    <!-- إحصائيات سريعة -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">إجمالي الرواتب</h6>
                                            <h3 class="mb-0" id="totalSalaries">0</h3>
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
                                            <h6 class="mb-0">تم دفع الراتب</h6>
                                            <h3 class="mb-0" id="paidEmployees">0</h3>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">لم يتم دفع الراتب</h6>
                                            <h3 class="mb-0" id="unpaidEmployees">0</h3>
                                        </div>
                                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">نسبة الدفع</h6>
                                            <h3 class="mb-0" id="paymentRate">0%</h3>
                                        </div>
                                        <i class="fas fa-percentage fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- جدول الرواتب -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">قائمة الرواتب</h6>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm" id="paySelectedSalaries">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        دفع الرواتب المحددة
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm text-white" id="exportToExcel">
                                        <i class="fas fa-file-excel me-1"></i>
                                        تصدير إلى Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="salariesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                                </div>
                                            </th>
                                            <th>الموظف</th>
                                            <th>الوظيفة</th>
                                            <th>الراتب الأساسي</th>
                                            <th>الخصومات</th>
                                            <th>المكافآت</th>
                                            <th>تاريخ الدفعة التالية</th>
                                            <th>حالة الراتب</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employees as $employee)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input employee-checkbox"
                                                           value="{{ $employee->id }}"
                                                           {{ $employee->current_month_payment ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                                    <strong>{{ $employee->name }}</strong>
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $employee->employee_number }}</small>
                                            </td>
                                            <td>{{ $employee->jobTitle->name }}</td>
                                            <td>{{ number_format($employee->salary, 2) }}</td>
                                            <td>{{ number_format($employee->deductions, 2) }}</td>
                                            <td>{{ number_format($employee->bonuses, 2) }}</td>
                                            <td>{{ $employee->next_payment_date ? $employee->next_payment_date->format('Y-m-d') : 'غير محدد' }}</td>
                                            <td>
                                                @if($employee->current_month_payment)
                                                <span class="badge bg-success">تم الدفع</span>
                                                @else
                                                <span class="badge bg-danger">لم يتم الدفع</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    @if(!$employee->current_month_payment)
                                                    <button type="button" class="btn btn-sm btn-success pay-salary"
                                                            data-employee-id="{{ $employee->id }}"
                                                            data-employee-name="{{ $employee->name }}"
                                                            data-salary="{{ $employee->net_salary }}">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-info text-white show-history"
                                                            data-employee-id="{{ $employee->id }}"
                                                            data-employee-name="{{ $employee->name }}">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
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

<!-- Modal دفع الراتب -->
<div class="modal fade" id="paySalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    دفع راتب الموظف
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="salaryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الموظف</label>
                        <input type="text" class="form-control form-control-lg" id="paymentEmployeeName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">صافي الراتب</label>
                        <div class="input-group">
                        <input type="text" class="form-control" id="paymentNetSalary" readonly>
                            <span class="input-group-text">ج.م</span>
                        </div>
                        <input type="hidden" id="actualSalaryAmount">
                    </div>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                        <select class="form-select" id="paymentMethod" required>
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
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        حفظ الدفعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal سجل المدفوعات -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    سجل المدفوعات - <span id="historyEmployeeName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="paymentHistoryTable">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>رقم المرجع</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('/assets/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/buttons.bootstrap5.min.css') }}">
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
<script src="{{ asset('/assets/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('/assets/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('/assets/jszip.min.js') }}"></script>
<script src="{{ asset('/assets/buttons.html5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة جدول الرواتب
    const table = $('#salariesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // تحديد/إلغاء تحديد جميع الموظفين
    $('#selectAll').on('change', function() {
        $('.employee-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
        updateSelectedCount();
    });

    // تحديث عدد الموظفين المحددين
    $('.employee-checkbox').on('change', function() {
        updateSelectedCount();
        if (!$(this).prop('checked')) {
            $('#selectAll').prop('checked', false);
        }
    });

    // تحديث عدد الموظفين المحددين
    function updateSelectedCount() {
        const count = $('.employee-checkbox:checked').length;
        $('#paySelectedSalaries').text(`دفع الرواتب المحددة (${count})`);
    }

    // تصدير البيانات إلى Excel
    $('#exportToExcel').on('click', function() {
        const buttons = $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'excel',
                    text: 'تصدير إلى Excel',
                    title: 'تقرير الرواتب',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7]
                    }
                }
            ]
        });

        buttons.container().appendTo($('#exportToExcel'));
        table.button(0).trigger();
    });

    // تحديث البيانات عند تغيير الفلاتر
    $('#jobTitleFilter, #salaryStatusFilter, #monthFilter, #search').on('change keyup', function() {
        updateData();
    });

    // دالة تحديث البيانات
    function updateData() {
        const jobTitleId = $('#jobTitleFilter').val();
        const salaryStatus = $('#salaryStatusFilter').val();
        const month = $('#monthFilter').val();
        const search = $('#search').val();
        const salaryDisplayFrequency = $('#salaryDisplayFrequency').val();

        $.ajax({
            url: '{{ route("employees.salaries.data") }}',
            method: 'GET',
            data: {
                job_title_id: jobTitleId,
                salary_status: salaryStatus,
                month: month,
                search: search,
                salary_display_frequency: salaryDisplayFrequency
            },
            beforeSend: function() {
                // إظهار مؤشر التحميل
                $('#salariesTable tbody').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
            },
            success: function(response) {
                // تحديث الإحصائيات
                $('#totalSalaries').text(response.totalSalaries.toLocaleString('en-US'));
                $('#paidEmployees').text(response.paidEmployees);
                $('#unpaidEmployees').text(response.unpaidEmployees);
                $('#paymentRate').text(response.paymentRate + '%');

                // تحديث وضع عرض الرواتب وتاريخ الدفعة التالية
                const salaryModeText = response.salary_display_frequency === 'monthly' ? 'شهري' : 'أسبوعي';
                // The following lines are removed as per the edit hint
                // $('#currentSalaryMode').text(salaryModeText);
                // $('#nextPaymentDateDisplay').text(response.next_payment_date || 'غير محدد');

                // تحديث الجدول
                table.clear();
                response.employees.forEach(function(employee) {
                    table.row.add([
                        `<div class="form-check">
                            <input type="checkbox" class="form-check-input employee-checkbox"
                                   value="${employee.id}"
                                   ${employee.current_month_payment ? 'disabled' : ''}>
                        </div>`,
                        `<a href="/employees/${employee.id}" class="text-decoration-none">
                            <strong>${employee.name}</strong>
                        </a>
                        <br>
                        <small class="text-muted">${employee.employee_number}</small>`,
                        employee.job_title.name,
                        Number(employee.salary).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                        Number(employee.deductions).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                        Number(employee.bonuses).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                        employee.next_payment_date ? moment(employee.next_payment_date).format('YYYY-MM-DD') : 'غير محدد',
                        employee.current_month_payment ?
                            '<span class="badge bg-success">تم الدفع</span>' :
                            '<span class="badge bg-danger">لم يتم الدفع</span>',
                        `<div class="btn-group">
                            ${!employee.current_month_payment ?
                                `<button type="button" class="btn btn-sm btn-success pay-salary"
                                         data-employee-id="${employee.id}"
                                         data-employee-name="${employee.name}"
                                         data-salary="${employee.net_salary}">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>` : ''}
                            <button type="button" class="btn btn-sm btn-info text-white show-history"
                                    data-employee-id="${employee.id}"
                                    data-employee-name="${employee.name}">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>`
                    ]);
                });
                table.draw();

                // تحديث عدد الموظفين المحددين
                updateSelectedCount();
            },
            error: function(xhr) {
                console.error('Error:', xhr);
            }
        });
    }

    // دفع الراتب
    const paySalaryModal = new bootstrap.Modal(document.getElementById('paySalaryModal'));
    let selectedEmployeeId = null;

    $(document).on('click', '.pay-salary', function() {
        selectedEmployeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        const salary = $(this).data('salary');

        console.log('Original salary value:', salary, typeof salary);

        // تأكد من أن القيمة رقمية
        const numericSalary = parseFloat(salary);
        console.log('Parsed salary value:', numericSalary, typeof numericSalary);

        if (isNaN(numericSalary)) {
            showAlert('حدث خطأ في استرجاع مبلغ الراتب', 'danger');
            return;
        }

        $('#paymentEmployeeName').val(employeeName);
        $('#paymentNetSalary').val(Number(numericSalary).toLocaleString('en-US', { minimumFractionDigits: 2 }));
        $('#actualSalaryAmount').val(numericSalary);

        paySalaryModal.show();
    });

    $('#salaryForm').on('submit', function(e) {
        e.preventDefault();

        const paymentMethod = $('#paymentMethod').val();
        const referenceNumber = $('#referenceNumber').val();
        const notes = $('#notes').val();
        // استخدام القيمة المخزنة في الحقل المخفي
        const amount = parseFloat($('#actualSalaryAmount').val());

        // التحقق من صحة المبلغ
        if (isNaN(amount) || amount <= 0) {
            showAlert('مبلغ الراتب غير صحيح، يرجى المحاولة مرة أخرى', 'danger');
            console.error('Invalid amount value:', $('#actualSalaryAmount').val());
            return;
        }

        console.log('Submitting salary payment:', {
            employee_id: selectedEmployeeId,
            amount: amount,
            payment_method: paymentMethod,
            reference_number: referenceNumber
        });

        $.ajax({
            url: `/employees/${selectedEmployeeId}/pay-salary`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                amount: amount,
                payment_method: paymentMethod,
                reference_number: referenceNumber,
                notes: notes
            },
            beforeSend: function() {
                // تعطيل زر الحفظ
                $('#salaryForm button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...');
            },
            success: function(response) {
                console.log('Payment success response:', response);
                if (response.success) {
                    paySalaryModal.hide();
                    updateData();
                    showAlert('تم دفع الراتب بنجاح', 'success');
                } else {
                    showAlert('حدث خطأ أثناء حفظ الدفعة: ' + (response.message || ''), 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment error:', {xhr, status, error});

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'الرجاء تصحيح الأخطاء التالية:<br>';

                    if (errors) {
                    Object.keys(errors).forEach(key => {
                            errorMessage += `${errors[key]}<br>`;
                    });
                    } else {
                        errorMessage += 'خطأ في التحقق من البيانات';
                    }

                    showAlert(errorMessage, 'danger');
                } else {
                    showAlert('حدث خطأ أثناء حفظ الدفعة: ' + error, 'danger');
                }
            },
            complete: function() {
                // إعادة تمكين زر الحفظ
                $('#salaryForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save me-1"></i> حفظ الدفعة');
            }
        });
    });

    // عرض سجل المدفوعات
    const paymentHistoryModal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
    const paymentHistoryTable = $('#paymentHistoryTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[0, 'desc']],
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    $(document).on('click', '.show-history', function() {
        const employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');

        // عرض اسم الموظف في عنوان النافذة
        $('#historyEmployeeName').text(employeeName);

        $.ajax({
            url: `/employees/${employeeId}/salary-history`,
            method: 'GET',
            beforeSend: function() {
                // إظهار مؤشر التحميل
                paymentHistoryTable.clear().draw();
                $('#paymentHistoryTable tbody').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
                paymentHistoryModal.show();
            },
            success: function(response) {
                paymentHistoryTable.clear();
                if (response.payments && response.payments.length > 0) {
                response.payments.forEach(function(payment) {
                    paymentHistoryTable.row.add([
                            new Date(payment.payment_date).toLocaleDateString('en-US'), // تنسيق ميلادي
                            Number(payment.amount).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                        payment.payment_method_text,
                        payment.reference_number || '-',
                        payment.notes || '-'
                    ]);
                });
                } else {
                    // لا توجد مدفوعات
                    paymentHistoryTable.row.add(['', 'لا توجد مدفوعات سابقة', '', '', '']);
                }
                paymentHistoryTable.draw();
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                paymentHistoryTable.clear();
                paymentHistoryTable.row.add(['', 'حدث خطأ أثناء تحميل سجل المدفوعات', '', '', '']);
                paymentHistoryTable.draw();
                showAlert('حدث خطأ أثناء تحميل سجل المدفوعات', 'danger');
            }
        });
    });

    // دفع الرواتب المحددة
    $('#paySelectedSalaries').on('click', function() {
        const selectedEmployees = $('.employee-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedEmployees.length === 0) {
            showAlert('الرجاء تحديد موظف واحد على الأقل', 'warning');
            return;
        }

        if (confirm(`هل أنت متأكد من دفع الرواتب لـ ${selectedEmployees.length} موظف؟`)) {
            $.ajax({
                url: '{{ route("employees.pay-multiple-salaries") }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    employee_ids: selectedEmployees,
                    payment_method: 'cash',
                    notes: 'دفع راتب جماعي'
                },
                beforeSend: function() {
                    // تعطيل زر الدفع
                    $('#paySelectedSalaries').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الدفع...');
                },
                success: function(response) {
                    if (response.success) {
                        updateData();
                        showAlert(response.message, 'success');
                    } else {
                        showAlert('حدث خطأ أثناء دفع الرواتب: ' + (response.message || ''), 'danger');
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    let errorMsg = 'حدث خطأ أثناء دفع الرواتب';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ': ' + xhr.responseJSON.message;
                    }
                    showAlert(errorMsg, 'danger');
                },
                complete: function() {
                    // إعادة تمكين زر الدفع
                    $('#paySelectedSalaries').prop('disabled', false).text(`دفع الرواتب المحددة (${selectedEmployees.length})`);
                }
            });
        }
    });

    // عرض رسالة تنبيه
    function showAlert(message, type) {
        const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`);

        // إضافة التنبيه أعلى الصفحة
        $('#alert-container').append(alertDiv);

        // التمرير إلى أعلى الصفحة
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // إخفاء التنبيه بعد 5 ثواني
        setTimeout(function() {
            alertDiv.alert('close');
        }, 5000);
    }

    // تحديث البيانات عند تحميل الصفحة
    updateData();
});
</script>
@endpush
@endsection 