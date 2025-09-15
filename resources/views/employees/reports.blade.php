@extends('layouts.app')

@section('title', 'تقارير الموظفين')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            تقارير الموظفين
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
                                <label for="dateRangeFilter" class="form-label">الفترة الزمنية</label>
                                <select class="form-control" id="dateRangeFilter">
                                    <option value="today">اليوم</option>
                                    <option value="week">هذا الأسبوع</option>
                                    <option value="month" selected>هذا الشهر</option>
                                    <option value="year">هذا العام</option>
                                    <option value="custom">تحديد فترة مخصصة</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3" id="customDateRange" style="display: none;">
                            <div class="form-group">
                                <label for="startDate" class="form-label">من تاريخ</label>
                                <input type="date" class="form-control" id="startDate">
                            </div>
                        </div>
                        <div class="col-md-3" id="customDateRange2" style="display: none;">
                            <div class="form-group">
                                <label for="endDate" class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control" id="endDate">
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
                                            <h6 class="mb-0">إجمالي الموظفين</h6>
                                            <h3 class="mb-0" id="totalEmployees">0</h3>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
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
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">متوسط الراتب</h6>
                                            <h3 class="mb-0" id="averageSalary">0</h3>
                                        </div>
                                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">معدل الحضور</h6>
                                            <h3 class="mb-0" id="attendanceRate">0%</h3>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الرسوم البيانية -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">توزيع الموظفين حسب الوظيفة</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="employeesByJobTitle"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">معدل الحضور اليومي</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyAttendance"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">توزيع الرواتب</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salaryDistribution"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">مدفوعات الرواتب الشهرية</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlySalaryPayments"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- جدول تفصيلي -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">تفاصيل الموظفين</h6>
                                <button type="button" class="btn btn-success btn-sm" id="exportToExcel">
                                    <i class="fas fa-file-excel me-1"></i>
                                    تصدير إلى Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="employeesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الموظف</th>
                                            <th>الوظيفة</th>
                                            <th>الراتب</th>
                                            <th>أيام الحضور</th>
                                            <th>متوسط ساعات العمل</th>
                                            <th>إجمالي المدفوعات</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employees as $employee)
                                        <tr>
                                            <td>
                                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                                    {{ $employee->name }}
                                                </a>
                                                <br>
                                                <small class="text-muted">{{ $employee->employee_number }}</small>
                                            </td>
                                            <td>{{ $employee->jobTitle->name }}</td>
                                            <td>{{ number_format($employee->salary, 2) }}</td>
                                            <td>{{ $employee->attendanceRecords->count() }}</td>
                                            <td>{{ number_format($employee->averageWorkingHours(), 1) }}</td>
                                            <td>{{ number_format($employee->salaryPayments->sum('amount'), 2) }}</td>
                                            <td>
                                                <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $employee->is_active ? 'نشط' : 'غير نشط' }}
                                                </span>
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
    canvas {
        max-height: 300px;
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
<script src="{{ asset('/assets/chart.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة جدول البيانات
    const table = $('#employeesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
        },
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // تصدير البيانات إلى Excel
    $('#exportToExcel').on('click', function() {
        const buttons = $.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'excel',
                    text: 'تصدير إلى Excel',
                    title: 'تقرير الموظفين',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }
                }
            ]
        });

        buttons.container().appendTo($('#exportToExcel'));
        table.button(0).trigger();
    });

    // إظهار/إخفاء حقول التاريخ المخصص
    $('#dateRangeFilter').on('change', function() {
        const showCustom = $(this).val() === 'custom';
        $('#customDateRange, #customDateRange2').toggle(showCustom);
    });

    // تحديث البيانات عند تغيير الفلاتر
    $('#jobTitleFilter, #dateRangeFilter, #startDate, #endDate').on('change', function() {
        updateCharts();
    });

    // دالة تحديث الرسوم البيانية
    function updateCharts() {
        const jobTitleId = $('#jobTitleFilter').val();
        const dateRange = $('#dateRangeFilter').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        $.ajax({
            url: '{{ route("employees.reports.data") }}',
            method: 'GET',
            data: {
                job_title_id: jobTitleId,
                date_range: dateRange,
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                // تحديث الإحصائيات السريعة
                $('#totalEmployees').text(response.totalEmployees);
                $('#totalSalaries').text(response.totalSalaries.toLocaleString('ar-SA'));
                $('#averageSalary').text(response.averageSalary.toLocaleString('ar-SA'));
                $('#attendanceRate').text(response.attendanceRate + '%');

                // تحديث الرسوم البيانية
                updateEmployeesByJobTitleChart(response.employeesByJobTitle);
                updateDailyAttendanceChart(response.dailyAttendance);
                updateSalaryDistributionChart(response.salaryDistribution);
                updateMonthlySalaryPaymentsChart(response.monthlySalaryPayments);
            },
            error: function(xhr) {
                console.error('Error:', xhr);
            }
        });
    }

    // دالة تحديث رسم توزيع الموظفين حسب الوظيفة
    function updateEmployeesByJobTitleChart(data) {
        const ctx = document.getElementById('employeesByJobTitle').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
                        '#e74a3b', '#858796', '#5a5c69', '#2e59d9'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // دالة تحديث رسم معدل الحضور اليومي
    function updateDailyAttendanceChart(data) {
        const ctx = document.getElementById('dailyAttendance').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'عدد الحاضرين',
                    data: data.values,
                    borderColor: '#4e73df',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // دالة تحديث رسم توزيع الرواتب
    function updateSalaryDistributionChart(data) {
        const ctx = document.getElementById('salaryDistribution').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'عدد الموظفين',
                    data: data.values,
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // دالة تحديث رسم مدفوعات الرواتب الشهرية
    function updateMonthlySalaryPaymentsChart(data) {
        const ctx = document.getElementById('monthlySalaryPayments').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'إجمالي المدفوعات',
                    data: data.values,
                    borderColor: '#36b9cc',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // تحديث البيانات عند تحميل الصفحة
    updateCharts();
});
</script>
@endpush
@endsection 