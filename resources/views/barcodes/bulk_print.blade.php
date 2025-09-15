@extends('layouts.app')

@section('title', 'طباعة باركود متعدد')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-barcode me-2"></i>
                        طباعة باركود سريع للمنتجات
                    </h5>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للمنتجات
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        يمكنك اختيار المنتجات التي ترغب في طباعة باركود لها مع تحديد الكمية المطلوبة من كل منتج.
                    </div>

                    <!-- إعدادات الطباعة -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">إعدادات الطباعة</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="barcodeType" class="form-label">نوع الباركود</label>
                                        <select id="barcodeType" class="form-select">
                                            <option value="C128">Code 128</option>
                                            <option value="C39">Code 39</option>
                                            <option value="EAN13">EAN 13</option>
                                            <option value="UPCA">UPC-A</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="defaultPriceType" class="form-label">نوع السعر الافتراضي</label>
                                        <select id="defaultPriceType" class="form-select">
                                            @foreach($priceTypes as $priceType)
                                                <option value="{{ $priceType->id }}" {{ $priceType->is_default ? 'selected' : '' }}>
                                                    {{ $priceType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة المنتجات المختارة -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">المنتجات المختارة</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addProductsBtn">
                                <i class="fas fa-plus-circle me-1"></i>
                                إضافة منتجات
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="selectedProductsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>المنتج</th>
                                            <th>الوحدة/الباركود</th>
                                            <th>نوع السعر</th>
                                            <th width="120">عدد النسخ</th>
                                            <th width="80">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedProductsList">
                                        <tr id="noProductsRow">
                                            <td colspan="5" class="text-center text-muted py-3">
                                                <i class="fas fa-barcode fa-2x mb-2"></i>
                                                <div>لم يتم اختيار منتجات بعد</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار الطباعة -->
                    <div class="text-center mb-4">
                        <button id="previewBtn" class="btn btn-info text-white me-2" disabled>
                            <i class="fas fa-eye me-1"></i>
                            معاينة
                        </button>
                        <button id="printBtn" class="btn btn-success btn-lg" disabled>
                            <i class="fas fa-print me-2"></i>
                            طباعة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal اختيار المنتجات -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-labelledby="productsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productsModalLabel">اختر المنتجات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- بحث المنتجات -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="بحث بالاسم أو الباركود...">
                            <button class="btn btn-primary" type="button" id="searchBtn">بحث</button>
                        </div>
                    </div>
                </div>

                <!-- جدول المنتجات -->
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAllProducts">
                                    </div>
                                </th>
                                <th>المنتج</th>
                                <th>المجموعة</th>
                                <th>الباركود</th>
                                <th>الوحدات</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr id="loadingRow">
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">جاري التحميل...</span>
                                    </div>
                                    <div class="mt-2">جاري تحميل المنتجات...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- رسالة عدم وجود منتجات -->
                <div id="noProductsFound" class="text-center py-4 d-none">
                    <i class="fas fa-search fa-2x text-muted mb-2"></i>
                    <h5>لم يتم العثور على منتجات</h5>
                    <p class="text-muted">حاول البحث بكلمات مختلفة</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="addSelectedProductsBtn">
                    <i class="fas fa-plus-circle me-1"></i>
                    إضافة المنتجات المحددة
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .selected-product-row .form-select, .selected-product-row .form-control {
        font-size: 0.9rem;
        padding: 0.375rem 0.5rem;
    }
    
    .product-checkbox:checked + label {
        font-weight: bold;
    }
    
    .unit-option {
        padding: 8px;
        border-radius: 4px;
        margin-bottom: 5px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }
    
    .unit-option:hover {
        background-color: #e9ecef;
    }
    
    .unit-option .form-check-input:checked + label {
        font-weight: bold;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsModal = new bootstrap.Modal(document.getElementById('productsModal'));
    const selectedProducts = new Map(); // Map to store selected products
    
    // Buttons
    const addProductsBtn = document.getElementById('addProductsBtn');
    const searchBtn = document.getElementById('searchBtn');
    const addSelectedProductsBtn = document.getElementById('addSelectedProductsBtn');
    const printBtn = document.getElementById('printBtn');
    const previewBtn = document.getElementById('previewBtn');
    
    // Input elements
    const searchInput = document.getElementById('searchInput');
    const selectAllProducts = document.getElementById('selectAllProducts');
    
    // Tables
    const productsTableBody = document.getElementById('productsTableBody');
    const selectedProductsList = document.getElementById('selectedProductsList');
    const noProductsRow = document.getElementById('noProductsRow');
    
    // Price type
    const defaultPriceType = document.getElementById('defaultPriceType');
    
    // Event Listeners
    addProductsBtn.addEventListener('click', function(event) {
        event.preventDefault();
        openProductsModal();
    });
    searchBtn.addEventListener('click', function(event) {
        event.preventDefault();
        searchProducts();
    });
    addSelectedProductsBtn.addEventListener('click', function(event) {
        event.preventDefault();
        addSelectedProducts();
    });
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProducts();
        }
    });
    selectAllProducts.addEventListener('change', toggleSelectAllProducts);
    printBtn.addEventListener('click', printBarcodes);
    previewBtn.addEventListener('click', previewBarcodes);
    
    // Load products when modal is opened
    function openProductsModal() {
        loadProducts();
        productsModal.show();
    }
    
    // Load products from API
    function loadProducts() {
        const searchTerm = searchInput.value.trim();
        productsTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <div class="mt-2">جاري تحميل المنتجات...</div>
                </td>
            </tr>
        `;
        
        // Make API request
        fetch(`{{ route('bulk-barcodes.get-products') }}?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                renderProductsTable(data.products);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                productsTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            حدث خطأ أثناء تحميل المنتجات. يرجى المحاولة مرة أخرى.
                        </td>
                    </tr>
                `;
            });
    }
    
    // Search products
    function searchProducts() {
        loadProducts();
    }
    
    // Toggle select all products
    function toggleSelectAllProducts() {
        const isChecked = selectAllProducts.checked;
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    }
    
    // Render products in the table
    function renderProductsTable(products) {
        if (!products || products.length === 0) {
            productsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <h5>لم يتم العثور على منتجات</h5>
                        <p class="text-muted">حاول البحث بكلمات مختلفة</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        
        products.forEach(product => {
            const isSelected = selectedProducts.has(product.id);
            
            // Check if product has any units with barcodes
            const hasValidBarcodes = product.units && product.units.some(unit => 
                unit.barcodes && unit.barcodes.length > 0
            );
            
            if (!hasValidBarcodes) {
                return; // Skip products without barcodes
            }
            
            html += `
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input product-checkbox" type="checkbox" 
                                data-product-id="${product.id}" 
                                ${isSelected ? 'checked' : ''}
                                id="product-${product.id}">
                        </div>
                    </td>
                    <td>
                        <label for="product-${product.id}" class="cursor-pointer">
                            <strong>${product.name}</strong>
                        </label>
                    </td>
                    <td>
                        <span class="badge bg-primary">${product.category ? product.category.name : 'غير محدد'}</span>
                    </td>
                    <td>
                        ${product.barcode ? `<span class="badge bg-light text-dark border">${product.barcode}</span>` : '-'}
                    </td>
                    <td>
                        <div class="units-container">
                            ${renderProductUnits(product)}
                        </div>
                    </td>
                </tr>
            `;
        });
        
        productsTableBody.innerHTML = html;
        
        // Add event listeners to checkboxes
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const unitRadios = document.querySelectorAll(`input[name="unit-${this.dataset.productId}"]`);
                unitRadios.forEach(radio => {
                    radio.disabled = !this.checked;
                });
            });
        });
    }
    
    // Render units for each product
    function renderProductUnits(product) {
        if (!product.units || product.units.length === 0) {
            return '<div class="text-muted small">لا توجد وحدات</div>';
        }
        
        let html = '';
        let hasValidUnit = false;
        
        product.units.forEach(unit => {
            if (!unit.barcodes || unit.barcodes.length === 0) {
                return;
            }
            
            hasValidUnit = true;
            const unitName = unit.unit ? unit.unit.name : 'وحدة';
            
            unit.barcodes.forEach(barcode => {
                html += `
                    <div class="unit-option">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" 
                                name="unit-${product.id}" 
                                value="${barcode.id}" 
                                data-unit-id="${unit.id}" 
                                data-unit-name="${unitName}" 
                                data-barcode="${barcode.barcode}" 
                                id="unit-${product.id}-${barcode.id}"
                                ${selectedProducts.has(product.id) ? '' : 'disabled'}>
                            <label class="form-check-label" for="unit-${product.id}-${barcode.id}">
                                ${unitName} - ${barcode.barcode}
                            </label>
                        </div>
                    </div>
                `;
            });
        });
        
        return hasValidUnit ? html : '<div class="text-muted small">لا توجد باركودات</div>';
    }
    
    // Add selected products
    function addSelectedProducts() {
        const checkedProducts = document.querySelectorAll('.product-checkbox:checked');
        
        if (checkedProducts.length === 0) {
            alert('يرجى اختيار منتج واحد على الأقل');
            return;
        }
        
        let hasErrors = false;
        
        checkedProducts.forEach(checkbox => {
            const productId = checkbox.dataset.productId;
            const selectedUnit = document.querySelector(`input[name="unit-${productId}"]:checked`);
            
            if (!selectedUnit) {
                hasErrors = true;
                return;
            }
            
            const unitId = selectedUnit.dataset.unitId;
            const unitName = selectedUnit.dataset.unitName;
            const barcodeId = selectedUnit.value;
            const barcode = selectedUnit.dataset.barcode;
            const productName = checkbox.parentElement.parentElement.nextElementSibling.textContent.trim();
            
            // Add to selected products map if not already there
            if (!selectedProducts.has(productId)) {
                selectedProducts.set(productId, {
                    id: productId,
                    name: productName,
                    unitId: unitId,
                    unitName: unitName,
                    barcodeId: barcodeId,
                    barcode: barcode,
                    copies: 1,
                    priceTypeId: defaultPriceType.value
                });
            }
        });
        
        if (hasErrors) {
            alert('يرجى اختيار وحدة وباركود لكل منتج محدد');
            return;
        }
        
        updateSelectedProductsList();
        productsModal.hide();
    }
    
    // Update the selected products list in the main view
    function updateSelectedProductsList() {
        if (selectedProducts.size === 0) {
            selectedProductsList.innerHTML = noProductsRow.outerHTML;
            printBtn.disabled = true;
            previewBtn.disabled = true;
            return;
        }
        
        let html = '';
        
        selectedProducts.forEach(product => {
            html += `
                <tr class="selected-product-row" data-product-id="${product.id}">
                    <td>${product.name}</td>
                    <td>${product.unitName} - ${product.barcode}</td>
                    <td>
                        <select class="form-select form-select-sm price-type-select" data-product-id="${product.id}">
                            @foreach($priceTypes as $priceType)
                                <option value="{{ $priceType->id }}" ${product.priceTypeId == {{ $priceType->id }} ? 'selected' : ''}>
                                    {{ $priceType->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm copies-input" 
                            data-product-id="${product.id}" 
                            value="${product.copies}" 
                            min="1" max="100">
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-danger remove-product-btn" data-product-id="${product.id}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        selectedProductsList.innerHTML = html;
        printBtn.disabled = false;
        previewBtn.disabled = false;
        
        // Add event listeners to inputs and remove buttons
        document.querySelectorAll('.copies-input').forEach(input => {
            input.addEventListener('change', updateProductCopies);
        });
        
        document.querySelectorAll('.price-type-select').forEach(select => {
            select.addEventListener('change', updateProductPriceType);
        });
        
        document.querySelectorAll('.remove-product-btn').forEach(btn => {
            btn.addEventListener('click', removeProduct);
        });
    }
    
    // Update product copies
    function updateProductCopies(e) {
        const productId = e.target.dataset.productId;
        const product = selectedProducts.get(productId);
        
        if (product) {
            product.copies = parseInt(e.target.value) || 1;
            selectedProducts.set(productId, product);
        }
    }
    
    // Update product price type
    function updateProductPriceType(e) {
        const productId = e.target.dataset.productId;
        const product = selectedProducts.get(productId);
        
        if (product) {
            product.priceTypeId = e.target.value;
            selectedProducts.set(productId, product);
        }
    }
    
    // Remove a product from the selected list
    function removeProduct(e) {
        e.preventDefault(); // Prevent default button action
        const productId = e.currentTarget.dataset.productId;
        selectedProducts.delete(productId);
        updateSelectedProductsList();
    }
    
    // Print barcodes
    function printBarcodes(event) {
        event.preventDefault(); // Prevent default button action
        
        const items = [];
        
        selectedProducts.forEach(product => {
            items.push({
                product_id: product.id,
                barcode_id: product.barcodeId,
                copies: product.copies,
                price_type_id: product.priceTypeId
            });
        });
        
        if (items.length === 0) {
            alert('يرجى اختيار منتج واحد على الأقل للطباعة');
            return;
        }
        
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('bulk-barcodes.print') }}';
        form.target = '_blank';
        form.style.display = 'none';
        
        // CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Barcode type
        const barcodeType = document.createElement('input');
        barcodeType.type = 'hidden';
        barcodeType.name = 'barcode_type';
        barcodeType.value = document.getElementById('barcodeType').value;
        form.appendChild(barcodeType);
        
        // Items as individual inputs
        items.forEach((item, index) => {
            for (const key in item) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = item[key];
                form.appendChild(input);
            }
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    // Preview barcodes
    function previewBarcodes(event) {
        event.preventDefault(); // Prevent default button action
        
        const items = [];
        
        selectedProducts.forEach(product => {
            items.push({
                product_id: product.id,
                barcode_id: product.barcodeId,
                copies: product.copies,
                price_type_id: product.priceTypeId
            });
        });
        
        if (items.length === 0) {
            alert('يرجى اختيار منتج واحد على الأقل للمعاينة');
            return;
        }
        
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('bulk-barcodes.print') }}';
        form.target = '_blank';
        form.style.display = 'none';
        
        // CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Barcode type
        const barcodeType = document.createElement('input');
        barcodeType.type = 'hidden';
        barcodeType.name = 'barcode_type';
        barcodeType.value = document.getElementById('barcodeType').value;
        form.appendChild(barcodeType);
        
        // Is preview flag
        const isPreview = document.createElement('input');
        isPreview.type = 'hidden';
        isPreview.name = 'is_preview';
        isPreview.value = '1';
        form.appendChild(isPreview);
        
        // Items as individual inputs
        items.forEach((item, index) => {
            for (const key in item) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = item[key];
                form.appendChild(input);
            }
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
});
</script>
@endpush 