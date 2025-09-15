@extends('layouts.app')

@section('title', 'إضافة نوع سعر جديد')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold">إضافة نوع سعر جديد</h5>
                <a href="{{ route('price-types.index') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">بيانات نوع السعر</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('price-types.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label required">الاسم</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <input type="hidden" id="code" name="code" value="{{ old('code') }}">
                        
                        <div class="mb-3">
                            <label for="sort_order" class="form-label required">ترتيب العرض</label>
                            <input type="number" min="1" class="form-control @error('sort_order') is-invalid @enderror" 
                                id="sort_order" name="sort_order" value="{{ old('sort_order', 1) }}" required>
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default" 
                                    {{ old('is_default') ? 'checked' : '' }} value="on">
                                <label class="form-check-label" for="is_default">
                                    تعيين كنوع سعر افتراضي
                                </label>
                            </div>
                            <small class="text-muted">
                                يجب أن يكون هناك نوع سعر افتراضي واحد على الأقل في النظام. تحديد هذا الخيار سيلغي الإعداد الافتراضي من الأنواع الأخرى.
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ
                            </button>
                            <a href="{{ route('price-types.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0 fw-bold">معلومات</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>الاسم هو ما سيظهر للمستخدمين في واجهة النظام.</li>
                        <li>يستخدم الترتيب لعرض الأسعار مرتبة في القوائم والتقارير.</li>
                        <li>نوع السعر الافتراضي هو الذي سيتم استخدامه في حالة عدم تحديد نوع السعر.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // توليد الكود تلقائيًا بناءً على الاسم
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');
        
        // Set initial code if name has a value
        if (nameInput.value) {
            generateCode(nameInput.value);
        }
        
        nameInput.addEventListener('input', function() {
            generateCode(this.value);
        });
        
        function generateCode(name) {
            let code = name.toLowerCase()
                .replace(/\s+/g, '_') // استبدال المسافات بشرطة سفلية
                .replace(/[^\w_]/g, '') // إزالة أي أحرف غير مسموح بها
                .replace(/_{2,}/g, '_'); // منع تكرار الشرطات السفلية
            
            codeInput.value = code;
        }
        
        // Ensure code is generated on form submission
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (!codeInput.value && nameInput.value) {
                generateCode(nameInput.value);
            }
        });
    });
</script>
@endsection 