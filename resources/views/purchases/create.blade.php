@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <form action="{{ route('purchases.store') }}" method="POST" id="purchase-form-container">
        @csrf
    <div class="row">
            <!-- Main Content Area -->
            <div class="col-12">
                <!-- Invoice Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 bg-light">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice me-2 text-primary"></i>بيانات الفاتورة الأساسية</h5>
                    </div>
                    <div class="card-body">
                                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold"><i class="fas fa-user-tie me-1 text-muted"></i>المورد <small>(اختياري)</small></label>
                                                <div class="input-group">
                                                    <select name="supplier_id" id="supplier_id" class="form-select select2">
                                        <option value="">اختر المورد</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-balance="{{ $supplier->balance }}">{{ $supplier->name }} - {{ $supplier->company_name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="supplier-info mt-2 alert alert-info py-2 small" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>الرصيد الحالي: <span id="supplierBalance" class="fw-bold">0</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="employee_id" class="form-label fw-semibold"><i class="fas fa-user me-1 text-muted"></i>الموظف <small>(اختياري)</small></label>
                                                        <select name="employee_id" id="employee_id" class="form-select select2">
                                        <option value="">اختر الموظف</option>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            <div class="col-md-3">
                                <label for="purchase_date" class="form-label fw-semibold"><i class="fas fa-calendar me-1 text-muted"></i>تاريخ الشراء</label>
                                                        <input type="date" name="purchase_date" id="purchase_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                <!-- Products Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-boxes me-1 text-primary"></i> المنتجات</h5>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-switch me-3">
                                <input class="form-check-input" type="checkbox" id="toggle-selling-prices" checked>
                                <label class="form-check-label small" for="toggle-selling-prices">عرض الأرباح</label>
                                    </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggle-expiry-dates" checked>
                                <label class="form-check-label small" for="toggle-expiry-dates">عرض الصلاحية</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                         <div class="row mb-3">
                            <div class="col-md-12">
                                <input type="text" id="product-search-input" class="form-control" placeholder="أدخل اسم المنتج أو الباركود ثم اضغط Enter...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="products-table" style="width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th width="18%">المنتج</th>
                                                <th width="12%">الوحدة</th>
                                        <th width="8%" class="text-center">الكمية</th>
                                        <th width="12%">سعر الشراء</th>
                                        <th width="10%">الإجمالي</th>
                                        <th class="selling-price-column" width="12%" style="display: none;">سعر البيع</th>
                                        <th class="expiry-date-column" width="12%" style="display: none;">تاريخ الإنتاج</th>
                                        <th class="expiry-date-column" width="12%" style="display: none;">تاريخ الصلاحية</th>
                                        <th class="selling-price-column" width="12%" style="display: none;">الربح</th>
                                                <th width="4%"></th>
                                    </tr>
                                </thead>
                                <tbody id="products-tbody">
                                    {{-- Rows will be added dynamically by JS --}}
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-empty-row-btn"><i class="fas fa-plus me-1"></i> إضافة صف فارغ</button>
                        </div>
                                    </div>
                                </div>

                <!-- Invoice Summary Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>ملخص وحفظ الفاتورة</h5>
                    </div>
                    <div class="card-body">
                        <div id="validation-errors" class="alert alert-danger p-3 small" style="display: none;">
                            <strong class="d-block mb-1"><i class="fas fa-exclamation-triangle me-1"></i>يرجى تصحيح الأخطاء:</strong>
                            <ul id="error-list" class="mb-0 ps-3"></ul>
                        </div>
                        @if ($errors->any())
                        <div class="alert alert-danger p-3 small mb-3">
                            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-8">
                                <dl class="row mb-0">
                                    <dt class="col-4 align-self-center small">إجمالي الفاتورة:</dt>
                                    <dd class="col-8 fs-4 fw-bold text-primary text-end">
                                        <input type="hidden" name="total_amount" id="total_amount">
                                        <span id="total_amount_display">0.00</span>
                                    </dd>
                                    
                                    <dt class="col-12"><hr class="my-2"></dt>
                                    
                                    <dt class="col-4 align-self-center small selling-price-column" style="display: none;">الربح المتوقع:</dt>
                                    <dd class="col-8 fs-5 fw-semibold text-success text-end selling-price-column" style="display: none;">
                                        <input type="hidden" id="total_profit">
                                        <span id="total_profit_display">0.00</span>
                                    </dd>

                                    <dt class="col-12 selling-price-column" style="display: none;"><hr class="my-2"></dt>

                                    <dt class="col-4 align-self-center small">المبلغ المدفوع:</dt>
                                    <dd class="col-8 text-end">
                                        <input type="number" name="paid_amount" id="paid_amount" class="form-control text-end" value="0" step="0.01">
                                    </dd>

                                    <dt class="col-12"><hr class="my-2"></dt>

                                    <dt class="col-4 align-self-center small">المبلغ المتبقي:</dt>
                                    <dd class="col-8 fs-5 fw-bold text-danger text-end">
                                        <input type="hidden" id="remaining_amount">
                                        <span id="remaining_amount_display">0.00</span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-semibold"><i class="fas fa-sticky-note me-1 text-muted"></i>الملاحظات</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية..."></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> حفظ الفاتورة</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    </form>
</div>

@include('purchases.partials.add_supplier_modal')

@push('scripts')
<script>
    // Simplified Template for new rows
    const productRowTemplate = `
        <tr class="product-row">
            <td>
                <select name="items[{index}][product_id]" class="form-select form-select-sm select2 product-select" required>
                    <option value="">اختر المنتج</option>
                </select>
            </td>
            <td>
                <select name="items[{index}][unit_id]" class="form-select form-select-sm unit-select" required></select>
                <div class="unit-prices small text-muted mt-1" style="display: none;"></div>
            </td>
            <td class="text-center">
                <input type="number" name="items[{index}][quantity]" class="form-control form-control-sm text-center quantity" value="1" min="1" required style="width: 80px;">
            </td>
            <td>
                <input type="number" name="items[{index}][purchase_price]" class="form-control form-control-sm purchase-price" step="0.01" min="0" required style="width: 100px;">
            </td>
            <td><strong class="row-total fw-semibold text-primary">0.00</strong></td>
            <td class="selling-price-column" style="display: none;"><input type="number" name="items[{index}][selling_price]" class="form-control form-control-sm selling-price" step="0.01" readonly></td>
            <td class="expiry-date-column" style="display: none;"><input type="date" name="items[{index}][production_date]" class="form-control form-control-sm"></td>
            <td class="expiry-date-column" style="display: none;"><input type="date" name="items[{index}][expiry_date]" class="form-control form-control-sm"></td>
            <td class="selling-price-column" style="display: none;"><span class="expected-profit fw-semibold text-success">0.00</span></td>
            <td class="text-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `;

    $(document).ready(function() {
        let rowIndex = 0;
        
        function initializeGlobalSelect2() {
            $('.select2').select2({
                width: '100%',
                theme: 'bootstrap-5',
                language: 'ar',
                dir: 'rtl'
            });
        }

        function initializeRowSelect2(row) {
             row.find('.product-select').select2({
                width: '100%',
                theme: 'bootstrap-5',
                language: 'ar',
                dir: 'rtl',
                placeholder: 'ابحث عن منتج بالاسم أو الباركود',
                ajax: {
                    url: "{{ route('api.products.select2') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
            // Unit select will be initialized in fetchProductUnits
        }

        // --- INITIALIZATION ---
        initializeGlobalSelect2();
        
        let showSellingPrices = localStorage.getItem('showSellingPrices') !== 'false';
        let showExpiryDates = localStorage.getItem('showExpiryDates') === 'true';
        
        // Store the initial state to localStorage if not set
        if (localStorage.getItem('showSellingPrices') === null) {
            localStorage.setItem('showSellingPrices', 'true');
            showSellingPrices = true;
        }
        if (localStorage.getItem('showExpiryDates') === null) {
            localStorage.setItem('showExpiryDates', 'false');
            showExpiryDates = false;
        }
        
        $('#toggle-selling-prices').prop('checked', showSellingPrices);
        $('#toggle-expiry-dates').prop('checked', showExpiryDates);
        
        console.log('Initial showSellingPrices:', showSellingPrices); // Debug log
        
        // Ensure column visibility is set correctly on page load
        setTimeout(() => {
            updateColumnVisibility();
            // Force initial visibility
            if (showSellingPrices) {
                $('.selling-price-column').show();
            } else {
                $('.selling-price-column').hide();
            }
        }, 200);
        
        addNewRow();

        // --- EVENT HANDLERS ---
        $('#toggle-selling-prices, #toggle-expiry-dates').change(function() {
            updateColumnVisibility();
            // Save state to localStorage
            localStorage.setItem('showSellingPrices', $('#toggle-selling-prices').prop('checked'));
            localStorage.setItem('showExpiryDates', $('#toggle-expiry-dates').prop('checked'));
        });
        
        $('#product-search-input').on('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                const term = $(this).val().trim();
                if (term) findAndAddProduct(term);
            }
        });

        $('#add-empty-row-btn').click(addNewRow);
        
        $('#products-tbody').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('.product-row').length === 0) addNewRow();
            calculateTotals();
        });
            
        $('#supplier_id').change(function() {
            const balance = $(this).find(':selected').data('balance');
            if (balance) {
                $('.supplier-info').show().find('#supplierBalance').text(balance);
            } else {
                $('.supplier-info').hide();
            }
        });

        // Product selection in a row
        $(document).on('change', '.product-select', function() {
            const row = $(this).closest('tr');
            const unitSelect = row.find('.unit-select');
            const productId = $(this).val();

            // --- Reset Row ---
            row.find('.quantity').val(1);
            row.find('.purchase-price, .selling-price').val('');
            row.find('.row-total, .expected-profit').text('0.00');

            // If a select2 instance exists, destroy it.
            if (unitSelect.hasClass("select2-hidden-accessible")) {
                unitSelect.select2('destroy');
            }

            // Clear the unit select and show a loading state.
            unitSelect.empty().append('<option value="">جار التحميل...</option>');

            // Recalculate totals since the row is effectively zeroed out.
            calculateTotals();
            
            if (productId) {
                // Fetch the units. The success callback of this function will handle re-initializing select2.
                fetchProductUnits(productId, unitSelect);
            } else {
                 // If no product is selected, clear it and leave it empty.
                 unitSelect.empty().append('<option value="">اختر الوحدة</option>');
            }
        });

        // Unit selection in a row
        $(document).on('change', '.unit-select', function() {
            const row = $(this).closest('tr');
            const unitId = $(this).val();
            
            // Clear purchase price before fetching new one
            row.find('.purchase-price').val('');

            if (unitId) {
                fetchUnitPrices(unitId, row);
                fetchLastPurchasePrice(unitId, row);
            }
            calculateRow(row);
        });

        // Recalculate on data change
        $(document).on('change keyup', '.quantity, .purchase-price, .selling-price, #paid_amount', function(e) {
            const row = $(this).closest('tr');
            calculateRow(row);
        });
        
        $('#purchase-form-container').submit(function(e) {
            e.preventDefault();
            if (validateForm()) {
                                Swal.fire({
                    title: 'تأكيد حفظ الفاتورة',
                    text: 'هل أنت متأكد من حفظ فاتورة الشراء؟',
                    icon: 'question',
                                    showCancelButton: true,
                    confirmButtonText: 'نعم، حفظ',
                    cancelButtonText: 'إلغاء'
                                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            }
        });

        // --- FUNCTIONS ---
        function addNewRow(productId = null) {
            const newRowHtml = productRowTemplate.replace(/{index}/g, rowIndex++);
            const newRow = $(newRowHtml);
            $('#products-tbody').append(newRow);
            
            initializeRowSelect2(newRow);
            
            if (productId) {
                newRow.find('.product-select').val(productId).trigger('change');
            } else {
                newRow.find('.unit-select').append('<option value="">اختر منتج أولاً</option>');
            }
            
            // Ensure column visibility is correct for the new row
            setTimeout(() => {
                updateColumnVisibility();
                // Force visibility for new row
                if (showSellingPrices) {
                    newRow.find('.selling-price-column').show();
                } else {
                    newRow.find('.selling-price-column').hide();
                }
            }, 100);
            
            return newRow;
        }
        
        function calculateRow(row) {
            if (!row || row.length === 0) return;
            const quantity = parseFloat(row.find('.quantity').val()) || 0;
            const purchasePrice = parseFloat(row.find('.purchase-price').val()) || 0;
            const sellingPrice = parseFloat(row.find('.selling-price').val()) || 0;

            const rowTotal = quantity * purchasePrice;
            row.find('.row-total').text(rowTotal.toFixed(2));

            if (showSellingPrices) {
                const profit = (sellingPrice - purchasePrice) * quantity;
                row.find('.expected-profit').text(profit.toFixed(2));
            }
            
            calculateTotals();
        }

        function calculateTotals() {
            let totalAmount = 0;
            let totalProfit = 0;
            
            $('.product-row').each(function() {
                const row = $(this);
                if (row.find('.product-select').val()) {
                    totalAmount += parseFloat(row.find('.row-total').text()) || 0;
                    if(showSellingPrices) {
                        totalProfit += parseFloat(row.find('.expected-profit').text()) || 0;
                    }
                }
            });

            const paidAmount = parseFloat($('#paid_amount').val()) || 0;
            $('#total_amount').val(totalAmount.toFixed(2));
            $('#total_amount_display').text(totalAmount.toFixed(2));
            $('#total_profit').val(totalProfit.toFixed(2));
            $('#total_profit_display').text(totalProfit.toFixed(2));
            const remainingAmount = totalAmount - paidAmount;
            $('#remaining_amount').val(remainingAmount.toFixed(2));
            $('#remaining_amount_display').text(remainingAmount.toFixed(2));
        }

        function updateColumnVisibility() {
            showSellingPrices = $('#toggle-selling-prices').prop('checked');
            console.log('showSellingPrices:', showSellingPrices); // Debug log
            
            // Force show/hide with display property
            if (showSellingPrices) {
                $('.selling-price-column').show();
            } else {
                $('.selling-price-column').hide();
            }
            
            if (!showSellingPrices) {
                 $('.expected-profit').text('0.00');
            }

            // Handle expiry date columns
            const showExpiryDates = $('#toggle-expiry-dates').prop('checked');
            if (showExpiryDates) {
                $('.expiry-date-column').show();
            } else {
                $('.expiry-date-column').hide();
            }
            
            calculateTotals();
        }

        function findAndAddProduct(term) {
            const searchInput = $('#product-search-input');
            searchInput.prop('disabled', true);
            $.ajax({
                url: "{{ route('api.sales.products.search') }}", // Use the robust sales search endpoint
                data: { search: term }, // Use 'search' as the parameter consistent with the sales API
                success: function(response) {
                    if (response.success && response.products && response.products.length > 0) {
                        const product = response.products[0]; // Take the first result
                        const productId = product.id;
                        let existingRow = findRowByProductId(productId);
                        if (existingRow) {
                            const quantityInput = existingRow.find('.quantity');
                            quantityInput.val(parseInt(quantityInput.val()) + 1).trigger('keyup');
                            highlightRow(existingRow);
                        } else {
                            const firstEmptyRow = findFirstEmptyRow();
                            if (firstEmptyRow) {
                                // Create a new option and append it
                                var newOption = new Option(product.name, product.id, true, true);
                                firstEmptyRow.find('.product-select').append(newOption).trigger('change');
                            } else {
                                // This case might be less common now, but kept for robustness
                                const newRow = addNewRow();
                                var newOption = new Option(product.name, product.id, true, true);
                                newRow.find('.product-select').append(newOption).trigger('change');
                            }
                        }
                        ensureEmptyRowAtEnd();
                        searchInput.val('');
                    } else {
                        Swal.fire('لم يتم العثور عليه', 'لم يتم العثور على منتج مطابق.', 'warning');
                    }
                },
                error: () => Swal.fire('خطأ', 'حدث خطأ أثناء البحث عن المنتج.', 'error'),
                complete: () => searchInput.prop('disabled', false).focus()
            });
        }

        function fetchProductUnits(productId, unitSelect) {
            $.ajax({
                url: `/api/products/${productId}/units`,
                success: function(units) {
                    console.log("Units API Response:", units); // Debug log
                    unitSelect.empty();
                    if (units && units.length > 0) {
                        unitSelect.append('<option value="">اختر الوحدة</option>');
                        units.forEach(unit => {
                            unitSelect.append(new Option(unit.name, unit.id, false, false));
                        });
                        
                        // Initialize Select2 AFTER options are populated
                        unitSelect.select2({ width: '100%', theme: 'bootstrap-5', language: 'ar', dir: 'rtl' });
                        
                        const mainUnit = units.find(u => u.is_main_unit) || units[0];
                        if (mainUnit) {
                            unitSelect.val(mainUnit.id).trigger('change');
                        }
            } else {
                        unitSelect.append('<option value="">لا توجد وحدات</option>');
                        unitSelect.select2({ width: '100%', theme: 'bootstrap-5', language: 'ar', dir: 'rtl' });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching units:", error, xhr.responseText); // Debug log
                    unitSelect.empty().append('<option value="">خطأ بالتحميل</option>');
                    unitSelect.select2({ width: '100%', theme: 'bootstrap-5', language: 'ar', dir: 'rtl' });
                }
            });
        }

        function fetchUnitPrices(unitId, row) {
             $.get(`/api/product-units/${unitId}/prices`, function(response) {
                if (response.success && response.prices) {
                    const defaultPrice = response.prices.find(p => p.is_default);
                    if (defaultPrice) {
                        row.find('.selling-price').val(defaultPrice.value).trigger('keyup');
                    }
                }
                calculateRow(row);
            });
        }
        
        function fetchLastPurchasePrice(unitId, row) {
            $.get(`/api/product-units/${unitId}/last-purchase-price`, function(response) {
                if (response.success && response.lastPurchasePrice !== null && response.lastPurchasePrice !== undefined) {
                    const purchasePriceInput = row.find('.purchase-price');
                    if (!purchasePriceInput.val() || purchasePriceInput.val() == "0") {
                        purchasePriceInput.val(parseFloat(response.lastPurchasePrice).toFixed(2));
                    }
                }
                calculateRow(row);
            });
        }

        function findRowByProductId(productId) {
            let existingRow = null;
            $('.product-row').each(function() {
                const row = $(this);
                if (row.find('.product-select').val() == productId && row.find('.unit-select').val()) { 
                    existingRow = row;
                    return false;
                }
            });
            return existingRow;
        }

        function findFirstEmptyRow() {
            let emptyRow = null;
                $('.product-row').each(function() {
                if (!$(this).find('.product-select').val()) {
                    emptyRow = $(this);
                    return false;
                }
            });
            return emptyRow;
        }

        function ensureEmptyRowAtEnd() {
            if (!$('.product-row').last().find('.product-select').val()) return;
            addNewRow();
        }
        
        function highlightRow(row) {
            row.addClass('bg-warning-soft');
            setTimeout(() => row.removeClass('bg-warning-soft'), 1500);
        }

        function validateForm() {
                hideErrors();
            let errors = [];
            let isFirstPopulatedRowFound = false;
            
            $('.product-row').each(function() {
                const row = $(this);
                const rowIndex = row.index() + 1;
                if (row.find('.product-select').val()) {
                    isFirstPopulatedRowFound = true;
                    if (!row.find('.unit-select').val()) {
                        errors.push(`الوحدة مطلوبة في الصف ${rowIndex}.`);
                        row.find('.unit-select').closest('td').find('.select2-container').addClass('is-invalid');
                    }
                    if (!row.find('.quantity').val() || parseFloat(row.find('.quantity').val()) <= 0) {
                        errors.push(`الكمية يجب أن تكون أكبر من صفر في الصف ${rowIndex}.`);
                        row.find('.quantity').addClass('is-invalid');
                    }
                    if (row.find('.purchase-price').val() === '' || parseFloat(row.find('.purchase-price').val()) < 0) {
                        errors.push(`سعر الشراء مطلوب ولا يمكن أن يكون سالباً في الصف ${rowIndex}.`);
                        row.find('.purchase-price').addClass('is-invalid');
                    }
                }
            });

            if (!isFirstPopulatedRowFound) {
                 errors.push('يجب إضافة منتج واحد على الأقل للفاتورة.');
            }
            
            const total = parseFloat($('#total_amount').val()) || 0;
            const paid = parseFloat($('#paid_amount').val()) || 0;
            if (paid > total) {
                errors.push('المبلغ المدفوع لا يمكن أن يكون أكبر من إجمالي الفاتورة.');
                $('#paid_amount').addClass('is-invalid');
            }
            if (paid < 0) {
                 errors.push('المبلغ المدفوع لا يمكن أن يكون سالباً.');
                $('#paid_amount').addClass('is-invalid');
            }
            
            if (errors.length > 0) {
                showErrors(errors);
                return false;
            }
            return true;
        }

        function showErrors(errors) {
            const errorList = $('#error-list');
            errorList.empty();
            errors.forEach(error => errorList.append(`<li>${error}</li>`));
            $('#validation-errors').show();
            $('html, body').animate({ scrollTop: $('#validation-errors').offset().top - 20 }, 200);
        }

        function hideErrors() {
            $('#validation-errors').hide();
            $('.is-invalid').removeClass('is-invalid');
        }

        // --- Supplier Modal Logic ---
        $('#save-supplier-btn').on('click', function() {
            const form = $('#add-supplier-form');
            const saveBtn = $(this);
            const errorMessages = $('#supplier-error-messages');

            saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحفظ...');
            errorMessages.hide().empty();

            $.ajax({
                url: "{{ route('api.suppliers.store') }}",
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success && response.supplier) {
                        const supplier = response.supplier;
                        // Add the new supplier to the dropdown
                        const newOption = new Option(`${supplier.name} - ${supplier.company_name || ''}`, supplier.id, true, true);
                        $('#supplier_id').append(newOption).trigger('change');
                        
                        // Close the modal
                        $('#addSupplierModal').modal('hide');
                        form[0].reset();
                        
                        Swal.fire('نجاح', 'تم إضافة المورد بنجاح.', 'success');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorHtml = '<ul>';
                        $.each(errors, function(key, value) {
                            errorHtml += `<li>${value[0]}</li>`;
                        });
                        errorHtml += '</ul>';
                        errorMessages.html(errorHtml).show();
                    } else {
                        Swal.fire('خطأ', 'حدث خطأ غير متوقع.', 'error');
                    }
                },
                complete: function() {
                    saveBtn.prop('disabled', false).text('حفظ المورد');
                }
            });
        });
    });
</script>
@endpush 