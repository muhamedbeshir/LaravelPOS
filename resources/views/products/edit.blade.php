@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <!-- Header Section -->
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-2 mb-2">
                    <div class="bg-warning rounded-circle p-2 me-2">
                        <i class="fas fa-edit text-white"></i>
                    </div>
                    <div class="text-start">
                        <h4 class="mb-0 fw-bold text-dark">تعديل المنتج: {{ $product->name }}</h4>
                        <p class="text-muted mb-0 small">تحديث تفاصيل المنتج والوحدات والأسعار</p>
                    </div>
                </div>
            </div>

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    <div>
                        <strong>خطأ!</strong>
                        <div>{{ session('error') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" id="product-form">
                @csrf
                @method('PUT')
                
                <!-- Basic Information Card -->
                <div class="card border-0 shadow-sm mb-3 overflow-hidden">
                    <div class="card-header bg-gradient py-2" style="background: linear-gradient(45deg, #007bff, #0056b3); border: none;">
                        <div class="d-flex align-items-center text-white">
                            <i class="fas fa-info-circle me-2"></i>
                            <h6 class="mb-0 fw-bold">المعلومات الأساسية</h6>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <!-- Product Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold small mb-1">
                                    <i class="fas fa-tag text-primary me-1"></i>
                                    اسم المنتج
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $product->name) }}" 
                                       placeholder="أدخل اسم المنتج"
                                       required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Category -->
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-semibold small mb-1">
                                    <i class="fas fa-folder text-primary me-1"></i>
                                    المجموعة
                                </label>
                                <div class="input-group">
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" 
                                            name="category_id" 
                                            required>
                                        <option value="">اختر المجموعة</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#newCategoryModal"
                                            title="إضافة مجموعة جديدة">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Alert Quantity -->
                            <div class="col-md-6">
                                <label for="alert_quantity" class="form-label fw-semibold small mb-1">
                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                    حد التنبيه
                                </label>
                                <input type="number" 
                                       class="form-control @error('alert_quantity') is-invalid @enderror" 
                                       id="alert_quantity" 
                                       name="alert_quantity" 
                                       value="{{ old('alert_quantity', $product->alert_quantity) }}" 
                                       step="0.01" 
                                       min="0"
                                       placeholder="0">
                                @error('alert_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Product Image -->
                            <div class="col-md-6">
                                <label for="image" class="form-label fw-semibold small mb-1">
                                    <i class="fas fa-image text-primary me-1"></i>
                                    صورة المنتج
                                </label>
                                <input type="file" 
                                       class="form-control @error('image') is-invalid @enderror" 
                                       id="image" 
                                       name="image" 
                                       accept="image/*">
                                @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <div id="imagePreview" class="mt-2 {{ $product->image ? '' : 'd-none' }}">
                                    <div class="border border-dashed rounded p-2 bg-light text-center">
                                        <img src="{{ $product->image ? asset('storage/products/' . $product->image) : '#' }}" 
                                             alt="معاينة الصورة" class="img-fluid rounded" style="max-height: 100px">
                                        <p class="text-muted small mt-1 mb-0">معاينة الصورة</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Serial Number Section -->
                            <div class="col-12">
                                <div class="card bg-light border">
                                    <div class="card-body p-2">
                                        <div class="form-check form-switch mb-2">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="has_serial" 
                                                   name="has_serial" 
                                                   value="1" 
                                                   {{ old('has_serial', $product->has_serial) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold small" for="has_serial">
                                                <i class="fas fa-barcode text-primary me-1"></i>
                                                متابعة الرقم التسلسلي
                                            </label>
                                        </div>
                                        
                                        <div id="serial_number_container" class="{{ old('has_serial', $product->has_serial) ? '' : 'd-none' }}">
                                            <input type="text" 
                                                   class="form-control form-control-sm @error('serial_number') is-invalid @enderror" 
                                                   id="serial_number" 
                                                   name="serial_number" 
                                                   value="{{ old('serial_number', $product->serial_number) }}"
                                                   placeholder="أدخل الرقم التسلسلي">
                                            @error('serial_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Units and Pricing Card -->
                <div class="card border-0 shadow-sm mb-3 overflow-hidden">
                    <div class="card-header bg-gradient py-2" style="background: linear-gradient(45deg, #28a745, #20c997); border: none;">
                        <div class="d-flex justify-content-between align-items-center text-white">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calculator me-2"></i>
                                <h6 class="mb-0 fw-bold">الوحدات والأسعار</h6>
                            </div>
                            <div class="badge bg-white text-dark px-2 py-1 small">
                                <i class="fas fa-info-circle me-1"></i>
                                وحدة واحدة لكل نوع
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">

                        <div id="units-container">
                            @foreach($product->units as $index => $productUnit)
                            <!-- Unit Row -->
                            <div class="unit-row position-relative mb-3">
                                <!-- Unit Number Badge -->
                                <div class="position-absolute top-0 start-0 translate-middle z-index-1">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                         style="width: 30px; height: 30px;">
                                        <span class="fw-bold small">{{ $index + 1 }}</span>
                                    </div>
                                </div>
                                
                                <!-- Unit Content Card -->
                                <div class="card border border-primary border-opacity-25 bg-white">
                                    <div class="card-body p-3 pt-4">
                                        <div class="row g-3">
                                            <!-- Unit Selection -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small mb-1">
                                                    <i class="fas fa-cube text-primary me-1"></i>
                                                    الوحدة
                                                </label>
                                                <div class="input-group">
                                                    <select class="form-select" name="units[{{ $index }}][unit_id]" required>
                                                        <option value="">اختر الوحدة</option>
                                                        @foreach($units as $unit)
                                                        <option value="{{ $unit->id }}" 
                                                                {{ old('units.'.$index.'.unit_id', $productUnit->unit_id) == $unit->id ? 'selected' : '' }}>
                                                            {{ $unit->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#newUnitModal"
                                                            title="إضافة وحدة جديدة">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Unit Barcode -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold small mb-1">
                                                    <i class="fas fa-barcode text-primary me-1"></i>
                                                    باركود الوحدة
                                                </label>
                                                <div class="barcode-container">
                                                    <div class="barcode-entries">
                                                        @php
                                                            $barcodes = $productUnit->barcodes->pluck('barcode')->toArray();
                                                            if (empty($barcodes)) {
                                                                $barcodes = [''];
                                                            }
                                                        @endphp
                                                        @foreach($barcodes as $barcodeIndex => $barcode)
                                                        <div class="barcode-entry d-flex align-items-center mb-2">
                                                            <input type="text" class="form-control form-control-sm" 
                                                                   name="units[{{ $index }}][barcodes][]" 
                                                                   value="{{ $barcode }}"
                                                                   placeholder="أدخل الباركود">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 generate-barcode-btn flex-shrink-0" title="توليد باركود">
                                                                <i class="fas fa-magic"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm ms-1 remove-barcode-btn flex-shrink-0" title="إزالة">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-outline-primary btn-sm add-barcode-btn mt-1">
                                                        <i class="fas fa-plus"></i> إضافة باركود آخر
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Prices Section -->
                                        <div class="mt-3">
                                            <h6 class="fw-bold text-dark mb-2 small">
                                                <i class="fas fa-money-bill-wave text-success me-1"></i>
                                                الأسعار
                                            </h6>
                                            <div class="row prices-row g-2">
                                                @foreach($priceTypes as $priceType)
                                                    @php
                                                        $unitPrice = $productUnit->prices->firstWhere('price_type_id', $priceType->id);
                                                        $isDefault = $priceType->is_default;
                                                    @endphp
                                                    @if($unitPrice || $isDefault)
                                                    <div class="col-md-6 price-type-col">
                                                        <div class="card h-100 border-{{ $isDefault ? 'success' : 'info' }} border-opacity-25">
                                                            <div class="card-body p-2">
                                                                <label class="form-label fw-semibold small text-muted mb-1">
                                                                    {{ $priceType->name }}
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text bg-{{ $isDefault ? 'success' : 'info' }} text-white border-0">
                                                                        <i class="fas fa-dollar-sign"></i>
                                                                    </span>
                                                                    <input type="number" 
                                                                           class="form-control border-{{ $isDefault ? 'success' : 'info' }} border-opacity-50"
                                                                           name="units[{{ $index }}][prices][{{ $priceType->id }}][value]"
                                                                           value="{{ old('units.'.$index.'.prices.'.$priceType->id.'.value', $unitPrice ? $unitPrice->value : '') }}"
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           {{ $isDefault ? 'required' : '' }}
                                                                           placeholder="0.00">
                                                                    <input type="hidden" 
                                                                           name="units[{{ $index }}][prices][{{ $priceType->id }}][price_type_id]" 
                                                                           value="{{ $priceType->id }}">
                                                                    @if(!$isDefault)
                                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-price-type-btn" title="حذف نوع السعر">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                    @endif
                                                                </div>
                                                                @error('units.'.$index.'.prices.'.$priceType->id.'.value')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <!-- Add Price Type Button -->
                                            <div class="mt-2">
                                                <button type="button" 
                                                        class="btn btn-outline-success btn-sm add-price-type-btn" 
                                                        data-unit-index="{{ $index }}">
                                                    <i class="fas fa-plus-circle me-1"></i>
                                                    إضافة نوع سعر آخر
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Main Unit Selection -->
                                        <div class="mt-3">
                                            <div class="card bg-primary bg-opacity-10 border-primary border-opacity-25">
                                                <div class="card-body p-2">
                                                    <div class="form-check">
                                                        <input type="radio" 
                                                               class="form-check-input" 
                                                               name="main_unit_index" 
                                                               value="{{ $index }}" 
                                                               {{ $productUnit->is_main_unit ? 'checked' : '' }} 
                                                               required>
                                                        <label class="form-check-label fw-semibold text-primary small">
                                                            <i class="fas fa-star me-1"></i>
                                                            الوحدة الرئيسية
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Add Unit Button -->
                        <div class="text-center">
                            <button type="button" 
                                    class="btn btn-success px-4 py-2" 
                                    id="add-unit">
                                <i class="fas fa-plus-circle me-2"></i>
                                إضافة وحدة أخرى
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('products.index') }}" 
                                   class="btn btn-light px-4 py-2">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    رجوع
                                </a>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" id="print_barcode" name="print_barcode" value="1">
                                    <label class="form-check-label" for="print_barcode">
                                        <i class="fas fa-barcode me-1"></i>
                                        طباعة الباركود بعد الحفظ
                                    </label>
                                </div>
                                <button type="submit" 
                                        class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ التغييرات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for adding new category -->
<div class="modal fade" id="newCategoryModal" tabindex="-1" aria-labelledby="newCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newCategoryModalLabel">إضافة مجموعة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">اسم المجموعة</label>
                        <input type="text" class="form-control" id="category_name" required>
                        <div class="invalid-feedback" id="category-name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="category_color" class="form-label">لون المجموعة</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" 
                                id="category_color" value="#2563eb" title="اختر لوناً">
                            <input type="text" class="form-control" id="colorHexModal" value="#2563eb" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveNewCategory">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new unit -->
<div class="modal fade" id="newUnitModal" tabindex="-1" aria-labelledby="newUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUnitModalLabel">إضافة وحدة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="unitForm">
                    <div class="mb-3">
                        <label for="unit_name" class="form-label">اسم الوحدة</label>
                        <input type="text" class="form-control" id="unit_name" required>
                        <div class="invalid-feedback" id="unit-name-error"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_base_unit" checked>
                            <label class="form-check-label" for="is_base_unit">
                                وحدة أساسية
                            </label>
                        </div>
                    </div>
                    <div id="parent_unit_section" class="mb-3 d-none">
                        <label for="parent_unit_id" class="form-label">الوحدة الأم</label>
                        <select class="form-select" id="parent_unit_id">
                            <option value="">اختر الوحدة الأم</option>
                            @foreach($units as $unit)
                                @if($unit->is_base_unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="parent-unit-error"></div>
                    </div>
                    <div id="conversion_factor_section" class="mb-3 d-none">
                        <label for="conversion_factor" class="form-label">معامل التحويل</label>
                        <input type="number" class="form-control" id="conversion_factor" step="0.01" min="0.01" value="1">
                        <small class="form-text text-muted">عدد وحدات الأم التي تعادل وحدة واحدة من هذه الوحدة</small>
                        <div class="invalid-feedback" id="conversion-factor-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveNewUnit">حفظ</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.priceTypes = @json($priceTypes);
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.getElementById('product-form');

    productForm.addEventListener('click', function(e) {
        // Generate barcode button
        if (e.target.closest('.generate-barcode-btn')) {
            e.preventDefault();
            const button = e.target.closest('.generate-barcode-btn');
            const barcodeEntry = button.closest('.barcode-entry');
            const input = barcodeEntry.querySelector('input[type="text"]');
            // A simple random barcode generator (13 digits)
            input.value = Math.floor(1000000000000 + Math.random() * 9000000000000);
        }

        // Remove barcode button
        if (e.target.closest('.remove-barcode-btn')) {
            e.preventDefault();
            const button = e.target.closest('.remove-barcode-btn');
            const barcodeEntry = button.closest('.barcode-entry');
            const entriesContainer = barcodeEntry.parentElement;
            // Do not remove the last input, just clear it
            if (entriesContainer.children.length > 1) {
                barcodeEntry.remove();
            } else {
                barcodeEntry.querySelector('input[type="text"]').value = '';
            }
        }

        // Add another barcode button
        if (e.target.closest('.add-barcode-btn')) {
            e.preventDefault();
            const button = e.target.closest('.add-barcode-btn');
            const barcodeContainer = button.closest('.barcode-container');
            const entriesContainer = barcodeContainer.querySelector('.barcode-entries');
            
            // Clone the first entry to use as a template
            const newEntry = entriesContainer.children[0].cloneNode(true);
            newEntry.querySelector('input[type="text"]').value = '';
            entriesContainer.appendChild(newEntry);
        }
    });
    
    // معاينة الصورة
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = imagePreview.querySelector('img');

    imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                imagePreview.classList.remove('d-none');
            }
            reader.readAsDataURL(this.files[0]);
        } else {
            imagePreview.classList.add('d-none');
        }
    });

    // إضافة وحدة جديدة
    let unitIndex = {{ count($product->units) }};
    document.getElementById('add-unit').addEventListener('click', function() {
        const container = document.getElementById('units-container');
        const template = container.querySelector('.unit-row').cloneNode(true);
        
        // Update unit number badge
        const badge = template.querySelector('.bg-primary span');
        badge.textContent = unitIndex + 1;
        
        // تحديث الأسماء والقيم
        template.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                // تحديث جميع الأسماء لتناسب الفهرس الجديد
                input.setAttribute('name', name.replace(/units\[\d+\]/, `units[${unitIndex}]`));
                if (input.type === 'number' || input.type === 'text') {
                    input.value = '';
                }
            }
            if (input.type === 'radio') {
                input.checked = false;
                input.value = unitIndex;
            }
        });

        // إعادة توليد حقول الأسعار حسب أنواع الأسعار
        const pricesRow = template.querySelector('.prices-row');
        pricesRow.innerHTML = '';
        window.priceTypes.filter(pt => pt.is_default).forEach(function(priceType) {
            const col = document.createElement('div');
            col.className = 'col-md-6 price-type-col';
            col.innerHTML = `
                <div class="card h-100 border-success border-opacity-25">
                    <div class="card-body p-2">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            ${priceType.name}
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-success text-white border-0">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <input type="number" class="form-control"
                                name="units[${unitIndex}][prices][${priceType.id}][value]"
                                step="0.01" min="0" placeholder="0.00">
                            <input type="hidden" name="units[${unitIndex}][prices][${priceType.id}][price_type_id]" value="${priceType.id}">
                        </div>
                    </div>
                </div>
            `;
            pricesRow.appendChild(col);
        });
        
        // Add price type button
        const addPriceTypeBtn = template.querySelector('.add-price-type-btn');
        addPriceTypeBtn.setAttribute('data-unit-index', unitIndex);
        
        // Reset barcode container to single entry
        const barcodeContainer = template.querySelector('.barcode-container');
        const barcodeEntries = barcodeContainer.querySelector('.barcode-entries');
        
        // Remove all but the first barcode entry
        while (barcodeEntries.children.length > 1) {
            barcodeEntries.removeChild(barcodeEntries.lastChild);
        }
        
        // Clear the value of the remaining input and update its name
        const firstInput = barcodeEntries.querySelector('input[type="text"]');
        if (firstInput) {
            firstInput.value = '';
            firstInput.name = `units[${unitIndex}][barcodes][]`;
        }
        
        container.appendChild(template);
        setupPriceTypeButtons();
        unitIndex++;
    });

    // إدارة ظهور حقل الرقم التسلسلي
    const hasSerialCheckbox = document.getElementById('has_serial');
    const serialNumberContainer = document.getElementById('serial_number_container');
    const serialNumberInput = document.getElementById('serial_number');

    hasSerialCheckbox.addEventListener('change', function() {
        if (this.checked) {
            serialNumberContainer.classList.remove('d-none');
            serialNumberInput.setAttribute('required', 'required');
        } else {
            serialNumberContainer.classList.add('d-none');
            serialNumberInput.removeAttribute('required');
            serialNumberInput.value = '';
        }
    });

    // التحقق من النموذج قبل الإرسال
    document.getElementById('product-form').addEventListener('submit', function(e) {
        let isValid = true;
        let errorMessages = [];

        // 1. التحقق من اختيار وحدة رئيسية
        const mainUnitChecked = document.querySelector('input[name="main_unit_index"]:checked');
        if (!mainUnitChecked) {
            errorMessages.push('يجب اختيار وحدة رئيسية للمنتج.');
            isValid = false;
        }
        
        // 2. التحقق من أن حقول الأسعار المطلوبة ليست فارغة أو صفر
        const requiredPriceInputs = document.querySelectorAll('input[name*="[prices]"][required]');
        requiredPriceInputs.forEach(input => {
            if (!input.value || parseFloat(input.value) <= 0) {
                const unitRow = input.closest('.unit-row');
                const unitSelect = unitRow.querySelector('select[name*="[unit_id]"]');
                const unitName = unitSelect.options[unitSelect.selectedIndex]?.text || 'وحدة غير محددة';
                errorMessages.push(`- السعر الافتراضي للوحدة "${unitName}" مطلوب ويجب أن يكون أكبر من صفر.`);
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        // 3. التحقق من عدم تكرار الوحدات
        const selectedUnits = new Set();
        const unitSelects = document.querySelectorAll('select[name*="[unit_id]"]');
        let hasDuplicate = false;
        unitSelects.forEach(select => {
            if (selectedUnits.has(select.value)) {
                if (!hasDuplicate) { // Add error message only once
                    errorMessages.push('لا يمكن اختيار نفس الوحدة أكثر من مرة.');
                    hasDuplicate = true;
                }
                select.classList.add('is-invalid');
                isValid = false;
            } else {
                selectedUnits.add(select.value);
                select.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('يرجى تصحيح الأخطاء التالية:\n\n' + errorMessages.join('\n'));
            return false;
        }
    });

    // Initialize the color picker for the category color field
    const colorInput = document.getElementById('category_color');
    const colorHexModal = document.getElementById('colorHexModal');
    
    if (colorInput && colorHexModal) {
        // Set initial color
        colorInput.value = colorInput.value || '#3498db';
        colorHexModal.value = colorInput.value;
        
        // Update preview when color changes
        colorInput.addEventListener('input', function() {
            colorHexModal.value = colorInput.value;
        });
    }
    
    // Add event listener for the save button in the category modal
    const saveNewCategoryBtn = document.getElementById('saveNewCategory');
    if (saveNewCategoryBtn) {
        saveNewCategoryBtn.addEventListener('click', function() {
            const categoryName = document.getElementById('category_name').value;
            const categoryColor = document.getElementById('category_color').value;
            
            // Basic validation
            if (!categoryName.trim()) {
                const categoryNameError = document.getElementById('category-name-error');
                categoryNameError.textContent = 'اسم المجموعة مطلوب';
                categoryNameError.style.display = 'block';
                return;
            }
            
            // Send AJAX request to create category
            fetch('/api/categories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: categoryName,
                    color: categoryColor
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new category to the dropdown
                    const categorySelect = document.getElementById('category_id');
                    const option = document.createElement('option');
                    option.value = data.category.id;
                    option.textContent = data.category.name;
                    categorySelect.appendChild(option);
                    
                    // Select the newly created category
                    categorySelect.value = data.category.id;
                    
                    // Show success message
                    alert('تم إضافة المجموعة بنجاح');
                    
                    // Close the modal
                    bootstrap.Modal.getInstance(document.getElementById('newCategoryModal')).hide();
                    
                    // Reset the form
                    document.getElementById('categoryForm').reset();
                    colorHexModal.value = '#3498db';
                } else {
                    // Show error message
                    alert('فشل إضافة المجموعة: ' + (data.message || 'حدث خطأ غير معروف'));
                    
                    // Show validation errors if any
                    if (data.errors && data.errors.name) {
                        const categoryNameError = document.getElementById('category-name-error');
                        categoryNameError.textContent = data.errors.name[0];
                        categoryNameError.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة المجموعة');
            });
        });
    }

    // Add event listener for the save button in the unit modal
    const saveNewUnitBtn = document.getElementById('saveNewUnit');
    if (saveNewUnitBtn) {
        // Toggle parent unit section based on is_base_unit checkbox
        document.getElementById('is_base_unit').addEventListener('change', function() {
            const parentUnitSection = document.getElementById('parent_unit_section');
            const conversionFactorSection = document.getElementById('conversion_factor_section');
            
            if (this.checked) {
                parentUnitSection.classList.add('d-none');
                conversionFactorSection.classList.add('d-none');
            } else {
                parentUnitSection.classList.remove('d-none');
                conversionFactorSection.classList.remove('d-none');
            }
        });
        
        saveNewUnitBtn.addEventListener('click', function() {
            const unitName = document.getElementById('unit_name').value;
            const isBaseUnit = document.getElementById('is_base_unit').checked;
            let parentUnitId = null;
            let conversionFactor = 1;
            
            // Basic validation
            if (!unitName.trim()) {
                const unitNameError = document.getElementById('unit-name-error');
                unitNameError.textContent = 'اسم الوحدة مطلوب';
                unitNameError.style.display = 'block';
                return;
            }
            
            // Additional validation for non-base units
            if (!isBaseUnit) {
                parentUnitId = document.getElementById('parent_unit_id').value;
                conversionFactor = document.getElementById('conversion_factor').value;
                
                if (!parentUnitId) {
                    const parentUnitError = document.getElementById('parent-unit-error');
                    parentUnitError.textContent = 'يجب اختيار الوحدة الأم';
                    parentUnitError.style.display = 'block';
                    return;
                }
                
                if (!conversionFactor || conversionFactor <= 0) {
                    const conversionFactorError = document.getElementById('conversion-factor-error');
                    conversionFactorError.textContent = 'معامل التحويل يجب أن يكون أكبر من الصفر';
                    conversionFactorError.style.display = 'block';
                    return;
                }
            }
            
            // Send AJAX request to create unit
            fetch('/api/units', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    name: unitName,
                    is_base_unit: isBaseUnit,
                    parent_unit_id: parentUnitId,
                    conversion_factor: conversionFactor
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new unit to all unit dropdowns
                    const unitSelects = document.querySelectorAll('select[name$="[unit_id]"]');
                    unitSelects.forEach(select => {
                        const option = document.createElement('option');
                        option.value = data.unit.id;
                        option.textContent = data.unit.name;
                        select.appendChild(option);
                    });
                    
                    // If this is a base unit, add it to the parent unit dropdown too
                    if (isBaseUnit) {
                        const parentUnitSelect = document.getElementById('parent_unit_id');
                        const option = document.createElement('option');
                        option.value = data.unit.id;
                        option.textContent = data.unit.name;
                        parentUnitSelect.appendChild(option);
                    }
                    
                    // Show success message
                    alert('تم إضافة الوحدة بنجاح');
                    
                    // Close the modal
                    bootstrap.Modal.getInstance(document.getElementById('newUnitModal')).hide();
                    
                    // Reset the form
                    document.getElementById('unitForm').reset();
                    document.getElementById('is_base_unit').checked = true;
                    document.getElementById('parent_unit_section').classList.add('d-none');
                    document.getElementById('conversion_factor_section').classList.add('d-none');
                } else {
                    // Show error message
                    alert('فشل إضافة الوحدة: ' + (data.message || 'حدث خطأ غير معروف'));
                    
                    // Show validation errors if any
                    if (data.errors) {
                        if (data.errors.name) {
                            const unitNameError = document.getElementById('unit-name-error');
                            unitNameError.textContent = data.errors.name[0];
                            unitNameError.style.display = 'block';
                        }
                        if (data.errors.parent_unit_id) {
                            const parentUnitError = document.getElementById('parent-unit-error');
                            parentUnitError.textContent = data.errors.parent_unit_id[0];
                            parentUnitError.style.display = 'block';
                        }
                        if (data.errors.conversion_factor) {
                            const conversionFactorError = document.getElementById('conversion-factor-error');
                            conversionFactorError.textContent = data.errors.conversion_factor[0];
                            conversionFactorError.style.display = 'block';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة الوحدة');
            });
        });
    }

    // Functions for price type management
    function setupPriceTypeButtons() {
        // زر حذف نوع السعر
        document.querySelectorAll('.remove-price-type-btn').forEach(btn => {
            btn.onclick = function() {
                if(confirm('هل أنت متأكد من حذف نوع السعر هذا؟')) {
                    this.closest('.price-type-col').remove();
                }
            };
        });
        
        // زر إضافة نوع سعر
        document.querySelectorAll('.add-price-type-btn').forEach(btn => {
            btn.onclick = function() {
                const unitIndex = this.getAttribute('data-unit-index');
                const unitRow = this.closest('.unit-row');
                if (!unitRow) {
                    console.error('Could not find unit row');
                    return;
                }
                const pricesRow = unitRow.querySelector('.prices-row');
                if (!pricesRow) {
                    console.error('Could not find prices row');
                    return;
                }
                // أنواع الأسعار المضافة حالياً
                const addedIds = Array.from(pricesRow.querySelectorAll('input[name^="units['+unitIndex+'][prices]"][name$="[price_type_id]"]')).map(i => i.value);
                // أنواع الأسعار المتاحة للإضافة (جميع الأنواع ما عدا المضافة حالياً)
                const available = window.priceTypes.filter(pt => !addedIds.includes(pt.id.toString()));
                if(available.length === 0) {
                    alert('تمت إضافة جميع أنواع الأسعار بالفعل');
                    return;
                }
                // إضافة أول نوع متاح
                const pt = available[0];
                const col = document.createElement('div');
                col.className = 'col-md-6 price-type-col';
                col.innerHTML = `
                    <div class="card h-100 border-info border-opacity-25">
                        <div class="card-body p-2">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            ${pt.name}
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-info text-white border-0">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <input type="number" class="form-control"
                                name="units[${unitIndex}][prices][${pt.id}][value]" 
                                step="0.01" min="0" placeholder="0.00">
                            <input type="hidden" name="units[${unitIndex}][prices][${pt.id}][price_type_id]" value="${pt.id}">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-price-type-btn" title="حذف نوع السعر">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        </div>
                    </div>
                `;
                pricesRow.appendChild(col);
                setupPriceTypeButtons();
            };
        });
    }
    
    // Initialize price type buttons on page load
    setupPriceTypeButtons();
});
</script>
@endpush

@endsection 