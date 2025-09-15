@extends('layouts.app')

@section('title', 'تعديل أسعار المنتج')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tags me-2"></i>
                            تعديل أسعار المنتج: {{ $product->name }}
                        </h5>
                        <div class="text-end">
                            @if($product->sku)
                                <small>كود المنتج: {{ $product->sku }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('products.update-prices', $product) }}" method="POST" id="pricesForm">
                        @csrf
                        @method('PUT')

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 200px;">
                                            <i class="fas fa-weight-hanging me-1"></i>
                                            الوحدة
                                        </th>
                                        @foreach($priceTypes as $priceType)
                                        <th class="text-center">
                                            <i class="fas fa-tag me-1"></i>
                                            {{ $priceType->name }}
                                            @if($priceType->is_default)
                                                <span class="badge bg-warning text-dark ms-1">افتراضي</span>
                                            @endif
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->productUnits as $productUnit)
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <strong>{{ $productUnit->unit->name }}</strong>
                                                    @if($productUnit->is_main_unit)
                                                        <span class="badge bg-primary ms-2">وحدة رئيسية</span>
                                                    @endif
                                                    <div class="small text-muted">
                                                        معامل التحويل: {{ $productUnit->conversion_factor }}
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="units[{{ $loop->index }}][id]" value="{{ $productUnit->id }}">
                                        </td>
                                        @foreach($priceTypes as $priceType)
                                        @php
                                            $existingPrice = $productUnit->prices->where('price_type_id', $priceType->id)->first();
                                            $inputName = "units[{$loop->parent->index}][prices][{$priceType->id}]";
                                        @endphp
                                        <td class="text-center">
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       class="form-control text-center price-input @error($inputName) is-invalid @enderror"
                                                       name="{{ $inputName }}" 
                                                       value="{{ old($inputName, $existingPrice ? $existingPrice->value : '') }}"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0.00"
                                                       data-unit-id="{{ $productUnit->id }}"
                                                       data-price-type-id="{{ $priceType->id }}">
                                                <span class="input-group-text">ج.م</span>
                                            </div>
                                            @error($inputName)
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($product->productUnits->isEmpty())
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <h6>لا توجد وحدات محددة لهذا المنتج</h6>
                                <p class="mb-0">يرجى إضافة وحدات للمنتج أولاً من صفحة تعديل المنتج</p>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm mt-2">
                                    <i class="fas fa-edit me-1"></i>تعديل المنتج
                                </a>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">
                                            <i class="fas fa-info-circle text-info me-1"></i>
                                            معلومات هامة
                                        </h6>
                                        <ul class="small mb-0">
                                            <li>يمكن ترك الأسعار فارغة إذا لم تكن مطلوبة</li>
                                            <li>الأسعار بالجنيه المصري</li>
                                            <li>سيتم حفظ تاريخ آخر تحديث تلقائياً</li>
                                            <li class="text-primary">إذا واجهت مشكلة، حاول إعادة تحميل الصفحة</li>
                                            <li class="text-primary">تأكد من وجود السعر الرئيسي على الأقل</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-outline-danger me-2" onclick="clearAllPrices()">
                                        <i class="fas fa-eraser me-1"></i>مسح الأسعار
                                    </button>
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-right me-1"></i>رجوع
                                    </a>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-info">
                                        <i class="fas fa-eye me-1"></i>عرض المنتج
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="saveBtn">
                                        <i class="fas fa-save me-1"></i>حفظ الأسعار
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add loading state to save button
    $('#pricesForm').on('submit', function() {
        $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>جاري الحفظ...');
    });
    
    // Format price inputs on blur
    $('.price-input').on('blur', function() {
        let value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
    
    // Add visual feedback for changed prices
    $('.price-input').on('input', function() {
        $(this).addClass('border-warning');
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#pricesForm').submit();
        }
    });
});

// دالة مسح جميع الأسعار
function clearAllPrices() {
    if (confirm('هل أنت متأكد من مسح جميع الأسعار؟')) {
        $('.price-input').val('').removeClass('border-warning');
    }
}
</script>
@endpush

@push('styles')
<style>
.price-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.table th {
    font-weight: 600;
    font-size: 0.9rem;
}

.input-group-text {
    font-size: 0.8rem;
    font-weight: 500;
}

.border-warning {
    border-color: #ffc107 !important;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .input-group-sm .form-control {
        font-size: 0.8rem;
    }
}
</style>
@endpush 