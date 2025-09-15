@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-2 mb-sm-0"><i class="fas fa-database me-2"></i>{{ __('إدارة النسخ الاحتياطي') }}</h2>
        <div>
            <form action="{{ route('backups.create') }}" method="POST" class="d-inline me-2" onsubmit="return confirmBackupCreation(this);">
                @csrf
                <button type="submit" class="btn btn-success" id="create-backup-btn">
                    <span class="button-icon"><i class="fas fa-plus-circle me-1"></i></span>
                    <span class="button-text">{{ __('إنشاء نسخة احتياطية جديدة') }}</span>
                    <span class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                </button>
            </form>
            {{-- Add Upload Form --}}
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadBackupModal">
                <i class="fas fa-upload me-1"></i> {{ __('رفع نسخة احتياطية') }}
            </button>
        </div>
    </div>

    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-1"></i> {{ __('تنبيه هام!') }}</h4>
        <p>{{ __('عملية الاستعادة ستقوم بحذف جميع البيانات الحالية في قاعدة البيانات واستبدالها بالبيانات الموجودة في ملف النسخة الاحتياطية المختار. يرجى التأكد تمامًا قبل المتابعة.') }}</p>
        <hr>
        <p class="mb-0">{{ __('يوصى بأخذ نسخة احتياطية جديدة قبل القيام بأي عملية استعادة.') }}</p>
    </div>
    
    @include('partials.flash_messages')

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('النسخ الاحتياطية المتاحة') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('تاريخ النسخ') }}</th>
                            <th>{{ __('اسم الملف') }}</th>
                            <th>{{ __('الحجم') }}</th>
                            <th class="text-center">{{ __('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $index => $backup)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $backup->date()->format('Y-m-d H:i:s') }} ({{ $backup->date()->diffForHumans() }})</td>
                            <td>{{ basename($backup->path()) }}</td>
                            <td>{{ number_format($backup->sizeInBytes() / 1024 / 1024, 2) }} MB</td>
                            <td class="text-center">
                                {{-- Download --}}
                                <a href="{{ route('backups.download', basename($backup->path())) }}" class="btn btn-sm btn-info me-1" title="{{ __('تنزيل') }}">
                                    <i class="fas fa-download"></i>
                                </a>

                                {{-- Restore --}}
                                <button type="button" class="btn btn-sm btn-warning me-1 restore-button" title="{{ __('استعادة') }}"
                                        data-url="{{ route('backups.restore', basename($backup->path())) }}">
                                    <i class="fas fa-undo"></i>
                                </button>

                                {{-- Delete --}}
                                <button type="button" class="btn btn-sm btn-danger delete-button" title="{{ __('حذف') }}"
                                        data-url="{{ route('backups.destroy', basename($backup->path())) }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">{{ __('لا توجد نسخ احتياطية متاحة.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Hidden forms for delete/restore --}}
<form id="delete-backup-form" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<form id="restore-backup-form" action="" method="POST" style="display: none;">
    @csrf
</form>

{{-- Modal for Uploading Backup --}}
<div class="modal fade" id="uploadBackupModal" tabindex="-1" aria-labelledby="uploadBackupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('backups.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadBackupModalLabel"><i class="fas fa-upload me-2"></i>{{ __('رفع ملف نسخة احتياطية') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('إغلاق') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        {{ __('يرجى التأكد من أن الملف هو نسخة احتياطية صالحة تم إنشاؤها بواسطة هذا النظام (.zip).') }}
                    </div>
                    <div class="mb-3">
                        <label for="backup_file" class="form-label">{{ __('ملف النسخة الاحتياطية (.zip)') }} <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="backup_file" name="backup_file" accept=".zip" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('رفع الملف') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmBackupCreation(form) {
        const button = form.querySelector('#create-backup-btn');
        const buttonText = button.querySelector('.button-text');
        const buttonIcon = button.querySelector('.button-icon');
        const spinner = button.querySelector('.spinner-border');

        Swal.fire({
            title: '{{ __("إنشاء نسخة احتياطية؟") }}',
            html: `{!! __("سيتم إنشاء نسخة احتياطية لقاعدة البيانات الآن. قد تستغرق هذه العملية بعض الوقت بناءً على حجم البيانات.<br><strong class='text-warning'>يرجى عدم إغلاق هذه الصفحة أو التنقل بعيدًا حتى تكتمل العملية.</strong>") !!}`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("نعم، ابدأ الإنشاء!") }}',
            cancelButtonText: '{{ __("إلغاء") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button, show spinner, change text
                button.disabled = true;
                spinner.classList.remove('d-none');
                buttonIcon.classList.add('d-none'); // Hide original icon
                buttonText.textContent = '{{ __("جاري الإنشاء...") }}';
                
                // Submit the form after confirmation and UI update
                form.submit(); 
            }
        });

        // Prevent the default form submission initially
        return false; 
    }

    // Add event listeners for delete buttons
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', function() {
            confirmDelete(this.dataset.url);
        });
    });

    function confirmDelete(deleteUrl) {
        if (!deleteUrl) return;
        const button = document.querySelector(`.delete-button[data-url='${deleteUrl}']`); // Find the specific button

        Swal.fire({
            title: '{{ __("هل أنت متأكد؟") }}',
            text: '{{ __("سيتم حذف ملف النسخة الاحتياطية هذا نهائياً!") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '{{ __("نعم، قم بالحذف!") }}',
            cancelButtonText: '{{ __("إلغاء") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                if (button) {
                    button.disabled = true; // Disable the clicked button
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'; // Show spinner
                }
                const form = document.getElementById('delete-backup-form');
                form.action = deleteUrl;
                form.submit();
            }
        });
    }

    // Add event listeners for restore buttons
    document.querySelectorAll('.restore-button').forEach(button => {
        button.addEventListener('click', function() {
            confirmRestore(this.dataset.url);
        });
    });

    function confirmRestore(restoreUrl) {
        if (!restoreUrl) return;
        const button = document.querySelector(`.restore-button[data-url='${restoreUrl}']`); // Find the specific button

        Swal.fire({
            title: '{{ __("تأكيد عملية الاستعادة!") }}',
            html: `{!! __("<strong class='text-danger'>تحذير خطير:</strong><br>سيتم حذف <strong class='text-danger'>جميع البيانات الحالية</strong> (منتجات، مبيعات، مستخدمين، إلخ) واستبدالها بالبيانات من ملف النسخة الاحتياطية.<br><br><strong>هل أنت متأكد تماماً من رغبتك في المتابعة؟ لا يمكن التراجع عن هذه العملية.") !!}`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545', 
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("نعم، أفهم المخاطر وأريد الاستعادة!") }}',
            cancelButtonText: '{{ __("إلغاء") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show a loading indicator on the main Swal modal first
                Swal.fire({
                    title: '{{ __("جاري الاستعادة...") }}',
                    html: '{{ __("قد تستغرق هذه العملية بعض الوقت. يرجى عدم إغلاق الصفحة أو التنقل بعيدًا.") }}',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                if (button) {
                    button.disabled = true; // Disable the clicked button
                    // Optional: Change button icon/text if needed, though the Swal modal handles primary feedback
                }

                const form = document.getElementById('restore-backup-form');
                form.action = restoreUrl;
                form.submit();
            }
        });
    }
</script>
@endpush 