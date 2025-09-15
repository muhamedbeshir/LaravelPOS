@extends('layouts.app')

@section('title', 'إنشاء مرتجع مشتريات جديد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">إنشاء مرتجع مشتريات جديد</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('purchase-returns.store') }}" method="POST" id="purchase-return-form">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="supplier_id">المورد <span class="text-danger">*</span></label>
                                    <select class="form-control @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                                        <option value="">اختر المورد</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="purchase_id">فاتورة المشتريات الأصلية</label>
                                    <div class="input-group">
                                        <input type="hidden" id="purchase_id" name="purchase_id" value="{{ old('purchase_id') }}">
                                        <input type="text" class="form-control @error('purchase_id') is-invalid @enderror" id="purchase_display" placeholder="اختر فاتورة المشتريات" readonly>
                                        <button class="btn btn-primary" type="button" id="search-purchase-btn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    @error('purchase_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="employee_id">الموظف المستلم</label>
                                    <select class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id">
                                        <option value="">اختر الموظف</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('employee_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="return_date">تاريخ المرتجع <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('return_date') is-invalid @enderror" id="return_date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" required>
                                    @error('return_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="return_type">نوع المرتجع <span class="text-danger">*</span></label>
                                    <select class="form-control @error('return_type') is-invalid @enderror" id="return_type" name="return_type" required>
                                        <option value="partial" @selected(old('return_type') == 'partial')>مرتجع جزئي</option>
                                        <option value="full" @selected(old('return_type') == 'full')>مرتجع كامل</option>
                                        <option value="direct" @selected(old('return_type') == 'direct')>مرتجع مباشر</option>
                                    </select>
                                    @error('return_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="notes">ملاحظات</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="1">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <h4>الأصناف</h4>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="items-table">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الوحدة</th>
                                        <th>الكمية الأصلية</th>
                                        <th>المخزون الحالي</th>
                                        <th>الكمية المرتجعة</th>
                                        <th>سعر الشراء</th>
                                        <th>السبب</th>
                                        <th>الإجمالي</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                                        <tr id="item-row-template" class="d-none">
                        <td>
                            <select class="form-control item-product" name="items[0][product_id]" disabled>
                                <option value="">اختر المنتج</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control item-unit" name="items[0][unit_id]" disabled>
                                <option value="">اختر الوحدة</option>
                            </select>
                        </td>
                        <td>
                            <span class="item-original-quantity">0</span>
                            <input type="hidden" class="item-original-quantity-input" name="items[0][original_quantity]" value="0" disabled>
                        </td>
                        <td>
                            <span class="item-current-stock">0</span>
                            <input type="hidden" class="item-current-stock-input" name="items[0][current_stock]" value="0" disabled>
                        </td>
                        <td>
                            <input type="number" class="form-control item-quantity" name="items[0][quantity]" min="1" value="1" disabled>
                            <div class="invalid-feedback item-quantity-error"></div>
                        </td>
                        <td>
                            <input type="number" step="0.01" class="form-control item-price" name="items[0][purchase_price]" min="0.01" value="0" disabled>
                        </td>
                        <td>
                            <input type="text" class="form-control item-reason" name="items[0][reason]" placeholder="سبب الإرجاع" disabled>
                        </td>
                        <td>
                            <span class="item-total">0.00</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="9">
                                            <button type="button" class="btn btn-success btn-sm" id="add-item">
                                                <i class="fas fa-plus"></i> إضافة صنف
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 offset-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <h5>إجمالي المرتجع:</h5>
                                            <h5 id="total-amount">0.00</h5>
                                        </div>
                                        <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">حفظ المرتجع</button>
                            <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال البحث عن فاتورة -->
<div class="modal fade" id="searchPurchaseModal" tabindex="-1" aria-labelledby="searchPurchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchPurchaseModalLabel">بحث عن فاتورة مشتريات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="search-purchase-input" placeholder="رقم الفاتورة أو اسم المورد">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="search-purchase-btn-modal">بحث</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="purchase-search-results">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>المورد</th>
                                <th>التاريخ</th>
                                <th>إجمالي الفاتورة</th>
                                <th>اختيار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ستتم تعبئة هذا الجزء عبر AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <div id="purchase-search-loading" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p>جاري البحث...</p>
                </div>
                
                <div id="purchase-search-no-results" class="alert alert-warning d-none">
                    لم يتم العثور على نتائج
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Keep track of item count
        let itemCount = 0;
        
        // Add first item row on page load if creating a direct return
        if ($('#return_type').val() === 'direct') {
            addItemRow();
        }
        
        // Fix any existing invalid form controls
        fixFormControls();
        
        // Open purchase search modal
        $('#search-purchase-btn').click(function(e) {
            e.preventDefault();
            $('#searchPurchaseModal').modal('show');
            
            // Automatically search for invoices from the selected supplier
            $('#search-purchase-btn-modal').trigger('click');
        });
        
        // Handle purchase search
        $('#search-purchase-btn-modal').click(function() {
            const searchTerm = $('#search-purchase-input').val();
            const supplierId = $('#supplier_id').val();
            console.log('Searching for purchases with term:', searchTerm, 'for supplier:', supplierId);
            
            // Show loading
            $('#purchase-search-loading').removeClass('d-none');
            $('#purchase-search-results tbody').empty();
            $('#purchase-search-no-results').addClass('d-none');
            
            // Prepare search data
            const searchData = {};
            if (searchTerm) searchData.search = searchTerm;
            if (supplierId) searchData.supplier_id = supplierId;
            
            // Perform search
            $.ajax({
                url: '/api/purchases',
                type: 'GET',
                data: searchData,
                success: function(response) {
                    $('#purchase-search-loading').addClass('d-none');
                    console.log('Search response:', response);
                    
                    if (response.purchases && response.purchases.data && response.purchases.data.length > 0) {
                        console.log('Found purchases:', response.purchases.data.length);
                        response.purchases.data.forEach(function(purchase) {
                            const row = `
                                <tr>
                                    <td>${purchase.invoice_number}</td>
                                    <td>${purchase.supplier ? purchase.supplier.name : 'غير محدد'}</td>
                                    <td>${purchase.purchase_date}</td>
                                    <td>${purchase.total_amount}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success select-purchase" 
                                                data-id="${purchase.id}" 
                                                data-invoice="${purchase.invoice_number}" 
                                                data-supplier="${purchase.supplier_id}">
                                            اختيار
                                        </button>
                                    </td>
                                </tr>
                            `;
                            $('#purchase-search-results tbody').append(row);
                        });
                    } else {
                        console.log('No purchases found');
                        $('#purchase-search-no-results').removeClass('d-none');
                    }
                },
                error: function(error) {
                    console.error('Error searching purchases:', error);
                    $('#purchase-search-loading').addClass('d-none');
                    $('#purchase-search-no-results').removeClass('d-none');
                }
            });
        });
        
        // Handle purchase selection from search results
        $(document).on('click', '.select-purchase', function() {
            const purchaseId = $(this).data('id');
            const invoiceNumber = $(this).data('invoice');
            const supplierId = $(this).data('supplier');
            
            // Set the selected purchase
            $('#purchase_id').val(purchaseId);
            $('#purchase_display').val(invoiceNumber);
            $('#supplier_id').val(supplierId).prop('disabled', true);
            
            // Close modal
            $('#searchPurchaseModal').modal('hide');
            
            // Load purchase items
            loadPurchaseItems(purchaseId);
        });
        
        // Add new item row
        $('#add-item').click(function() {
            addItemRow();
        });
        
        // Remove item row
        $(document).on('click', '.remove-item', function() {
            if ($('#items-table tbody tr:visible').length > 1) {
                $(this).closest('tr').remove();
                calculateTotal();
            } else {
                alert('يجب أن يكون هناك صنف واحد على الأقل');
            }
        });
        
        // Update units when product changes
        $(document).on('change', '.item-product', function() {
            const productId = $(this).val();
            const row = $(this).closest('tr');
            
            if (productId) {
                // Load units for this product
                loadUnitsForProduct(productId, row);
                
                // Get product stock information
                $.ajax({
                    url: `/api/products/${productId}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const product = response.product;
                            row.find('.item-current-stock').text(product.stock_quantity);
                            row.find('.item-current-stock-input').val(product.stock_quantity);
                        }
                    },
                    error: function(error) {
                        console.error('Error fetching product details:', error);
                    }
                });
            } else {
                // Reset unit select
                row.find('.item-unit').empty().append('<option value="">اختر المنتج أولاً</option>');
                
                // Reset stock info
                row.find('.item-current-stock').text('0');
                row.find('.item-current-stock-input').val('0');
            }
            
            // Reset price and quantity
            row.find('.item-price').val(0);
            row.find('.item-quantity').val(1);
            row.find('.item-original-quantity').text('0');
            row.find('.item-original-quantity-input').val(0);
            
            calculateRowTotal(row);
        });
        
        // Update totals when quantity or price changes
        $(document).on('input', '.item-quantity, .item-price', function() {
            const row = $(this).closest('tr');
            
            // Validate quantity against available stock if needed
            if ($(this).hasClass('item-quantity')) {
                validateReturnQuantity(row);
            }
            
            calculateRowTotal(row);
        });
        
        // Handle return type change
        $('#return_type').change(function() {
            const returnType = $(this).val();
            
            // Clear items table
            $('#items-table tbody tr:not(#item-row-template)').remove();
            itemCount = 0;
            
            if (returnType === 'full') {
                // For full returns, purchase invoice is required
                $('#purchase_id').prop('required', true);
                $('#search-purchase-btn').prop('disabled', false);
                $('#purchase_display').prop('disabled', false);
            } else if (returnType === 'partial') {
                // For partial returns, purchase invoice is optional
                $('#purchase_id').prop('required', false);
                $('#search-purchase-btn').prop('disabled', false);
                $('#purchase_display').prop('disabled', false);
                addItemRow();
            } else {
                // For direct returns, no purchase invoice
                $('#purchase_id').prop('required', false);
                $('#purchase_id').val('');
                $('#purchase_display').val('');
                $('#search-purchase-btn').prop('disabled', true);
                $('#purchase_display').prop('disabled', true);
                $('#supplier_id').prop('disabled', false);
                addItemRow();
            }
        });
        
        // Submit form
        $('#purchase-return-form').submit(function(e) {
            // Make sure there's at least one item
            if ($('#items-table tbody tr:visible').length < 1) {
                e.preventDefault();
                alert('يجب إضافة صنف واحد على الأقل');
                return false;
            }
            
            // Make sure total amount is valid
            const totalAmount = parseFloat($('#total_amount_input').val());
            if (isNaN(totalAmount) || totalAmount <= 0) {
                e.preventDefault();
                alert('المبلغ الإجمالي يجب أن يكون أكبر من صفر');
                return false;
            }
            
            // Validate all return quantities
            let isValid = true;
            $('#items-table tbody tr:not(#item-row-template)').each(function() {
                if (!validateReturnQuantity($(this))) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('يوجد خطأ في كمية المرتجع لبعض الأصناف');
                return false;
            }
            
            // Enable disabled fields for submission
            $('#supplier_id').prop('disabled', false);
            
            // All good, continue submission
            return true;
        });
        
        // Function to add a new item row
        function addItemRow() {
            // Create a completely new row instead of cloning the template
            const newRow = $(`
                <tr>
                    <td>
                        <select class="form-control item-product" name="items[${itemCount}][product_id]" required>
                            <option value="">اختر المنتج</option>
                            ${generateProductOptions()}
                        </select>
                    </td>
                    <td>
                        <select class="form-control item-unit" name="items[${itemCount}][unit_id]" required>
                            <option value="">اختر الوحدة</option>
                        </select>
                    </td>
                    <td>
                        <span class="item-original-quantity">0</span>
                        <input type="hidden" class="item-original-quantity-input" name="items[${itemCount}][original_quantity]" value="0">
                    </td>
                    <td>
                        <span class="item-current-stock">0</span>
                        <input type="hidden" class="item-current-stock-input" name="items[${itemCount}][current_stock]" value="0">
                    </td>
                    <td>
                        <input type="number" class="form-control item-quantity" name="items[${itemCount}][quantity]" min="1" value="1" required>
                        <div class="invalid-feedback item-quantity-error"></div>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control item-price" name="items[${itemCount}][purchase_price]" min="0.01" value="0" required>
                    </td>
                    <td>
                        <input type="text" class="form-control item-reason" name="items[${itemCount}][reason]" placeholder="سبب الإرجاع">
                    </td>
                    <td>
                        <span class="item-total">0.00</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            
            // Append to table
            $('#items-table tbody').append(newRow);
            
            // Increment counter
            itemCount++;
            
            return newRow;
        }
        
        // Helper function to generate product options
        function generateProductOptions() {
            let options = '';
            $('#item-row-template').find('.item-product option').each(function() {
                const value = $(this).val();
                const text = $(this).text();
                options += `<option value="${value}">${text}</option>`;
            });
            return options;
        }
        
        // Function to fix form controls
        function fixFormControls() {
            // Remove any invalid form controls
            $('.is-invalid').removeClass('is-invalid');
        }
        
        // Function to load purchase items
        function loadPurchaseItems(purchaseId) {
            console.log('Loading purchase items for purchase ID:', purchaseId);
            // Clear existing items
            $('#items-table tbody tr:not(#item-row-template)').remove();
            itemCount = 0;
            
            // Get purchase details via AJAX
            $.ajax({
                url: `/api/purchase-returns/purchase/${purchaseId}`,
                type: 'GET',
                success: function(response) {
                    console.log('Purchase details response:', response);
                    if (response.success && response.purchase) {
                        // Set supplier
                        $('#supplier_id').val(response.purchase.supplier_id).prop('disabled', true);
                        
                        // Add items
                        if (response.purchase.items && response.purchase.items.length > 0) {
                            console.log('Found items:', response.purchase.items.length);
                            
                            // Process items sequentially
                            response.purchase.items.forEach(function(item) {
                                addPurchaseItem(item);
                            });
                            
                            // Calculate total after all items are added
                            setTimeout(calculateTotal, 500);
                        } else {
                            console.log('No items found in the purchase');
                        }
                    } else {
                        console.error('Invalid response format or purchase not found');
                    }
                },
                error: function(error) {
                    console.error('Error fetching purchase details:', error);
                }
            });
        }
        
        // Function to add a purchase item
        function addPurchaseItem(item) {
            // Create a new row with the item data
            const newRow = $(`
                <tr>
                    <td>
                        <select class="form-control item-product" name="items[${itemCount}][product_id]" required>
                            <option value="">اختر المنتج</option>
                            ${generateProductOptions()}
                        </select>
                    </td>
                    <td>
                        <select class="form-control item-unit" name="items[${itemCount}][unit_id]" required>
                            <option value="">جار التحميل...</option>
                        </select>
                    </td>
                    <td>
                        <span class="item-original-quantity">${item.quantity}</span>
                        <input type="hidden" class="item-original-quantity-input" name="items[${itemCount}][original_quantity]" value="${item.quantity}">
                    </td>
                    <td>
                        <span class="item-current-stock">0</span>
                        <input type="hidden" class="item-current-stock-input" name="items[${itemCount}][current_stock]" value="0">
                    </td>
                    <td>
                        <input type="number" class="form-control item-quantity" name="items[${itemCount}][quantity]" min="1" value="${item.quantity}" required>
                        <div class="invalid-feedback item-quantity-error"></div>
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control item-price" name="items[${itemCount}][purchase_price]" min="0.01" value="${item.purchase_price}" required>
                    </td>
                    <td>
                        <input type="text" class="form-control item-reason" name="items[${itemCount}][reason]" placeholder="سبب الإرجاع">
                    </td>
                    <td>
                        <span class="item-total">0.00</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            
            // Append to table
            $('#items-table tbody').append(newRow);
            
            // Set product value
            newRow.find('.item-product').val(item.product_id);
            
            // Load units for this product
            loadUnitsForProduct(item.product_id, newRow, item.unit_id);
            
            // Get current stock status
            $.ajax({
                url: `/api/products/${item.product_id}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const product = response.product;
                        newRow.find('.item-current-stock').text(product.stock_quantity);
                        newRow.find('.item-current-stock-input').val(product.stock_quantity);
                        
                        // Set default return quantity (full amount or available stock, whichever is less)
                        const originalQty = parseFloat(item.quantity);
                        const currentStock = parseFloat(product.stock_quantity);
                        const returnQty = Math.min(originalQty, currentStock);
                        
                        newRow.find('.item-quantity').val(returnQty);
                        
                        calculateRowTotal(newRow);
                    }
                }
            });
            
            // Increment counter
            itemCount++;
            
            return newRow;
        }
        
        // Function to load units for a product
        function loadUnitsForProduct(productId, row, selectedUnitId = null) {
            const unitSelect = row.find('.item-unit');
            
            $.ajax({
                url: `/api/products/${productId}/units`,
                type: 'GET',
                success: function(units) {
                    console.log('Units response:', units);
                    
                    unitSelect.empty().append('<option value="">اختر الوحدة</option>');
                    
                    if (units && units.length > 0) {
                        units.forEach(function(unit) {
                            // Handle different response formats
                            const unitId = unit.unit_id || unit.id;
                            const unitName = unit.name;
                            unitSelect.append(`<option value="${unitId}">${unitName}</option>`);
                        });
                        
                        // Set selected unit if provided
                        if (selectedUnitId) {
                            unitSelect.val(selectedUnitId);
                        } else {
                            // Select main unit if available
                            const mainUnit = units.find(u => u.is_main_unit) || units[0];
                            if (mainUnit) {
                                const mainUnitId = mainUnit.unit_id || mainUnit.id;
                                unitSelect.val(mainUnitId);
                            }
                        }
                    }
                },
                error: function(error) {
                    console.error('Error fetching product units:', error);
                    unitSelect.empty().append('<option value="">خطأ بالتحميل</option>');
                }
            });
        }
        
        // Function to validate return quantity
        function validateReturnQuantity(row) {
            const returnType = $('#return_type').val();
            const quantityInput = row.find('.item-quantity');
            const quantity = parseFloat(quantityInput.val()) || 0;
            const originalQuantity = parseFloat(row.find('.item-original-quantity-input').val()) || 0;
            const currentStock = parseFloat(row.find('.item-current-stock-input').val()) || 0;
            const errorElement = row.find('.item-quantity-error');
            
            // Reset validation
            quantityInput.removeClass('is-invalid');
            errorElement.text('');
            
            if (quantity <= 0) {
                quantityInput.addClass('is-invalid');
                errorElement.text('الكمية يجب أن تكون أكبر من صفر');
                return false;
            }
            
            if (returnType !== 'direct' && quantity > originalQuantity) {
                quantityInput.addClass('is-invalid');
                errorElement.text('الكمية المرتجعة تتجاوز الكمية الأصلية');
                return false;
            }
            
            if (quantity > currentStock) {
                quantityInput.addClass('is-invalid');
                errorElement.text('الكمية المرتجعة تتجاوز المخزون المتاح');
                return false;
            }
            
            return true;
        }
        
        // Calculate row total
        function calculateRowTotal(row) {
            const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
            const price = parseFloat(row.find('.item-price').val()) || 0;
            const total = quantity * price;
            
            row.find('.item-total').text(total.toFixed(2));
            
            calculateTotal();
        }
        
        // Calculate total amount
        function calculateTotal() {
            let total = 0;
            
            $('#items-table tbody tr:not(#item-row-template)').each(function() {
                const rowTotal = parseFloat($(this).find('.item-total').text()) || 0;
                total += rowTotal;
            });
            
            $('#total-amount').text(total.toFixed(2));
            $('#total_amount_input').val(total.toFixed(2));
        }
    });
</script>
@endpush 