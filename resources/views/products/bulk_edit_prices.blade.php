@extends('layouts.app')

@section('title', 'تعديل أسعار المنتجات')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-white border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-tags fa-fw me-2"></i>
                            تعديل أسعار المنتجات
                        </h5>
                        <div class="badge bg-light text-primary">
                            {{ $products->count() }} منتج | {{ $priceTypes->count() }} نوع سعر
                        </div>
                    </div>
                </div>
                
                <!-- رسائل النظام -->
                <div class="card-body border-bottom">
                    @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center border-0 shadow-sm" role="alert">
                            <i class="fas fa-check-circle fa-lg me-2"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm" role="alert">
                            <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
                            <div>{{ session('error') }}</div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger d-flex border-0 shadow-sm" role="alert">
                            <i class="fas fa-exclamation-circle fa-lg me-2 mt-1"></i>
                            <div>
                                <div class="fw-bold mb-1">يرجى تصحيح الأخطاء التالية:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Control and Filter Card -->
        <div class="col-12">
            <div class="card bg-white border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="filter-controls">
                        <!-- Left side controls -->
                        <div class="filter-group">
                            <div class="control-item">
                                <i class="fas fa-sliders-h control-icon"></i>
                                <select class="form-select" id="editType">
                                    <option value="direct">تعديل مباشر للأسعار</option>
                                    <option value="bulk">تعديل جماعي</option>
                                </select>
                            </div>
                            <div class="control-item">
                                <i class="fas fa-folder control-icon"></i>
                                <select class="form-select" id="categoryFilter">
                                    <option value="">جميع المجموعات</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="control-item search-item">
                                <i class="fas fa-search control-icon"></i>
                                <input type="text" class="form-control" id="searchFilter" placeholder="بحث...">
                            </div>
                        </div>

                        <!-- Right side toggle -->
                        <div class="form-check form-switch toggle-control">
                            <input class="form-check-input" type="checkbox" id="showPurchasePriceToggle">
                            <label class="form-check-label" for="showPurchasePriceToggle">
                                <i class="fas fa-dollar-sign"></i>
                                إظهار سعر الشراء
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- نموذج التعديل المباشر -->
            <form action="{{ route('products.bulk-update-prices') }}" method="POST" id="directEditForm" class="edit-form">
                @csrf
                <input type="hidden" name="edit_type" value="direct">
                
                <div class="card bg-white border-0 shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-edit me-2"></i> التعديل المباشر للأسعار
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 px-3">المنتج</th>
                                        <th class="border-0 px-3">المجموعة</th>
                                        <th class="border-0 px-3" width="60%">الأسعار</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                    <tr data-category="{{ $product->category_id }}" data-search="{{ $product->name }} {{ $product->sku }}">
                                        <td class="px-3">
                                            <div class="fw-bold">{{ $product->name }}</div>
                                            @if($product->sku)
                                                <span class="badge bg-light text-dark">{{ $product->sku }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3">
                                            <span class="badge bg-secondary">{{ $product->category->name }}</span>
                                        </td>
                                        <td class="px-3">
                                            @foreach($product->productUnits as $productUnit)
                                            <div class="card mb-3 border">
                                                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                                                    <div>
                                                        <span class="fw-bold">{{ $productUnit->unit->name }}</span>
                                                        @if($productUnit->id == $product->main_unit_id)
                                                            <span class="badge bg-primary ms-1">رئيسية</span>
                                                        @endif
                                                    </div>
                                                    <span class="text-muted small">معامل: {{ $productUnit->conversion_factor }}</span>
                                                </div>
                                                <div class="card-body py-2">
                                                    <div class="row g-2">
                                                        {{-- New Purchase Price Input --}}
                                                        <div class="col-md-3 purchase-price-col" style="display: none;">
                                                            <div class="form-group">
                                                                <label class="form-label small text-success">
                                                                    سعر الشراء
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number"
                                                                        class="form-control purchase-price-input"
                                                                        name="purchase_prices[{{ $productUnit->id }}]"
                                                                        value="{{ $productUnit->cost }}"
                                                                        step="0.01"
                                                                        min="0"
                                                                        placeholder="0.00">
                                                                    <span class="input-group-text">ج.م</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @foreach($priceTypes as $priceType)
                                                        @php
                                                            $existingPrice = $productUnit->prices->where('price_type_id', $priceType->id)->first();
                                                            $inputName = "direct_prices[{$product->id}][{$productUnit->id}][{$priceType->id}]";
                                                        @endphp
                                                        <div class="col-md-3 selling-price-col">
                                                            <div class="form-group">
                                                                <label class="form-label small d-flex justify-content-between">
                                                                    <span>{{ $priceType->name }}</span>
                                                                    @if($priceType->is_default)
                                                                        <span class="badge bg-warning text-dark">افتراضي</span>
                                                                    @endif
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number" 
                                                                        class="form-control price-input"
                                                                        name="{{ $inputName }}"
                                                                        value="{{ $existingPrice ? $existingPrice->value : '' }}"
                                                                        step="0.01"
                                                                        min="0"
                                                                        placeholder="0.00">
                                                                    <span class="input-group-text">ج.م</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                            
                                            @if($product->productUnits->isEmpty())
                                            <div class="alert alert-warning py-2 mb-0">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <small>لا توجد وحدات محددة لهذا المنتج</small>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="clearAllPrices()">
                                    <i class="fas fa-eraser me-1"></i>مسح الأسعار
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="fillEmptyPrices()">
                                    <i class="fas fa-fill-drip me-1"></i>ملء الفراغات
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-right me-1"></i>رجوع
                                </a>
                                <button type="submit" class="btn btn-primary ms-2" id="saveDirectBtn">
                                    <i class="fas fa-save me-1"></i>حفظ الأسعار
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- نموذج التعديل بالنسبة أو القيمة -->
            <form action="{{ route('products.bulk-update-prices') }}" method="POST" id="bulkEditForm" class="edit-form" style="display: none;">
                @csrf
                <input type="hidden" name="edit_type" value="bulk">

                <div class="card bg-white border-0 shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-warning">
                            <i class="fas fa-percentage me-2"></i> التعديل الجماعي بالنسبة أو القيمة
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-center" style="width: 50px;">
                                            <div class="form-check">
                                                <input type="checkbox" id="masterCheckbox" class="form-check-input">
                                                <label class="form-check-label small" for="masterCheckbox">الكل</label>
                                            </div>
                                        </th>
                                        <th class="border-0 px-3">المنتج</th>
                                        <th class="border-0 px-3">المجموعة</th>
                                        <th class="border-0 px-3" width="50%">الأسعار الحالية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                    <tr data-category="{{ $product->category_id }}" data-search="{{ $product->name }} {{ $product->sku }}">
                                        <td class="text-center">
                                            <div class="form-check">
                                                <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input product-checkbox">
                                            </div>
                                        </td>
                                        <td class="px-3">
                                            <div class="fw-bold">{{ $product->name }}</div>
                                            @if($product->sku)
                                                <span class="badge bg-light text-dark">{{ $product->sku }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3">
                                            <span class="badge bg-secondary">{{ $product->category->name }}</span>
                                        </td>
                                        <td class="px-3">
                                            @foreach($product->productUnits as $productUnit)
                                            <div class="card mb-2 border">
                                                <div class="card-body py-2">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <div>
                                                            <span class="fw-bold">{{ $productUnit->unit->name }}</span>
                                                            @if($productUnit->id == $product->main_unit_id)
                                                                <span class="badge bg-primary ms-1">رئيسية</span>
                                                            @endif
                                                        </div>
                                                        <span class="text-muted small">معامل: {{ $productUnit->conversion_factor }}</span>
                                                    </div>
                                                    <div class="row g-2">
                                                        {{-- Display Purchase Price in Bulk Edit View --}}
                                                        <div class="col-md-3 purchase-price-col" style="display: none;">
                                                             <div class="price-display">
                                                                <span class="text-success small d-block">سعر الشراء:</span>
                                                                <span class="badge bg-light text-success price-badge">
                                                                    {{ number_format($productUnit->cost, 2) }} ج.م
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @foreach($priceTypes as $priceType)
                                                        @php
                                                            $existingPrice = $productUnit->prices->where('price_type_id', $priceType->id)->first();
                                                        @endphp
                                                        <div class="col-md-3 selling-price-col">
                                                            <div class="price-display">
                                                                <span class="text-muted small d-block">{{ $priceType->name }}:</span>
                                                                <span class="badge bg-light text-dark price-badge">
                                                                    {{ $existingPrice ? number_format($existingPrice->value, 2) : '0.00' }} ج.م
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                            
                                            @if($product->productUnits->isEmpty())
                                            <div class="alert alert-warning py-2 mb-0">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <small>لا توجد وحدات محددة</small>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card bg-white border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i> إعدادات التعديل الجماعي
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <div class="form-floating">
                                    <select name="price_type_id" class="form-select" required>
                                        <option value="">اختر نوع السعر</option>
                                        <option value="all">جميع أنواع الأسعار</option>
                                        @foreach($priceTypes as $priceType)
                                            <option value="{{ $priceType->id }}">{{ $priceType->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>
                                        <i class="fas fa-tag me-1"></i> نوع السعر المستهدف
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-floating">
                                    <select name="adjustment_type" class="form-select" required>
                                        <option value="fixed">قيمة ثابتة (ج.م)</option>
                                        <option value="percentage">نسبة مئوية (%)</option>
                                    </select>
                                    <label>
                                        <i class="fas fa-calculator me-1"></i> نوع التعديل
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-floating">
                                    <input type="number" name="adjustment_value" class="form-control" step="0.01" min="0" required placeholder="0.00">
                                    <label>
                                        <i class="fas fa-dollar-sign me-1"></i> القيمة
                                    </label>
                                </div>
                                <div class="form-text text-muted">القيمة <span id="valueUnit">ج.م</span></div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-floating">
                                    <select name="operation" class="form-select" required>
                                        <option value="increase">زيادة (+)</option>
                                        <option value="decrease">تخفيض (-)</option>
                                        <option value="set">تعيين (=)</option>
                                    </select>
                                    <label>
                                        <i class="fas fa-exchange-alt me-1"></i> العملية
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4 mb-0">
                            <div class="d-flex">
                                <i class="fas fa-info-circle fa-lg me-3 mt-1"></i>
                                <div>
                                    <strong>أمثلة:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>زيادة بنسبة 10% لكل الأسعار: اختر "جميع الأسعار" و "نسبة مئوية" و "10" و "زيادة"</li>
                                        <li>تخفيض بقيمة 5 ج.م للسعر العادي: اختر "السعر العادي" و "قيمة ثابتة" و "5" و "تخفيض"</li>
                                        <li>تعيين السعر الجملة لـ 100 ج.م: اختر "سعر الجملة" و "قيمة ثابتة" و "100" و "تعيين"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-info px-3 py-2 me-2" id="selectedCount">0</span>
                                <span class="text-muted">منتج محدد</span>
                            </div>
                            <div>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-right me-1"></i>رجوع
                                </a>
                                <button type="submit" class="btn btn-warning ms-2" id="saveBulkBtn">
                                    <i class="fas fa-calculator me-1"></i>تحديث الأسعار
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const editType = $('#editType');
    const directEditForm = $('#directEditForm');
    const bulkEditForm = $('#bulkEditForm');
    const showPurchasePriceToggle = $('#showPurchasePriceToggle');
    
    // Toggle purchase price visibility
    showPurchasePriceToggle.on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.purchase-price-col').toggle(isChecked);
        
        // Adjust column widths
        if(isChecked) {
            $('.selling-price-col').removeClass('col-md-{{ 12 / $priceTypes->count() }}').addClass('col-md-3');
        } else {
            $('.selling-price-col').removeClass('col-md-3').addClass('col-md-{{ 12 / $priceTypes->count() }}');
        }
    });
    
    // Console debugging for development
    console.log('Bulk Edit Prices script loaded');
    console.log('Forms found:', {
        directForm: directEditForm.length > 0,
        bulkForm: bulkEditForm.length > 0
    });
    
    // تبديل نوع التعديل
    editType.on('change', function() {
        console.log('Edit type changed to:', this.value);
        if (this.value === 'direct') {
            directEditForm.show();
            bulkEditForm.hide();
        } else {
            directEditForm.hide();
            bulkEditForm.show();
        }
        updateStatistics();
    });

    // تحديد/إلغاء تحديد الكل
    const masterCheckbox = $('#masterCheckbox');
    const productCheckboxes = $('.product-checkbox');
    
    masterCheckbox.on('change', function() {
        const isChecked = this.checked;
        productCheckboxes.each(function() {
            const row = $(this).closest('tr');
            if (!row.hasClass('d-none')) {
                this.checked = isChecked;
            }
        });
        updateSelectedCount();
    });

    // تحديث عداد المحدد عند تغيير الاختيار
    productCheckboxes.on('change', updateSelectedCount);

    // تصفية حسب المجموعة والبحث
    const categoryFilter = $('#categoryFilter');
    const searchFilter = $('#searchFilter');

    function applyFilters() {
        const searchTerm = searchFilter.val().toLowerCase();
        const categoryId = categoryFilter.val();
        let visibleCount = 0;

        $('tbody tr').each(function() {
            const row = $(this);
            const rowCategory = row.data('category');
            const rowSearchText = row.data('search').toLowerCase();
            const matchesCategory = !categoryId || rowCategory == categoryId;
            const matchesSearch = rowSearchText.includes(searchTerm);
            const shouldShow = matchesCategory && matchesSearch;

            row.toggleClass('d-none', !shouldShow);
            if (shouldShow) visibleCount++;
        });

        $('#visibleProducts').text(visibleCount);
        updateSelectedCount();
    }

    categoryFilter.on('change', applyFilters);
    searchFilter.on('input', applyFilters);

    // تحديث الإحصائيات
    function updateStatistics() {
        const totalProducts = $('tbody tr').length;
        $('#totalProducts').text(totalProducts);
        applyFilters();
    }

    // تحديث عداد المنتجات المحددة
    function updateSelectedCount() {
        const selectedCount = $('.product-checkbox:checked').filter(function() {
            return !$(this).closest('tr').hasClass('d-none');
        }).length;
        $('#selectedProducts, #selectedCount').text(selectedCount);
    }

    // تعديل وحدة القيمة حسب نوع التعديل
    $('select[name="adjustment_type"]').on('change', function() {
        const unit = this.value === 'percentage' ? '%' : 'ج.م';
        $('#valueUnit').text(unit);
    });

    // دوال مساعدة للتعديل المباشر
    window.clearAllPrices = function() {
        if (confirm('هل أنت متأكد من مسح جميع الأسعار؟')) {
            $('.price-input').val('').addClass('border-warning');
        }
    };

    window.fillEmptyPrices = function() {
        const defaultValue = prompt('أدخل القيمة الافتراضية للأسعار الفارغة:');
        if (defaultValue && !isNaN(defaultValue)) {
            $('.price-input').each(function() {
                if (!this.value) {
                    $(this).val(parseFloat(defaultValue).toFixed(2)).addClass('border-warning');
                }
            });
        }
    };

    // تنسيق الأسعار عند الكتابة
    $('.price-input, .purchase-price-input').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });

    // إضافة حالة التحميل للأزرار وإعادة تعيينها بعد الإرسال
    const directSaveBtn = $('#saveDirectBtn');
    const bulkSaveBtn = $('#saveBulkBtn');
    
    // تعامل مع نموذج التعديل المباشر
    directEditForm.on('submit', function(e) {
        console.log('Direct edit form submitted');
        
        // Check if form is valid
        if (!this.checkValidity()) {
            console.error('Direct form validation failed');
            e.preventDefault();
            return false;
        }
        
        console.log('Direct edit form data:', $(this).serialize());
        directSaveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>جاري الحفظ...');
        
        // إعادة تمكين الزر في حالة الخطأ أو انتهاء المهلة
        setTimeout(function() {
            if (directSaveBtn.prop('disabled')) {
                directSaveBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>حفظ الأسعار');
            }
        }, 10000); // إعادة تمكين بعد 10 ثوان كحد أقصى
    });
    
    // تعامل مع نموذج التعديل الجماعي
    bulkEditForm.on('submit', function(e) {
        console.log('Bulk edit form submitted');
        
        // التحقق من وجود منتجات محددة
        const checkedProducts = $('.product-checkbox:checked').filter(function() {
            return !$(this).closest('tr').hasClass('d-none');
        });
        
        if (checkedProducts.length === 0) {
            console.error('No products selected');
            e.preventDefault();
            alert('الرجاء تحديد منتج واحد على الأقل');
            return false;
        }
        
        // التحقق من اختيار نوع السعر المستهدف
        const priceTypeId = $('select[name="price_type_id"]').val();
        if (!priceTypeId) {
            console.error('No price type selected');
            e.preventDefault();
            alert('الرجاء اختيار نوع السعر المستهدف');
            return false;
        }
        
        // التحقق من إدخال قيمة التعديل
        const adjustmentValue = $('input[name="adjustment_value"]').val();
        if (!adjustmentValue || isNaN(adjustmentValue) || Number(adjustmentValue) <= 0) {
            console.error('Invalid adjustment value');
            e.preventDefault();
            alert('الرجاء إدخال قيمة تعديل صالحة');
            return false;
        }
        
        console.log('Bulk edit form data:', $(this).serialize());
        bulkSaveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>جاري التحديث...');
        
        // إعادة تمكين الزر في حالة الخطأ أو انتهاء المهلة
        setTimeout(function() {
            if (bulkSaveBtn.prop('disabled')) {
                bulkSaveBtn.prop('disabled', false).html('<i class="fas fa-calculator me-1"></i>تحديث الأسعار');
            }
        }, 10000); // إعادة تمكين بعد 10 ثوان كحد أقصى
    });

    // اختصارات لوحة المفاتيح
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (directEditForm.is(':visible')) {
                console.log('Submitting direct form via keyboard shortcut');
                directEditForm.submit();
            } else if (bulkEditForm.is(':visible')) {
                console.log('Submitting bulk form via keyboard shortcut');
                bulkEditForm.submit();
            }
        }
    });

    // تهيئة الإحصائيات
    updateStatistics();
});
</script>
@endpush

@push('styles')
<style>
/* تصميم أساسي */
body {
    background-color: #f5f7fb;
}

/* تحسين الجداول */
.table {
    margin-bottom: 0;
}

.table > :not(:first-child) {
    border-top: none;
}

.table > thead > tr > th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

/* تحسين البطاقات */
.card {
    transition: all 0.3s ease;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

/* تحسين الحقول */
.form-floating > .form-select,
.form-floating > .form-control {
    height: calc(3.5rem + 2px);
    line-height: 1.25;
}

.form-floating > label {
    padding: 1rem 0.75rem;
}

/* تحسين الزرائر */
.btn {
    font-weight: 500;
    padding: 0.5rem 1.25rem;
    border-radius: 0.25rem;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd, #0553c3);
    border: none;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e5a800);
    border: none;
    box-shadow: 0 2px 6px rgba(255, 193, 7, 0.25);
}

/* بطاقات الأسعار */
.price-badge {
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.4rem 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* تحسين المدخلات */
.price-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.input-group-text {
    font-weight: 500;
}

.border-warning {
    position: relative;
    animation: pulse-warning 1.5s infinite;
}

@keyframes pulse-warning {
    0% { border-color: #ffc107; }
    50% { border-color: #ffdb6d; }
    100% { border-color: #ffc107; }
}

/* تحسين الإستجابة */
@media (max-width: 768px) {
    .btn {
        padding: 0.4rem 1rem;
    }
    
    .price-badge {
        font-size: 0.8rem;
    }
}

/* تحسين الشارات */
.badge {
    font-weight: 500;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd, #0553c3);
}

.price-display .text-success {
    font-weight: 600;
}
.purchase-price-input:focus {
    border-color: #198754;
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
}
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}
.form-select, .form-control {
    padding: 0.6rem 0.75rem;
    font-size: 0.9rem;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}
.form-select:focus, .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
}
.form-switch .form-check-input {
    cursor: pointer;
}
/* New Filter Controls Styling */
.filter-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}
.filter-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-grow: 1;
}
.control-item {
    position: relative;
    flex: 1;
}
.control-icon {
    position: absolute;
    top: 50%;
    right: 0.75rem;
    transform: translateY(-50%);
    color: #adb5bd;
    pointer-events: none;
}
.search-item .control-icon {
    right: auto;
    left: 0.75rem;
}
.form-select, .form-control {
    padding-right: 2.5rem;
}
.search-item .form-control {
    padding-right: 0.75rem;
    padding-left: 2.5rem;
}
.toggle-control {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 50rem;
    padding: 0.5rem 1rem;
    padding-left: 2.5rem;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.toggle-control:hover {
    background-color: #e9ecef;
}
.toggle-control .form-check-input {
    width: 2em;
    height: 1.2em;
    margin-right: -2rem;
    cursor: pointer;
}
.toggle-control .form-check-label {
    font-weight: 500;
    color: #495057;
    cursor: pointer;
}
.toggle-control .form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}
.toggle-control .form-check-label .fa-dollar-sign {
    color: #6c757d;
    transition: color 0.2s ease;
}
.toggle-control .form-check-input:checked + .form-check-label .fa-dollar-sign {
    color: #198754;
}

@media (max-width: 992px) {
    .filter-controls, .filter-group {
        flex-direction: column;
        width: 100%;
        align-items: stretch;
    }
}
</style>
@endpush
@endsection 