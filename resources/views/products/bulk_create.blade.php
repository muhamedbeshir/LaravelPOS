@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>إضافة منتجات سريعة</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.bulk-store') }}" method="POST" id="bulk-product-form" novalidate>
                        @csrf
                        
                        <!-- Common Settings -->
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-3">
                                <label for="category_id" class="form-label">المجموعة</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">اختر المجموعة</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="unit_id" class="form-label">الوحدة الرئيسية</label>
                                <select class="form-select" id="unit_id" name="unit_id" required>
                                    <option value="">اختر الوحدة</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="alert_quantity" class="form-label">حد التنبيه</label>
                                <input type="number" class="form-control" id="alert_quantity" name="alert_quantity" value="0" step="1" min="0">
                            </div>
                            <div class="col-md-4">
                                <div class="border p-3 rounded bg-light h-100">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_initial_stock" name="enable_initial_stock">
                                                <label class="form-check-label" for="enable_initial_stock"><strong>إضافة رصيد افتتاحي</strong></label>
                                            </div>
                                            <small class="text-muted">لتحديد سعر الشراء والكمية الأولية.</small>
                                        </div>
                                        <div class="col-md-6">
                                             @if($showColors || $showSizes)
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enable_variants" name="enable_variants">
                                                <label class="form-check-label" for="enable_variants"><strong>تفعيل المتغيرات</strong></label>
                                            </div>
                                            <div class="ps-5" id="variable-prices-container" style="display: none;">
                                                <div class="form-check form-switch" id="variable-purchase-price-container">
                                                     <input class="form-check-input" type="checkbox" id="enable_variable_purchase_prices" name="enable_variable_purchase_prices">
                                                     <label class="form-check-label" for="enable_variable_purchase_prices"><small>أسعار شراء متغيرة؟</small></label>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_variable_selling_prices" name="enable_variable_selling_prices">
                                                    <label class="form-check-label" for="enable_variable_selling_prices"><small>أسعار بيع متغيرة؟</small></label>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Types Row -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">أنواع الأسعار</label>
                                <div class="d-flex flex-wrap">
                                    @foreach($priceTypes as $priceType)
                                    @if($priceType->name == 'سعر رئيسي')
                                    <div class="form-check me-3">
                                        <input class="form-check-input price-type-checkbox" type="checkbox" id="price_type_{{ $priceType->id }}" name="price_types[]" value="{{ $priceType->id }}" checked disabled>
                                        <label class="form-check-label" for="price_type_{{ $priceType->id }}">{{ $priceType->name }}</label>
                                        <input type="hidden" name="price_types[]" value="{{ $priceType->id }}">
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="products-table">
                                <thead class="table-light">
                                    <tr>
                                        <th width="3%">#</th>
                                        <th width="25%">اسم المنتج</th>
                                        <th class="main-price-cell" width="15%">الباركود</th>
                                        <th class="variants-column" style="display:none;" width="20%">المتغيرات</th>
                                        <th class="initial-stock-column main-price-cell" style="display:none;" width="10%">سعر الشراء</th>
                                        <th class="initial-stock-column main-quantity-cell" style="display:none;" width="10%">الكمية</th>
                                        @foreach($priceTypes as $priceType)
                                        @if($priceType->name == 'سعر رئيسي')
                                        <th class="price-column price-type-{{ $priceType->id }} main-price-cell">{{ $priceType->name }}</th>
                                        @endif
                                        @endforeach
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="products-container">
                                    <!-- Product row template will be cloned from here -->
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <button type="button" id="add-product-row" class="btn btn-success"><i class="fas fa-plus me-1"></i> إضافة منتج</button>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-right me-1"></i> رجوع</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> حفظ المنتجات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Selection Modal -->
<div class="modal" id="selection-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selection-modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="selection-modal-list" style="max-height: 450px; overflow-y: auto;">
                    <!-- Items will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="save-selection">حفظ</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    /* Forcefully remove modal fade animation */
    .modal {
        transition: none !important;
    }
    .modal.fade .modal-dialog {
        transition: none !important;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsContainer = document.getElementById('products-container');
    const addProductRowBtn = document.getElementById('add-product-row');
        const priceTypes = @json($priceTypes);
    const colors = @json($colors);
    const sizes = @json($sizes);

    let selectionModal = new bootstrap.Modal(document.getElementById('selection-modal'));
    let currentRowIndex = null;
    let currentSelectionType = null;

    function updateMainSellingPriceColumnsVisibility() {
        const areSellingPricesVariable = document.getElementById('enable_variable_selling_prices').checked;
        // Only show the main price column when variable selling prices are not enabled
        document.querySelectorAll('.main-price-cell.price-type-1').forEach(col => {
            col.style.display = !areSellingPricesVariable ? '' : 'none';
        });
    }

    function updateMainPurchasePriceVisibility() {
        const initialStockEnabled = document.getElementById('enable_initial_stock').checked;
        const arePurchasePricesVariable = document.getElementById('enable_variable_purchase_prices').checked;
        document.querySelectorAll('.main-price-cell[width="10%"]').forEach(col => { // A bit fragile, targets purchase price
             col.style.display = (initialStockEnabled && !arePurchasePricesVariable) ? '' : 'none';
        });
    }

    // Price type checkboxes are no longer needed since we only use main price
    // Removed event listeners for price type checkboxes

    // Initial product row
    addProductRow();

    // Event Listeners
    addProductRowBtn.addEventListener('click', addProductRow);

    productsContainer.addEventListener('click', function (e) {
        if (e.target.closest('.remove-product-row')) {
            handleRemoveRow(e.target);
        } else if (e.target.closest('.open-modal-btn')) {
            handleOpenModal(e.target.closest('.open-modal-btn'));
        } else if (e.target.closest('.generate-barcode-btn')) {
            const input = e.target.closest('.input-group').querySelector('input');
            generateRandomBarcode(input);
        }
    });

    document.getElementById('save-selection').addEventListener('click', handleSaveSelection);

    // Toggles
    setupToggle('enable_initial_stock', '.initial-stock-column', '');
    setupToggle('enable_variants', '.variants-column', '');

    document.getElementById('enable_variable_purchase_prices').addEventListener('change', updateMainPurchasePriceVisibility);
    document.getElementById('enable_variable_selling_prices').addEventListener('change', updateMainSellingPriceColumnsVisibility);


    function setupToggle(toggleId, columnClass, requiredInputs = '') {
        document.getElementById(toggleId).addEventListener('change', function () {
            const isEnabled = this.checked;
            document.querySelectorAll(columnClass).forEach(col => col.style.display = isEnabled ? '' : 'none');
            if (requiredInputs) {
                document.querySelectorAll(requiredInputs).forEach(input => {
                    if (isEnabled) {
                        input.setAttribute('required', 'required');
                    } else {
                        input.removeAttribute('required');
                    }
                });
            }
            
            if (toggleId === 'enable_variants') {
                const isEnabled = this.checked;
                document.getElementById('variable-prices-container').style.display = isEnabled ? 'block' : 'none';
                 // Also update the purchase price container visibility based on the initial stock state
                document.getElementById('variable-purchase-price-container').style.display = document.getElementById('enable_initial_stock').checked ? 'block' : 'none';

                if (!isEnabled) {
                    document.getElementById('enable_variable_purchase_prices').checked = false;
                    document.getElementById('enable_variable_selling_prices').checked = false;
                    // Manually trigger change to re-show main form prices
                    updateMainPurchasePriceVisibility();
                    updateMainSellingPriceColumnsVisibility();
                }

                document.querySelectorAll('.normal-barcode-cell').forEach(cell => {
                    cell.style.display = isEnabled ? 'none' : '';
                });
                document.querySelectorAll('.variants-column').forEach(cell => {
                    cell.style.display = isEnabled ? '' : 'none';
                });
                document.querySelectorAll('.main-quantity-cell').forEach(cell => {
                    cell.style.display = isEnabled ? 'none' : (document.getElementById('enable_initial_stock').checked ? '' : 'none');
                });

                 // Manually update visibilities when variants are toggled.
                 updateMainPurchasePriceVisibility();
                 updateMainSellingPriceColumnsVisibility();
            }

            if(toggleId === 'enable_initial_stock'){
                const isEnabled = this.checked;
                 document.getElementById('variable-purchase-price-container').style.display = isEnabled && document.getElementById('enable_variants').checked ? 'block' : 'none';
                 if(!isEnabled) {
                    document.getElementById('enable_variable_purchase_prices').checked = false;
                 }

                // Update columns related to initial stock
                document.querySelectorAll('.initial-stock-column').forEach(col => {
                    // This specifically targets the quantity column to hide/show it.
                    if (col.classList.contains('main-quantity-cell')) {
                         col.style.display = isEnabled && !document.getElementById('enable_variants').checked ? '' : 'none';
                    }
                });

                updateMainPurchasePriceVisibility();

                if(document.getElementById('enable_variants').checked){
                    document.querySelectorAll('.main-quantity-cell').forEach(cell => cell.style.display = 'none');
                }
            }
        });
    }

    // Row Management
    function createProductRow(index) {
        const tr = document.createElement('tr');
        tr.className = 'product-row';
        tr.setAttribute('data-index', index);

        let priceInputs = '';
        priceTypes.forEach(pt => {
            if (pt.name === 'سعر رئيسي') {
                priceInputs += `
                    <td class="price-column price-type-${pt.id} main-price-cell">
                        <input type="number" class="form-control" name="products[${index}][prices][${pt.id}]" step="0.01" min="0" placeholder="0.00" required>
                    </td>`;
            }
        });

        tr.innerHTML = `
            <td class="product-number">${index + 1}</td>
            <td><input type="text" class="form-control" name="products[${index}][name]" placeholder="اسم المنتج" required></td>
            <td>
                <div class="normal-barcode-cell">
                    <div class="input-group">
                        <input type="text" class="form-control" name="products[${index}][barcode]" placeholder="اتركه فارغاً للتوليد التلقائي">
                        <button type="button" class="btn btn-outline-secondary generate-barcode-btn"><i class="fas fa-magic"></i></button>
                    </div>
                </div>
            </td>
            <td class="variants-column" style="display:none;">
                <button type="button" class="btn btn-sm btn-primary open-modal-btn w-100" data-type="variants">
                    <i class="fas fa-puzzle-piece me-1"></i> إدارة المتغيرات (<span class="variants-count">0</span>)
                </button>
                <input type="hidden" name="products[${index}][variants]" class="selected-variants-input">
            </td>
            <td class="initial-stock-column main-price-cell" style="display:none;" width="10%"><input type="number" class="form-control purchase-price" name="products[${index}][purchase_price]" step="0.01" min="0" placeholder="0.00"></td>
            <td class="initial-stock-column main-quantity-cell" style="display:none;"><input type="number" class="form-control initial-quantity" name="products[${index}][initial_quantity]" step="0.01" min="0" placeholder="0.00"></td>
            ${priceInputs}
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-product-row"><i class="fas fa-trash"></i></button></td>
        `;
        return tr;
    }

    function addProductRow() {
        const index = productsContainer.children.length;
        const newRow = createProductRow(index);
        productsContainer.appendChild(newRow);
        updateTogglesForRow(newRow);
        updateMainSellingPriceColumnsVisibility();
        updateMainPurchasePriceVisibility();
    }

    function handleRemoveRow(button) {
        if (productsContainer.children.length > 1) {
            button.closest('.product-row').remove();
            updateRowIndexes();
                } else {
            alert('يجب أن يكون هناك منتج واحد على الأقل.');
        }
    }

    function updateRowIndexes() {
        Array.from(productsContainer.children).forEach((row, index) => {
            row.dataset.index = index;
            row.querySelector('.product-number').textContent = index + 1;
            row.querySelectorAll('[name^="products"]').forEach(input => {
                input.name = input.name.replace(/products\[\d+\]/, `products[${index}]`);
            });
        });
    }

    function updateTogglesForRow(row) {
        const stockEnabled = document.getElementById('enable_initial_stock').checked;
        const variantsEnabled = document.getElementById('enable_variants').checked;
        
        // تحديث عرض أعمدة الرصيد الافتتاحي
        row.querySelectorAll('.initial-stock-column').forEach(col => col.style.display = stockEnabled ? '' : 'none');
        
        // تحديث عرض أعمدة المتغيرات
        row.querySelectorAll('.variants-column').forEach(col => col.style.display = variantsEnabled ? '' : 'none');
        
        // تحديث عرض زر إدارة الباركودات وحقل الباركود العادي
        row.querySelector('.normal-barcode-cell').style.display = variantsEnabled ? 'none' : '';
    }

    // Modal Handling
    function handleOpenModal(button) {
        const row = button.closest('.product-row');
        currentRowIndex = row.dataset.index;
        currentSelectionType = button.dataset.type;

        if (currentSelectionType === 'variants') {
            const baseProductName = row.querySelector('input[name$="[name]"]').value || 'المنتج';
            document.getElementById('selection-modal-title').textContent = `متغيرات المنتج: ${baseProductName}`;
            
            const variantsInput = row.querySelector('.selected-variants-input');
            const existingVariants = variantsInput.value ? JSON.parse(variantsInput.value) : [];
            
            const modalBody = document.getElementById('selection-modal-list');
            const arePurchasePricesVariable = document.getElementById('enable_initial_stock').checked && document.getElementById('enable_variable_purchase_prices').checked;
            const areSellingPricesVariable = document.getElementById('enable_variable_selling_prices').checked;
            
            let priceHeaderHtml = '';
            if (arePurchasePricesVariable) {
                priceHeaderHtml += `<th class="bg-light">سعر الشراء</th>`;
            }
            if (areSellingPricesVariable) {
                priceTypes.forEach(pt => {
                    if (pt.name === 'سعر رئيسي') {
                        priceHeaderHtml += `<th class="bg-light">${pt.name}</th>`;
                    }
                });
            }

            modalBody.innerHTML = `
                <div class="row gx-2 mb-3 align-items-end">
                    <div class="col">
                        <label class="form-label small">اللون</label>
                        <select id="variant-color-select" class="form-select form-select-sm" ${!{{$showColors}} ? 'disabled' : ''}>
                            <option value="">-- بدون لون --</option>
                            ${colors.map(color => `<option value="${color.id}">${color.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label small">المقاس</label>
                        <select id="variant-size-select" class="form-select form-select-sm" ${!{{$showSizes}} ? 'disabled' : ''}>
                            <option value="">-- بدون مقاس --</option>
                            ${sizes.map(size => `<option value="${size.id}">${size.name}</option>`).join('')}
                        </select>
                    </div>
                    ${document.getElementById('enable_initial_stock').checked ? `
                    <div class="col-md-2">
                        <label class="form-label small">الكمية</label>
                        <input type="number" id="variant-quantity-input" class="form-control form-control-sm" value="1" min="0" step="1">
                    </div>
                    ` : ''}
                    <div class="col">
                        <label class="form-label small">الباركود</label>
                        <input type="text" id="variant-barcode-input" class="form-control form-control-sm" placeholder="اختياري">
                    </div>
                    <div class="col-auto">
                        <button type="button" id="add-variant-btn" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> إضافة
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="generate-all-variant-barcodes">
                        <i class="fas fa-magic me-1"></i> توليد باركودات للحقول الفارغة
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="variants-table">
                        <thead class="table-light">
                            <tr>
                                <th>اللون</th>
                                <th>المقاس</th>
                                ${document.getElementById('enable_initial_stock').checked ? `<th width="100">الكمية</th>` : ''}
                                <th>الباركود</th>
                                ${priceHeaderHtml}
                                <th width="100" class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${existingVariants.map(v => {
                                let priceCells = '';
                                if (arePurchasePricesVariable) {
                                    priceCells += `<td><input type="number" class="form-control form-control-sm variant-purchase-price" value="${v.purchase_price || ''}" min="0" step="0.01"></td>`;
                                }
                                if(areSellingPricesVariable){
                                    priceTypes.forEach(pt => {
                                        if (pt.name === 'سعر رئيسي') {
                                            const priceVal = v.prices && v.prices[pt.id] ? v.prices[pt.id] : '';
                                            priceCells += `<td><input type="number" class="form-control form-control-sm variant-sell-price" data-price-type-id="${pt.id}" value="${priceVal}" min="0" step="0.01"></td>`;
                                        }
                                    });
                                }

                                return `
                                <tr data-color-id="${v.colorId || ''}" data-size-id="${v.sizeId || ''}">
                                    <td>${v.colorId ? colors.find(c => c.id === v.colorId)?.name || '-' : '-'}</td>
                                    <td>${v.sizeId ? sizes.find(s => s.id === v.sizeId)?.name || '-' : '-'}</td>
                                    ${document.getElementById('enable_initial_stock').checked ? 
                                        `<td><input type="number" class="form-control form-control-sm variant-quantity" value="${v.quantity || 1}" min="0"></td>` : ''}
                                    <td>
                                        <input type="text" class="form-control form-control-sm variant-barcode" value="${v.barcode || ''}" placeholder="تلقائي">
                                    </td>
                                    ${priceCells}
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary generate-single-barcode" title="توليد باركود"><i class="fas fa-magic"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger remove-variant" title="إزالة"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            `}).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-2 ${existingVariants.length === 0 ? '' : 'd-none'}" id="no-variants-message">
                    <p class="text-muted">لم يتم إضافة متغيرات بعد.</p>
                </div>
            `;
            
            const variantsTable = document.getElementById('variants-table');
            
            document.getElementById('add-variant-btn').addEventListener('click', addVariant);
            
            document.getElementById('generate-all-variant-barcodes').addEventListener('click', () => {
                variantsTable.querySelectorAll('tbody .variant-barcode').forEach(input => {
                    if (!input.value) {
                        generateRandomBarcode(input);
                    }
                });
            });

            variantsTable.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-variant');
                const generateBtn = e.target.closest('.generate-single-barcode');

                if (removeBtn) {
                    removeBtn.closest('tr').remove();
                    if (variantsTable.querySelector('tbody').children.length === 0) {
                        document.getElementById('no-variants-message').classList.remove('d-none');
                    }
                } else if (generateBtn) {
                    const input = generateBtn.closest('tr').querySelector('.variant-barcode');
                    generateRandomBarcode(input);
                }
            });
            
            selectionModal.show();
        }
    }

    function addVariant() {
        const colorSelect = document.getElementById('variant-color-select');
        const sizeSelect = document.getElementById('variant-size-select');
        const barcodeInput = document.getElementById('variant-barcode-input');
        const isInitialStockEnabled = document.getElementById('enable_initial_stock').checked;
        
        const colorId = colorSelect.value;
        const sizeId = sizeSelect.value;
        const barcode = barcodeInput.value.trim();
        const quantity = isInitialStockEnabled ? document.getElementById('variant-quantity-input').value : 0;

        if (!colorId && !sizeId) {
            alert('يجب اختيار لون أو مقاس على الأقل');
            return;
        }
        
        const isDuplicate = Array.from(document.querySelectorAll('#variants-table tbody tr')).some(row => {
            return row.dataset.colorId === colorId && row.dataset.sizeId === sizeId;
        });
        
        if (isDuplicate) {
            alert('هذا المتغير موجود بالفعل');
            return;
        }
        
        const tableBody = document.getElementById('variants-table').querySelector('tbody');
        const colorName = colorId ? colors.find(c => c.id === parseInt(colorId))?.name || '-' : '-';
        const sizeName = sizeId ? sizes.find(s => s.id === parseInt(sizeId))?.name || '-' : '-';
        
        const arePurchasePricesVariable = document.getElementById('enable_initial_stock').checked && document.getElementById('enable_variable_purchase_prices').checked;
        const areSellingPricesVariable = document.getElementById('enable_variable_selling_prices').checked;
        let priceCells = '';
        if (arePurchasePricesVariable) {
            priceCells += `<td><input type="number" class="form-control form-control-sm variant-purchase-price" min="0" step="0.01"></td>`;
        }
        if (areSellingPricesVariable) {
             priceTypes.forEach(pt => {
                if (pt.name === 'سعر رئيسي') {
                    priceCells += `<td><input type="number" class="form-control form-control-sm variant-sell-price" data-price-type-id="${pt.id}" min="0" step="0.01"></td>`;
                }
            });
        }

        const newRow = document.createElement('tr');
        newRow.dataset.colorId = colorId;
        newRow.dataset.sizeId = sizeId;
        
        newRow.innerHTML = `
            <td>${colorName}</td>
            <td>${sizeName}</td>
            ${isInitialStockEnabled ? 
                `<td><input type="number" class="form-control form-control-sm variant-quantity" value="${quantity}" min="0"></td>` 
                : ''}
            <td>
                <input type="text" class="form-control form-control-sm variant-barcode" value="${barcode}" placeholder="تلقائي">
            </td>
            ${priceCells}
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-primary generate-single-barcode" title="توليد باركود"><i class="fas fa-magic"></i></button>
                <button type="button" class="btn btn-sm btn-danger remove-variant" title="إزالة"><i class="fas fa-times"></i></button>
            </td>
        `;
        
        tableBody.appendChild(newRow);
        
        document.getElementById('no-variants-message').classList.add('d-none');
        
        colorSelect.value = '';
        sizeSelect.value = '';
        barcodeInput.value = '';
        
        // إعادة تعيين حقل الكمية إذا كان موجودًا
        if (isInitialStockEnabled && document.getElementById('variant-quantity-input')) {
            document.getElementById('variant-quantity-input').value = '1';
        }
    }

    function handleSaveSelection() {
        if (currentSelectionType === 'variants') {
            const row = productsContainer.querySelector(`[data-index="${currentRowIndex}"]`);
            const variantsInput = row.querySelector('.selected-variants-input');
            
            const variants = [];
            const arePurchasePricesVariable = document.getElementById('enable_initial_stock').checked && document.getElementById('enable_variable_purchase_prices').checked;
            const areSellingPricesVariable = document.getElementById('enable_variable_selling_prices').checked;

            document.querySelectorAll('#variants-table tbody tr').forEach(tr => {
                const colorId = tr.dataset.colorId ? parseInt(tr.dataset.colorId) : null;
                const sizeId = tr.dataset.sizeId ? parseInt(tr.dataset.sizeId) : null;
                let barcode = tr.querySelector('.variant-barcode').value.trim();
                
                // If barcode is empty, generate a random one to ensure we always have a value
                if (!barcode) {
                    barcode = Math.floor(1000000000000 + Math.random() * 9000000000000).toString();
                    tr.querySelector('.variant-barcode').value = barcode;
                }
                
                // Safely get quantity value, handling cases when initial stock is disabled
                const quantityElement = tr.querySelector('.variant-quantity');
                const quantity = quantityElement ? quantityElement.value : 0;
                console.log('Saving variant with barcode:', barcode);
                
                const variantData = { colorId, sizeId, barcode, quantity };

                if (arePurchasePricesVariable) {
                    const purchasePriceInput = tr.querySelector('.variant-purchase-price');
                    if(purchasePriceInput){
                        variantData.purchase_price = purchasePriceInput.value;
                    }
                }
                
                if(areSellingPricesVariable){
                    variantData.prices = {};
                    tr.querySelectorAll('.variant-sell-price').forEach(priceInput => {
                        variantData.prices[priceInput.dataset.priceTypeId] = priceInput.value;
                    });
                }

                variants.push(variantData);
            });
            
            variantsInput.value = JSON.stringify(variants);
            
            row.querySelector('.variants-count').textContent = variants.length;
            
            selectionModal.hide();
        }
    }
    
    // Barcode Generation
        function generateRandomBarcode(input) {
        const barcode = Math.floor(1000000000000 + Math.random() * 9000000000000).toString();
            input.value = barcode;
        }

        document.getElementById('bulk-product-form').addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.matches('input[name*="[barcode]"]')) {
                event.preventDefault();
            }
        });

        document.getElementById('selection-modal').addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && (event.target.id === 'variant-barcode-input' || event.target.classList.contains('variant-barcode'))) {
                event.preventDefault();
            }
        });
    });
</script>
<style>
    .variants-column .btn {
        white-space: nowrap;
    }
</style>
@endpush 