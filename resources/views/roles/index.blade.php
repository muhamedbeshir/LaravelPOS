@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2 class="fw-bold mb-3">
                <i class="fas fa-user-tag text-primary me-2"></i>إدارة الأدوار والصلاحيات
            </h2>
            <p class="text-muted">قم بإنشاء وتعديل الأدوار المخصصة وتحديد الصلاحيات لكل دور</p>
        </div>
        <div class="col-md-4 text-md-end align-self-center">
            @can('manage-users')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> إنشاء دور جديد
            </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 py-1">
                <i class="fas fa-list me-1 text-primary"></i> قائمة الأدوار
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الدور</th>
                            <th>عدد المستخدمين</th>
                            <th>عدد الصلاحيات</th>
                            <th>الخيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $role->name }}</span>
                                    @if($role->name === 'admin')
                                        <span class="badge bg-gradient-primary ms-1">مسؤول النظام</span>
                                    @endif
                                </td>
                                <td>{{ $role->users->count() }}</td>
                                <td>
                                    <span class="badge bg-gradient-info">{{ $role->permissions->count() }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary {{ $role->name === 'admin' ? 'disabled' : '' }}">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        @if($role->name !== 'admin')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger confirm-delete" {{ $role->users->count() > 0 ? 'disabled' : '' }}>
                                                    <i class="fas fa-trash-alt"></i> حذف
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-exclamation-circle text-warning fs-1 mb-3"></i>
                                    <p>لا توجد أدوار مضافة حتى الآن</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تأكيد حذف الدور
        const deleteButtons = document.querySelectorAll('.confirm-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                
                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    text: "سيتم حذف هذا الدور نهائياً!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'نعم، قم بالحذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush 