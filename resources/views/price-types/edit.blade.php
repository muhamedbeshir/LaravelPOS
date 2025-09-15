@extends('layouts.app')

@section('title', 'تعديل نوع السعر')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold">تعديل نوع السعر: {{ $priceType->name }}</h5>
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
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                
                    <form action="{{ route('price-types.update', $priceType) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label required">الاسم</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" name="name" value="{{ old('name', $priceType->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <input type="hidden" id="code" name="code" value="{{ old('code', $priceType->code) }}">
                        
                        <div class="mb-3">
                            <label for="sort_order" class="form-label required">ترتيب العرض</label>
                            <input type="number" min="1" class="form-control @error('sort_order') is-invalid @enderror" 
                                id="sort_order" name="sort_order" value="{{ old('sort_order', $priceType->sort_order) }}" required>
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default" 
                                    {{ old('is_default', $priceType->is_default) ? 'checked' : '' }} value="on">
                                <label class="form-check-label" for="is_default">
                                    تعيين كنوع سعر افتراضي
                                </label>
                            </div>
                            <small class="text-muted">
                                يجب أن يكون هناك نوع سعر افتراضي واحد على الأقل في النظام. لا يمكن إلغاء تحديد هذا الخيار إذا كان هذا هو النوع الافتراضي الوحيد.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                    {{ old('is_active', $priceType->is_active) ? 'checked' : '' }} value="on"
                                    >
                                <label class="form-check-label" for="is_active">
                                    نشط
                                </label>
                            </div>
                            <small class="text-muted">
                                لا يمكن تعطيل نوع السعر الافتراضي. أنواع الأسعار غير النشطة لن تظهر للمستخدمين.
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ التغييرات
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
                    <h5 class="mb-0 fw-bold">معلومات النوع</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>الاسم هو ما سيظهر للمستخدمين في واجهة النظام.</li>
                        <li>يستخدم الترتيب لعرض الأسعار مرتبة في القوائم والتقارير.</li>
                        <li>نوع السعر الافتراضي هو الذي سيتم استخدامه في حالة عدم تحديد نوع السعر.</li>
                        <li>لا يمكن تعطيل نوع السعر الافتراضي.</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0 fw-bold">الاستخدام</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>عدد الأسعار التي تستخدم هذا النوع:</strong>
                        <span class="badge bg-info">{{ $priceType->productUnitPrices()->count() }}</span>
                    </div>
                    <p class="mb-0 text-muted small">
                        في حالة وجود أسعار تستخدم هذا النوع، يجب تغيير هذه الأسعار أولاً قبل محاولة حذف النوع.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // توليد الكود تلقائيًا بناءً على الاسم فقط إذا كان الاسم تغير
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');
        const originalName = "{{ $priceType->name }}";
        
        nameInput.addEventListener('input', function() {
            if (this.value !== originalName) {
                let code = this.value.toLowerCase()
                    .replace(/\s+/g, '_') // استبدال المسافات بشرطة سفلية
                    .replace(/[^\w_]/g, '') // إزالة أي أحرف غير مسموح بها
                    .replace(/_{2,}/g, '_'); // منع تكرار الشرطات السفلية
                
                codeInput.value = code;
            }
        });
    });
</script>
@endsection 