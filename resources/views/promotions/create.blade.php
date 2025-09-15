@extends('layouts.app')

@section('title', 'إضافة عرض ترويجي جديد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- بطاقة التعليمات -->
            <div class="card card-info mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> تعليمات</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p>يرجى اتباع الخطوات التالية لإنشاء عرض ترويجي جديد:</p>
                    <ol>
                        <li>أدخل اسم العرض الترويجي ووصفه.</li>
                        <li>اختر نوع العرض الترويجي (خصم بسيط، اشتر X واحصل على Y، إلخ).</li>
                        <li>حدد على ماذا ينطبق العرض (منتج محدد، تصنيف، جميع المنتجات).</li>
                        <li>أدخل قيمة الخصم (مطلوب للخصم البسيط).</li>
                        <li>حدد تاريخ بدء وانتهاء العرض (اختياري).</li>
                        <li>أدخل الحد الأدنى للشراء والحد الأقصى للخصم إذا كان ذلك مناسبًا.</li>
                        <li>حدد المنتجات أو التصنيفات المرتبطة بالعرض إذا كان ذلك مطلوبًا.</li>
                        <li>اختر العملاء المحددين للعرض إذا كان العرض مخصصًا لعملاء معينين.</li>
                    </ol>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إضافة عرض ترويجي جديد</h3>
                </div>
                <form action="{{ route('promotions.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @include('partials.flash_messages')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">اسم العرض <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="promotion_type">نوع العرض <span class="text-danger">*</span></label>
                                    <select class="form-control @error('promotion_type') is-invalid @enderror" id="promotion_type" name="promotion_type" required>
                                        <option value="">-- اختر نوع العرض --</option>
                                        <option value="simple_discount" {{ old('promotion_type') == 'simple_discount' ? 'selected' : '' }}>خصم بسيط</option>
                                        <option value="buy_x_get_y" {{ old('promotion_type') == 'buy_x_get_y' ? 'selected' : '' }}>اشتر X واحصل على Y</option>
                                        <option value="spend_x_save_y" {{ old('promotion_type') == 'spend_x_save_y' ? 'selected' : '' }}>أنفق X ووفر Y</option>
                                        <option value="coupon_code" {{ old('promotion_type') == 'coupon_code' ? 'selected' : '' }}>كوبون خصم</option>
                                    </select>
                                    @error('promotion_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="applies_to">ينطبق على <span class="text-danger">*</span></label>
                                    <select class="form-control @error('applies_to') is-invalid @enderror" id="applies_to" name="applies_to" required>
                                        <option value="">-- اختر --</option>
                                        <option value="product" {{ old('applies_to') == 'product' ? 'selected' : '' }}>منتج محدد</option>
                                        <option value="category" {{ old('applies_to') == 'category' ? 'selected' : '' }}>تصنيف</option>
                                        <option value="all" {{ old('applies_to') == 'all' ? 'selected' : '' }}>جميع المنتجات</option>
                                    </select>
                                    @error('applies_to')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_value">قيمة الخصم</label>
                                    <input type="number" step="0.01" class="form-control @error('discount_value') is-invalid @enderror" id="discount_value" name="discount_value" value="{{ old('discount_value') }}">
                                    @error('discount_value')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">تاريخ البدء</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                    @error('start_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">تاريخ الانتهاء</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="minimum_purchase">الحد الأدنى للشراء</label>
                                    <input type="number" step="0.01" class="form-control @error('minimum_purchase') is-invalid @enderror" id="minimum_purchase" name="minimum_purchase" value="{{ old('minimum_purchase') }}">
                                    @error('minimum_purchase')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="maximum_discount">الحد الأقصى للخصم</label>
                                    <input type="number" step="0.01" class="form-control @error('maximum_discount') is-invalid @enderror" id="maximum_discount" name="maximum_discount" value="{{ old('maximum_discount') }}">
                                    @error('maximum_discount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="usage_limit">حد الاستخدام</label>
                                    <input type="number" class="form-control @error('usage_limit') is-invalid @enderror" id="usage_limit" name="usage_limit" value="{{ old('usage_limit') }}">
                                    @error('usage_limit')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">الحالة</label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">نشط</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="product-selection d-none" id="product-selection">
                            <div class="form-group">
                                <label for="products">المنتجات</label>
                                <select class="form-control select2 @error('products') is-invalid @enderror" id="products" name="products[]" multiple>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ in_array($product->id, old('products', [])) ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @error('products')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="category-selection d-none" id="category-selection">
                            <div class="form-group">
                                <label for="categories">التصنيفات</label>
                                <select class="form-control select2 @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('categories')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="customer-selection">
                            <div class="form-group">
                                <label for="customers">العملاء (اختياري - إذا كان العرض محدد لعملاء معينين)</label>
                                <select class="form-control select2 @error('customers') is-invalid @enderror" id="customers" name="customers[]" multiple>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ in_array($customer->id, old('customers', [])) ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customers')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- قسم خاص بعرض اشتر X واحصل على Y -->
                        <div id="buy_x_get_y_section" class="d-none">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">تفاصيل عرض اشتر X واحصل على Y</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>المنتج الذي يجب شراؤه (X)</h5>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="buy_product_id">المنتج <span class="text-danger">*</span></label>
                                                <select class="form-control select2 @error('buy_product_id') is-invalid @enderror" id="buy_product_id" name="buy_product_id">
                                                    <option value="">-- اختر المنتج --</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" {{ old('buy_product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('buy_product_id')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="buy_quantity">الكمية <span class="text-danger">*</span></label>
                                                <input type="number" min="1" step="1" class="form-control @error('buy_quantity') is-invalid @enderror" id="buy_quantity" name="buy_quantity" value="{{ old('buy_quantity', 1) }}">
                                                @error('buy_quantity')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="buy_unit_id">الوحدة <span class="text-danger">*</span></label>
                                                <select class="form-control @error('buy_unit_id') is-invalid @enderror" id="buy_unit_id" name="buy_unit_id">
                                                    <option value="">-- اختر الوحدة --</option>
                                                    @foreach($units as $unit)
                                                        <option value="{{ $unit->id }}" {{ old('buy_unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('buy_unit_id')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <h5>المنتج الذي سيتم الحصول عليه مجانًا (Y)</h5>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="get_product_id">المنتج <span class="text-danger">*</span></label>
                                                <select class="form-control select2 @error('get_product_id') is-invalid @enderror" id="get_product_id" name="get_product_id">
                                                    <option value="">-- اختر المنتج --</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" {{ old('get_product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('get_product_id')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="get_quantity">الكمية <span class="text-danger">*</span></label>
                                                <input type="number" min="1" step="1" class="form-control @error('get_quantity') is-invalid @enderror" id="get_quantity" name="get_quantity" value="{{ old('get_quantity', 1) }}">
                                                @error('get_quantity')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="get_unit_id">الوحدة <span class="text-danger">*</span></label>
                                                <select class="form-control @error('get_unit_id') is-invalid @enderror" id="get_unit_id" name="get_unit_id">
                                                    <option value="">-- اختر الوحدة --</option>
                                                    @foreach($units as $unit)
                                                        <option value="{{ $unit->id }}" {{ old('get_unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('get_unit_id')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- قسم خاص بعرض أنفق X ووفر Y -->
                        <div id="spend_x_save_y_section" class="d-none">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">تفاصيل عرض أنفق X ووفر Y</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="minimum_purchase">الحد الأدنى للشراء <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control @error('minimum_purchase') is-invalid @enderror" id="minimum_purchase_spend" name="minimum_purchase" value="{{ old('minimum_purchase') }}">
                                                @error('minimum_purchase')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="discount_value">قيمة الخصم <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control @error('discount_value') is-invalid @enderror" id="discount_value_spend" name="discount_value" value="{{ old('discount_value') }}">
                                                @error('discount_value')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- قسم خاص بالخصم البسيط -->
                        <div id="simple_discount_section" class="d-none">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">تفاصيل الخصم البسيط</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="discount_value">نسبة الخصم (%) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control @error('discount_value') is-invalid @enderror" id="discount_value_simple" name="discount_value" value="{{ old('discount_value') }}">
                                                @error('discount_value')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <a href="{{ route('promotions.index') }}" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsContainer = document.getElementById('products-container');
    const addProductBtn = document.getElementById('add-product-btn');
    const form = document.getElementById('bulk-products-form');
    
    // For variant/unit selection modal
    let selectionModal;
    let currentSelectionType = '';
    let currentRowIndex = -1;
    const selectionModalElement = document.getElementById('selectionModal');
    if (selectionModalElement) {
        selectionModal = new bootstrap.Modal(selectionModalElement);
    }
    
    // Data from backend
    const categories = @json($categories);
    const units = @json($units);
    const priceTypes = @json($priceTypes);
    const colors = @json($colors);
    const sizes = @json($sizes);

    function clearErrors() {
        // Remove all previous invalid states and error messages
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    function displayServerErrors(errors) {
        clearErrors();
        let firstErrorElement = null;

        for (const key in errors) {
            const parts = key.split('.');
            let input = null;
            let errorMessage = errors[key].join(', ');

            if (parts[0] === 'products' && parts.length > 2) {
                const index = parseInt(parts[1]);
                const fieldName = parts.slice(2).join('.');
                const row = productsContainer.querySelectorAll('.product-row')[index];

                if (row) {
                    const selectorMap = {
                        'name': '.product-name', 'category_id': '.category-select', 'unit_id': '.unit-select',
                        'purchase_price': '.purchase-price', 'selling_price': '.selling-price', 'initial_stock': '.initial-stock'
                    };
                    const selector = selectorMap[fieldName];
                    if (selector) input = row.querySelector(selector);
                }
            } else if (document.getElementById(key)) {
                input = document.getElementById(key);
            }

            if (input) {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                errorDiv.textContent = errorMessage;
                // Insert after the input's parent if it's a specific container, or just after the input.
                if (input.parentNode.classList.contains('input-group')) {
                     input.parentNode.parentNode.appendChild(errorDiv);
                } else {
                     input.parentNode.appendChild(errorDiv);
                }


                if (!firstErrorElement) firstErrorElement = input;
            } else {
                // Fallback for errors not associated with a specific input
                alert(errorMessage);
            }
        }

        if (firstErrorElement) {
            firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function addProductRow() {
        try {
            const index = productsContainer.children.length;
            const newRow = document.createElement('div');
            newRow.className = 'row product-row mb-3 align-items-center';
            newRow.dataset.index = index;
            
            const sellingPriceInputs = priceTypes.map(pt => {
                if(pt.name === 'سعر رئيسي'){
                    return `<div class="col">
                                <label class="form-label small">سعر البيع (${pt.name})</label>
                                <input type="number" class="form-control selling-price" data-price-type-id="${pt.id}" step="0.01" min="0">
                            </div>`;
                }
                return '';
            }).join('');

            newRow.innerHTML = `
                <div class="col-md-2">
                    <label class="form-label small">اسم المنتج <span class="text-danger">*</span></label>
                    <input type="text" class="form-control product-name" required>
                </div>
                <div class="col">
                    <label class="form-label small">الصنف</label>
                    <select class="form-select category-select">
                        <option value="">-- اختر صنف --</option>
                        ${categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                    </select>
                </div>
                <div class="col">
                    <label class="form-label small">سعر الشراء</label>
                    <input type="number" class="form-control purchase-price" step="0.01" min="0" disabled>
                </div>
                ${sellingPriceInputs}
                <div class="col">
                    <label class="form-label small">الكمية الافتتاحية</label>
                    <input type="number" class="form-control initial-stock" value="0" min="0" step="1" disabled>
                </div>
                <div class="col-md-2 variants-column">
                    <label class="form-label small">المتغيرات</label>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary select-variants-btn">
                            <i class="fas fa-tags me-1"></i>
                            <span class="variants-count">0</span> متغيرات
                        </button>
                    </div>
                    <input type="hidden" class="selected-variants-input">
                </div>
                <div class="col-auto">
                    <label class="form-label small invisible">إجراء</label>
                    <button type="button" class="btn btn-danger btn-sm remove-product-btn"><i class="fas fa-trash"></i></button>
                </div>
            `;
            
            productsContainer.appendChild(newRow);
            updateFormState(); // To enable/disable inputs based on checkboxes
        } catch (error) {
            console.error("Error adding product row:", error);
            alert("حدث خطأ أثناء إضافة صف منتج جديد.");
        }
    }

    function removeProductRow(button) {
        try {
            button.closest('.product-row').remove();
        } catch (error) {
            console.error("Error removing product row:", error);
            alert("حدث خطأ أثناء إزالة صف المنتج.");
        }
    }

    // Event Listeners
    addProductBtn.addEventListener('click', addProductRow);

    productsContainer.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-product-btn');
        const variantsBtn = e.target.closest('.select-variants-btn');

        if (removeBtn) {
            removeProductRow(removeBtn);
        } else if (variantsBtn) {
            openSelectionModal('variants', variantsBtn);
        }
    });

    document.getElementById('save-selection-btn').addEventListener('click', handleSaveSelection);

    document.getElementById('bulk-products-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;

        try {
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جارٍ الحفظ...`;

            clearErrors(); 

            const productsData = [];
            const productRows = productsContainer.querySelectorAll('.product-row');
            let hasClientError = false;

            productRows.forEach((row, index) => {
                const nameInput = row.querySelector('.product-name');
                const name = nameInput.value.trim();

                if (!name) {
                    nameInput.classList.add('is-invalid');
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback d-block';
                    errorDiv.textContent = 'اسم المنتج مطلوب.';
                    nameInput.parentNode.appendChild(errorDiv);
                    hasClientError = true;
                }

                const categoryId = row.querySelector('.category-select').value;
                const purchasePrice = row.querySelector('.purchase-price').value;
                const variants = row.querySelector('.selected-variants-input').value;

                let sellingPrices = {};
                row.querySelectorAll('.selling-price').forEach(input => {
                    sellingPrices[input.dataset.priceTypeId] = input.value;
                });
                
                productsData.push({
                    name,
                    category_id: categoryId,
                    purchase_price: purchasePrice,
                    selling_prices: sellingPrices,
                    initial_stock: row.querySelector('.initial-stock').value,
                    variants: variants ? JSON.parse(variants) : [],
                });
            });

            if (hasClientError) {
                alert('الرجاء التأكد من إدخال اسم لجميع المنتجات.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            fetch('{{ route("products.bulk.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    products: productsData,
                    enable_initial_stock: document.getElementById('enable_initial_stock').checked,
                    initial_stock_date: document.getElementById('initial_stock_date').value,
                })
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    alert(data.message);
                    window.location.href = '{{ route("products.index") }}';
                } else {
                    if (data.errors) {
                        displayServerErrors(data.errors);
                        alert('يرجى تصحيح الأخطاء الموضحة والمحاولة مرة أخرى.');
                    } else {
                        alert(data.message || 'حدث خطأ غير متوقع من الخادم.');
                    }
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ فادح أثناء محاولة حفظ المنتجات. قد يكون هناك مشكلة في الشبكة أو خطأ في الخادم.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        } catch (err) {
            console.error('Client-side error before submission:', err);
            alert('حدث خطأ غير متوقع أثناء تجهيز البيانات. يرجى التحقق من المدخلات.');
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });

    // ... (updateFormState and other listeners remain the same) ...
    function updateFormState() {
        const isInitialStockEnabled = document.getElementById('enable_initial_stock').checked;
        
        document.getElementById('initial-stock-date-container').style.display = isInitialStockEnabled ? 'block' : 'none';
        document.getElementById('variable-purchase-price-container').style.display = isInitialStockEnabled ? 'block' : 'none';

        document.querySelectorAll('.product-row').forEach(row => {
            row.querySelector('.purchase-price').disabled = !isInitialStockEnabled;
            row.querySelector('.initial-stock').disabled = !isInitialStockEnabled;
        });
    }

    document.getElementById('enable_initial_stock').addEventListener('change', updateFormState);
    document.getElementById('enable_variable_selling_prices').addEventListener('change', updateFormState);
    
    // Initial setup
    updateFormState();
    addProductRow(); // Add the first row initially

    // Variant/Unit Modal Logic
    function openSelectionModal(type, button) {
        try {
            currentSelectionType = type;
            const row = button.closest('.product-row');
            currentRowIndex = parseInt(row.dataset.index);
            const modalTitle = selectionModalElement.querySelector('.modal-title');
            const modalBody = selectionModalElement.querySelector('.modal-body');
            
            if (type === 'variants') {
                modalTitle.textContent = 'تحديد المتغيرات للمنتج: ' + row.querySelector('.product-name').value;
                
                const variantsInput = row.querySelector('.selected-variants-input');
                const existingVariants = JSON.parse(variantsInput.value || '[]');

                const arePurchasePricesVariable = document.getElementById('enable_initial_stock').checked && document.getElementById('enable_variable_purchase_prices').checked;
                const areSellingPricesVariable = document.getElementById('enable_variable_selling_prices').checked;
                
                let priceHeaderHtml = '';
                if (arePurchasePricesVariable) {
                    priceHeaderHtml += '<th>سعر الشراء</th>';
                }
                if (areSellingPricesVariable) {
                    priceTypes.forEach(pt => {
                        if (pt.name === 'سعر رئيسي') {
                            priceHeaderHtml += `<th>سعر البيع (${pt.name})</th>`;
                        }
                    });
                }
                
                modalBody.innerHTML = `
                    <div class="row gx-2 mb-3 align-items-end">
                        <div class="col">
                            <label class="form-label small">اللون</label>
                            <select id="variant-color-select" class="form-select form-select-sm">
                                <option value="">-- بدون لون --</option>
                                ${colors.map(color => `<option value="${color.id}">${color.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label small">المقاس</label>
                            <select id="variant-size-select" class="form-select form-select-sm">
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
        } catch(error) {
            console.error("Error opening selection modal:", error);
            alert("حدث خطأ أثناء فتح نافذة التحديد. حاول تحديث الصفحة.");
            if (selectionModal) {
                selectionModal.hide();
            }
        }
    }

    function addVariant() {
        try {
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
            
            if (isInitialStockEnabled && document.getElementById('variant-quantity-input')) {
                document.getElementById('variant-quantity-input').value = '1';
            }
        } catch (error) {
            console.error("Error adding variant:", error);
            alert("حدث خطأ أثناء إضافة متغير.");
        }
    }

    function handleSaveSelection() {
        try {
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
                    
                    if (!barcode) {
                        barcode = Math.floor(1000000000000 + Math.random() * 9000000000000).toString();
                        tr.querySelector('.variant-barcode').value = barcode;
                    }
                    
                    const quantityInput = tr.querySelector('.variant-quantity');
                    const quantity = quantityInput ? quantityInput.value : null;

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
        } catch (error) {
            console.error("Error saving selection:", error);
            alert("حدث خطأ أثناء حفظ التحديد.");
            if (selectionModal) {
                selectionModal.hide();
            }
        }
    }
    
    // Barcode Generation
        function generateRandomBarcode(input) {
        const barcode = Math.floor(1000000000000 + Math.random() * 9000000000000).toString();
            input.value = barcode;
        }
    });
</script>
@endsection 