@extends('layouts.app')

@section('title', 'الحضور والانصراف')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            سجل الحضور والانصراف
                        </h5>
                        <div>
                            <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-users me-1"></i>
                                قائمة الموظفين
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- أدوات التصفية -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="jobTitleFilter">تصفية حسب الوظيفة</label>
                                <select class="form-control" id="jobTitleFilter">
                                    <option value="">الكل</option>
                                    @foreach($jobTitles as $jobTitle)
                                    <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="dateFilter">تصفية حسب التاريخ</label>
                                <input type="date" class="form-control" id="dateFilter" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="searchFilter">بحث</label>
                                <input type="text" class="form-control" id="searchFilter" placeholder="اسم الموظف، الرقم...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="attendanceTable">
                            <thead class="table-light">
                                <tr>
                                    <th>الموظف</th>
                                    <th>الوظيفة</th>
                                    <th>وقت الحضور</th>
                                    <th>وقت الانصراف</th>
                                    <th>عدد الساعات</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $employee)
                                <tr data-job-title="{{ $employee->job_title_id }}" 
                                    data-search="{{ $employee->name }} {{ $employee->employee_number }}">
                                    <td>
                                        <strong>{{ $employee->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $employee->employee_number }}</small>
                                    </td>
                                    <td>{{ $employee->jobTitle->name }}</td>
                                    <td>
                                        @php
                                            $attendance = $employee->getTodayAttendance();
                                        @endphp
                                        {{ $attendance ? $attendance->check_in->format('H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        {{ $attendance && $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        {{ $attendance && $attendance->check_out ? $attendance->getDuration() . ' ساعة' : '-' }}
                                    </td>
                                    <td>
                                        @if(!$attendance)
                                            <span class="badge bg-danger">غائب</span>
                                        @else
                                            <span class="badge bg-success">حاضر</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$attendance)
                                            <button type="button" 
                                                    class="btn btn-sm btn-success check-in-btn" 
                                                    data-employee-id="{{ $employee->id }}">
                                                <i class="fas fa-sign-in-alt"></i>
                                                تسجيل حضور
                                            </button>
                                        @elseif(!$attendance->check_out)
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning check-out-btn"
                                                    data-employee-id="{{ $employee->id }}">
                                                <i class="fas fa-sign-out-alt"></i>
                                                تسجيل انصراف
                                            </button>
                                        @else
                                            <span class="text-muted">تم تسجيل الانصراف</span>
                                        @endif
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

<!-- رسائل النجاح والفشل -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                <span id="successMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span id="errorMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
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
    .toast {
        min-width: 300px;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('/assets/dataTables.bootstrap5.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة عناصر Toast
    const successToast = new bootstrap.Toast(document.getElementById('successToast'));
    const errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
    
    // تهيئة جدول البيانات
    const table = $('#attendanceTable').DataTable({
        language: {
            url: '{{ asset('js/ar.json') }}'
        },
        order: [[2, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });

    // تسجيل الحضور
    $(document).on('click', '.check-in-btn', function() {
        const button = $(this);
        const employeeId = button.data('employee-id');
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري التسجيل...');
        
        $.ajax({
            url: `/employees/${employeeId}/check-in`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#successMessage').text(response.message);
                    successToast.show();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    $('#errorMessage').text(response.message);
                    errorToast.show();
                    button.prop('disabled', false)
                          .html('<i class="fas fa-sign-in-alt"></i> تسجيل حضور');
                }
            },
            error: function(xhr) {
                $('#errorMessage').text('حدث خطأ أثناء تسجيل الحضور');
                errorToast.show();
                button.prop('disabled', false)
                      .html('<i class="fas fa-sign-in-alt"></i> تسجيل حضور');
            }
        });
    });

    // تسجيل الانصراف
    $(document).on('click', '.check-out-btn', function() {
        const button = $(this);
        const employeeId = button.data('employee-id');
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري التسجيل...');
        
        $.ajax({
            url: `/employees/${employeeId}/check-out`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#successMessage').text(response.message);
                    successToast.show();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    $('#errorMessage').text(response.message);
                    errorToast.show();
                    button.prop('disabled', false)
                          .html('<i class="fas fa-sign-out-alt"></i> تسجيل انصراف');
                }
            },
            error: function(xhr) {
                $('#errorMessage').text('حدث خطأ أثناء تسجيل الانصراف');
                errorToast.show();
                button.prop('disabled', false)
                      .html('<i class="fas fa-sign-out-alt"></i> تسجيل انصراف');
            }
        });
    });

    // تصفية حسب الوظيفة
    $('#jobTitleFilter').on('change', function() {
        const jobTitleId = $(this).val();
        
        table.rows().every(function() {
            const row = $(this.node());
            if (!jobTitleId || row.data('job-title') === jobTitleId) {
                row.show();
            } else {
                row.hide();
            }
        });
        
        table.draw();
    });

    // تصفية حسب التاريخ
    $('#dateFilter').on('change', function() {
        // يمكن إضافة منطق التصفية حسب التاريخ هنا
        // سيتطلب ذلك تحديث البيانات من الخادم
    });

    // البحث
    $('#searchFilter').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>
@endpush
@endsection 