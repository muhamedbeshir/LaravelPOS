@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2 class="fw-bold mb-3">
                <i class="fas fa-edit text-primary me-2"></i>تعديل الدور
            </h2>
            <p class="text-muted">قم بتعديل اسم الدور وتحديد الصلاحيات المناسبة له</p>
        </div>
        <div class="col-md-4 text-md-end align-self-center">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> الرجوع للقائمة
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 py-1">
                <i class="fas fa-user-tag me-1 text-primary"></i> تعديل الدور: {{ $role->name }}
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">اسم الدور <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                            id="name" name="name" value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h4 class="fw-bold mt-4 mb-3">الصلاحيات</h4>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    حدد الصلاحيات التي سيتمتع بها المستخدمون الذين سيتم تعيينهم لهذا الدور
                </div>

                <div class="row permissions-container">
                    @foreach($permissions as $group => $permissionList)
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <h5 class="mb-0 fw-bold flex-grow-1">{{ $group }}</h5>
                                        <div class="form-check">
                                            <input class="form-check-input group-selector" type="checkbox" data-group="{{ $group }}">
                                            <label class="form-check-label">تحديد الكل</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($permissionList as $permission)
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input permission-checkbox" 
                                                           type="checkbox" 
                                                           name="permissions[]" 
                                                           value="{{ $permission->name }}" 
                                                           id="perm_{{ $permission->id }}"
                                                           data-group="{{ $group }}"
                                                           {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        {{ $translations[$permission->name] ?? $permission->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="reset" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-redo me-1"></i> إعادة تعيين
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تفعيل زر "تحديد الكل" لكل مجموعة
        const groupSelectors = document.querySelectorAll('.group-selector');
        
        groupSelectors.forEach(selector => {
            selector.addEventListener('change', function() {
                const group = this.getAttribute('data-group');
                const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        });
        
        // تحديث حالة زر "تحديد الكل" عند تغيير أي صلاحية
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        
        permissionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const group = this.getAttribute('data-group');
                const groupCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
                const groupSelector = document.querySelector(`.group-selector[data-group="${group}"]`);
                
                let allChecked = true;
                groupCheckboxes.forEach(cb => {
                    if (!cb.checked) {
                        allChecked = false;
                    }
                });
                
                groupSelector.checked = allChecked;
            });
        });
        
        // تحديث حالة أزرار "تحديد الكل" عند تحميل الصفحة
        groupSelectors.forEach(selector => {
            const group = selector.getAttribute('data-group');
            const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            
            let allChecked = true;
            checkboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    allChecked = false;
                }
            });
            
            selector.checked = allChecked;
        });
    });
</script>
@endpush 