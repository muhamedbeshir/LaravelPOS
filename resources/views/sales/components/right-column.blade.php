<!-- RIGHT COLUMN: Invoice details and payment -->
<div class="col-lg-4 col-md-5 ps-lg-1">
    <div class="compact-controls">
        <!-- Compact controls section with tabs -->
        <div class="d-flex">
            <ul class="nav nav-tabs nav-fill bg-light rounded-top flex-grow-1" id="invoiceControlTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active tab-button" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-content" type="button">
                        <i class="fas fa-cog text-primary me-1"></i> <span class="tab-text">إعدادات</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-button" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-content" type="button">
                        <i class="fas fa-calculator text-success me-1"></i> <span class="tab-text">ملخص</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-button" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-content" type="button">
                        <i class="fas fa-money-bill text-warning me-1"></i> <span class="tab-text">الدفع</span>
                    </button>
                </li>
            </ul>
            
            <!-- مربع رقم الفاتورة -->
            <div class="ms-2" style="max-height: 38px;">
                <div class="invoice-number-container bg-primary text-white rounded-top px-2 py-1 h-100 d-flex align-items-center shadow-sm" style="min-width: 120px;">
                    <div class="row w-100 mx-0">
                        <div class="col-12 px-1 text-center">
                            <div class="small mb-0 d-block opacity-75" style="font-size: 9px; letter-spacing: 0.5px;">رقم</div>
                            <div class="fw-bold" style="line-height: 1; font-size: 1.1rem;">
                                <span id="current-invoice-number">1</span>
                                <span class="text-white-50 mx-1">/</span>
                                <span class="invoice-number-count text-white-50" style="font-size: 0.9rem;">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- مربع الرقم المرجعي (مخفي) -->
            <div class="ms-2 d-none" style="max-height: 38px;">
                <div class="invoice-reference-container bg-info text-white rounded-top px-2 py-1 h-100 d-flex align-items-center shadow-sm" style="min-width: 120px;">
                    <div class="row w-100 mx-0">
                        <div class="col-12 px-1 text-center">
                            <div class="small mb-0 d-block opacity-75" style="font-size: 9px; letter-spacing: 0.5px;">الرقم المرجعي</div>
                            <div class="fw-bold" id="reference-invoice-number" style="line-height: 1; font-size: 0.95rem;">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- نقل أزرار الدليفري هنا لتكون ظاهرة دائماً عند تفعيل الدليفري -->
        <div class="mb-2" id="delivery-buttons-bar">
            <!-- تم إزالة زر حالة الدليفري من هنا -->
            <!-- تم إزالة زر وقت التوصيل -->
        </div>

        <div class="tab-content p-2 bg-white border border-top-0 rounded-bottom mb-2 shadow-sm">
            <!-- Settings Tab -->
            <div class="tab-pane fade show active" id="settings-content">
                <!-- Payment Preview -->
                <div class="row g-1 mb-2">
                    <div class="col-6">
                        <div class="payment-preview-card-compact paid-card">
                            <div class="preview-header-compact">
                                <i class="fas fa-money-bill-wave me-1"></i>
                                <span>المدفوع</span>
                            </div>
                            <input type="number" id="preview-paid" class="preview-input-compact" value="0.00" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="payment-preview-card-compact remaining-card">
                            <div class="preview-header-compact">
                                <i class="fas fa-hand-holding-usd me-1"></i>
                                <span>المتبقي</span>
                            </div>
                            <div id="preview-remaining" class="preview-amount-compact">0.00</div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-1">
                    <div class="col-6">
                        <label class="control-label-compact"><i class="fas fa-file-invoice me-1"></i>نوع الفاتورة</label>
                        <select class="form-select form-select-sm control-input-compact" id="invoice-type" onchange="handleInvoiceTypeChange()">
                            <option value="cash">كاش</option>
                            <option value="credit">آجل</option>
                            <option value="visa">فيزا</option>
                            <option value="transfer">تحويلات مالية</option>
                            <option value="mixed">دفع بطرق متعددة</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="control-label-compact"><i class="fas fa-truck me-1"></i>نوع الطلب</label>
                        <select class="form-select form-select-sm control-input-compact" id="order-type" onchange="handleOrderTypeChange()">
                            <option value="takeaway">تيك أواي</option>
                            <option value="delivery">دليفري</option>
                        </select>
                    </div>
                    
                    @if ($settings->get('allow_selling_at_different_prices', true))
                    <div class="col-6 price-type-selector-container">
                        <label class="control-label-compact"><i class="fas fa-tag me-1"></i>نوع السعر</label>
                        <select class="form-select form-select-sm control-input-compact" id="price-type">
                            @foreach($priceTypes as $priceType)
                            <option value="{{ $priceType->code }}" 
                                    data-is-default="{{ $priceType->is_default ? '1' : '0' }}"
                                    {{ ($settings->get('default_price_type', 'retail') == $priceType->code || ($settings->get('default_price_type') == '' && $priceType->is_default)) ? 'selected' : '' }}>
                                {{ $priceType->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div class="col-6">
                        <label class="control-label-compact"><i class="fas fa-user me-1"></i>العميل</label>
                        <div class="d-flex">
                            <select class="form-select form-select-sm control-input-compact" id="customer-id">
                                <option value="1" selected 
                                        data-credit="0.00" 
                                        data-credit-limit="0.00" 
                                        data-is-unlimited-credit="0"
                                        data-address=""
                                        data-default-price-type-code="">
                                    عميل نقدي
                                </option>
                                @foreach($customers as $customer)
                                    @if($customer->id != 1)
                                    <option value="{{ $customer->id }}" 
                                            data-credit="{{ $customer->credit_balance ?? '0.00' }}" 
                                            data-credit-limit="{{ $customer->credit_limit ?? '0.00' }}" 
                                            data-is-unlimited-credit="{{ $customer->is_unlimited_credit ? '1' : '0' }}"
                                            data-address="{{ $customer->address }}"
                                            data-default-price-type-code="{{ $customer->defaultPriceType ? $customer->defaultPriceType->code : '' }}">
                                        {{ $customer->name }} 
                                        @if($customer->defaultPriceType)
                                            ({{ $customer->defaultPriceType->name }})
                                        @endif
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="btn-group ms-1">
                                <button class="btn btn-modern-compact btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#select-customer-modal" title="بحث عن عميل">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button class="btn btn-modern-compact btn-success" type="button" data-bs-toggle="modal" data-bs-target="#add-customer-modal" title="إضافة عميل جديد">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <label class="control-label-compact"><i class="fas fa-percentage me-1"></i>الخصم</label>
                        <div class="d-flex discount-compact">
                            <input type="number" class="form-control form-control-sm control-input-compact discount-amount-compact" id="discount" value="0" min="0">
                            <select class="form-select form-select-sm control-input-compact discount-type-compact" id="discount-type">
                                <option value="percentage">%</option>
                                <option value="fixed">جنيه</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-6 delivery-section d-none">
                        <label class="control-label-compact"><i class="fas fa-motorcycle me-1"></i>موظف التوصيل</label>
                        <select class="form-select form-select-sm control-input-compact" id="delivery-employee">
                            <option value="">اختر موظف التوصيل</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Status Messages & Credit Info -->
                    <div class="col-12">
                        <div class="alert-message-compact d-none" id="customer-required-msg">
                            <i class="fas fa-exclamation-circle me-1"></i>يجب اختيار عميل للفواتير الآجلة
                        </div>
                        <div class="credit-info-compact d-none" id="customer-credit-info">
                            <div class="credit-header-compact">
                                <i class="fas fa-credit-card me-1"></i>
                                <span>معلومات الائتمان</span>
                            </div>
                            <div class="credit-row-compact">
                                <span><i class="fas fa-wallet me-1"></i>الرصيد:</span>
                                <span id="customer-current-credit" class="credit-value-compact">0</span>
                            </div>
                            <div class="credit-row-compact">
                                <span><i class="fas fa-credit-card me-1"></i>حد الائتمان:</span>
                                <span id="customer-credit-limit" class="credit-value-compact">0</span>
                            </div>
                            <div class="credit-row-compact highlight">
                                <span><i class="fas fa-coins me-1"></i>المتاح:</span>
                                <span id="customer-available-credit" class="credit-value-compact available">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- زر فتح حوار الدفعات المتعددة -->
                <div class="col-12 d-flex justify-content-end align-items-end d-none" id="mixed-payments-icon-container">
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center" id="open-mixed-payments-modal" data-bs-toggle="modal" data-bs-target="#mixed-payments-modal" title="تفاصيل الدفعات">
                        <i class="fas fa-layer-group me-1"></i>
                        <span>الدفعات</span>
                        <span id="mixed-payments-count" class="badge bg-primary ms-1">1</span>
                    </button>
                </div>
            </div>

            <!-- Summary Tab -->
            <div class="tab-pane fade" id="summary-content">
                <div class="row g-1">
                    <div class="col-6">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="summary-content">
                                <label class="summary-label">الإجمالي</label>
                                <div id="subtotal" class="summary-value">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-shopping-basket"></i>
                            </div>
                            <div class="summary-content">
                                <label class="summary-label">المنتجات</label>
                                <div id="items-count" class="summary-value">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 profit-summary" style="{{ !$settings->get('show_profit_in_summary') ? 'display:none;' : '' }}">
                        <div class="summary-card profit-card" id="profit-summary-box" onclick="showProfitDetails()">
                            <div class="summary-icon profit-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="summary-content">
                                <label class="summary-label">الربح</label>
                                <div id="total-profit" class="summary-value profit-value">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 profit-summary" style="{{ !$settings->get('show_profit_in_summary') ? 'display:none;' : '' }}">
                        <div class="summary-card profit-card" id="profit-percentage-box" onclick="showProfitDetails()">
                            <div class="summary-icon profit-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="summary-content">
                                <label class="summary-label">نسبة الربح</label>
                                <div id="profit-percentage" class="summary-value profit-value">0%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-1">
                        <div class="total-amount-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="total-label"><i class="fas fa-receipt me-1"></i>المطلوب دفعه</label>
                                <div class="total-value" id="total">0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Tab -->
            <div class="tab-pane fade" id="payment-content">
                <div class="mb-2">
                    <label class="payment-label"><i class="fas fa-money-bill-wave me-1"></i>المدفوع</label>
                    <input type="number" class="form-control form-control-sm paid-amount-input payment-input" id="paid-amount" min="0">
                </div>
                <div class="remaining-card mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="remaining-label"><i class="fas fa-hand-holding-usd me-1"></i>الباقي</label>
                        <div class="remaining-value" id="remaining">0.00</div>
                    </div>
                </div>
                <div class="d-grid gap-1">
                    <button class="btn btn-primary btn-sm payment-action-btn" id="save-invoice">
                        <i class="fas fa-save me-1"></i>
                        حفظ الفاتورة
                    </button>
                    <button class="btn btn-success btn-sm payment-action-btn" id="save-print-invoice">
                        <i class="fas fa-print me-1"></i>
                        حفظ وطباعة
                    </button>
                    <button class="btn btn-warning btn-sm payment-action-btn" id="suspend-invoice">
                        <i class="fas fa-pause me-1"></i>
                        تعليق الفاتورة
                    </button>
                    <button class="btn btn-info btn-sm payment-action-btn" id="delivery-status-btn">
                        <i class="fas fa-truck me-1"></i>
                        حالة الدليفري
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @include('sales.components.quick-totals')
</div>

<!-- Modal: تفاصيل الدفعات المتعددة -->
<div class="modal fade" id="mixed-payments-modal" tabindex="-1" aria-labelledby="mixedPaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mixedPaymentsModalLabel"><i class="fas fa-layer-group me-2"></i>تفاصيل الدفعات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-2">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width:40%">الطريقة</th>
                                <th style="width:40%">المبلغ</th>
                                <th style="width:20%"></th>
                            </tr>
                        </thead>
                        <tbody id="mixed-payments-body"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" id="add-payment-row-btn">
                    <i class="fas fa-plus me-1"></i> إضافة دفعة
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: فواتير الوردية الحالية -->
<div class="modal fade" id="shift-invoices-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title">فواتير الوردية الحالية (#<span id="shift-number"></span>)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="shift-invoices-loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">جاري التحميل...</p>
                </div>
                <div class="table-responsive d-none" id="shift-invoices-table-wrap">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light"><tr><th>#</th><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th></th></tr></thead>
                        <tbody id="shift-invoices-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // تأكد من إنشاء صف واحد على الأقل عند فتح المودال
    document.getElementById('mixed-payments-modal').addEventListener('shown.bs.modal', function () {
        if (typeof ensureMixedRows === 'function') {
            ensureMixedRows();
        }
    });

    // فتح المودال عند النقر على رقم الفاتورة
    $('.invoice-number-container').on('click', function(){
        $('#shift-invoices-modal').modal('show');
        loadShiftInvoices();
    });

    function loadShiftInvoices(){
        $('#shift-invoices-loading').removeClass('d-none');
        $('#shift-invoices-table-wrap').addClass('d-none');
        $('#shift-invoices-body').empty();
        $.get('{{ route('sales.current-shift-invoices') }}')
            .done(res => {
                if(!res.success){ alert(res.message); return; }
                $('#shift-number').text(res.shift_number);
                res.invoices.forEach((inv, idx)=>{
                    const customer = inv.customer ? inv.customer.name : '—';
                    $('#shift-invoices-body').append(`<tr>
                        <td>${idx+1}</td>
                        <td>${inv.invoice_number}</td>
                        <td>${customer}</td>
                        <td>${Number(inv.total).toFixed(2)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm btn-primary view-invoice" data-id="${inv.id}"><i class="fas fa-eye"></i></button>
                                <a href="{{ url('sales/invoices') }}/${inv.id}/print" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-print"></i></a>
                            </div>
                        </td>
                    </tr>`);
                });
                $('#shift-invoices-loading').addClass('d-none');
                $('#shift-invoices-table-wrap').removeClass('d-none');
            })
            .fail(()=>alert('خطأ في تحميل فواتير الوردية'));
    }
</script>

<script>
    /* --------------------------------------------------
       View Invoice Details (delegated)
       -------------------------------------------------- */
    $(document).on('click', '.view-invoice', function(e){
        e.preventDefault();
        e.stopPropagation();
        const invoiceId = $(this).data('id');

        // reset modal
        $('#invoice-loading').show();
        $('#invoice-content').hide();
        $('#modal-invoice-items').empty();
        $('#modal-invoice-payments').empty();

        // show modal
        $('#invoiceDetailsModal').modal('show');
        $('#print-invoice-btn').attr('href', `{{ url('sales/invoices') }}/${invoiceId}/print`);

        $.get(`{{ url('api/sales/invoices') }}/${invoiceId}`)
            .done(function(response){
                if(!response.success){ alert('حدث خطأ أثناء تحميل بيانات الفاتورة'); return; }

                const inv = response.invoice;

                // basic info
                $('#invoice-number, #modal-invoice-number').text(inv.invoice_number);
                $('#modal-invoice-date').text(new Date(inv.created_at).toLocaleString('ar-EG'));

                const typeMap={cash:'كاش',credit:'آجل',visa:'فيزا',transfer:'تحويل',mixed:'متعدد'};
                const invType = inv.type || inv.invoice_type || 'cash';
                $('#modal-invoice-type').text(typeMap[invType] ?? invType);
                $('#modal-invoice-order-type').text(inv.order_type==='takeaway'?'تيك أواي':'دليفري');

                const statusBadge = inv.status==='completed'?'<span class="badge bg-success">مكتملة</span>':
                                    inv.status==='pending'?'<span class="badge bg-warning">معلقة</span>':
                                    '<span class="badge bg-danger">ملغية</span>';
                $('#modal-invoice-status').html(statusBadge);

                if(inv.customer){
                    $('#modal-customer-name').text(inv.customer.name);
                    $('#modal-customer-phone').text(inv.customer.phone||'غير متوفر');
                    $('#modal-customer-address').text(inv.customer.address||'غير متوفر');
                }

                // items
                if(Array.isArray(inv.items) && inv.items.length){
                    inv.items.forEach(function(it,idx){
                        const discountTxt = it.discount_percentage>0 ? `${it.discount_percentage}%` : Number(it.discount_value||0).toFixed(2);
                        $('#modal-invoice-items').append(`<tr>
                            <td>${idx+1}</td>
                            <td>${it.product?it.product.name:'غير متوفر'}</td>
                            <td>${it.product_unit&&it.product_unit.unit?it.product_unit.unit.name:'غير متوفر'}</td>
                            <td>${Number(it.quantity||0)}</td>
                            <td>${Number(it.unit_price||0).toFixed(2)}</td>
                            <td>${discountTxt}</td>
                            <td>${Number(it.total||0).toFixed(2)}</td>
                            <td>${Number(it.profit||0).toFixed(2)}</td>
                        </tr>`);
                    });
                } else {
                    $('#modal-invoice-items').html('<tr><td colspan="8" class="text-center">لا توجد منتجات</td></tr>');
                }

                // payments
                if(Array.isArray(inv.payments) && inv.payments.length){
                    inv.payments.forEach(function(p){
                        const lbl = typeMap[p.method] ?? p.method;
                        $('#modal-invoice-payments').append(`<tr><td>${lbl}</td><td>${Number(p.amount).toFixed(2)}</td><td>${p.reference??'-'}</td></tr>`);
                    });
                } else {
                    const paid=parseFloat(inv.paid_amount||0);
                    const lbl=typeMap[invType]??invType;
                    $('#modal-invoice-payments').html(paid>0?`<tr><td>${lbl}</td><td>${paid.toFixed(2)}</td><td>-</td></tr>`:'<tr><td colspan="3" class="text-center">لا توجد دفعات</td></tr>');
                }

                // summary
                $('#modal-subtotal').text(Number(inv.subtotal||0).toFixed(2));
                const discPct=Number(inv.discount_percentage||0),discVal=Number(inv.discount_value||0);
                $('#modal-discount').text(discPct>0?`${discPct}% (${discVal.toFixed(2)})`:discVal.toFixed(2));
                $('#modal-total').text(Number(inv.total||0).toFixed(2));
                $('#modal-paid').text(Number(inv.paid_amount||0).toFixed(2));
                const rem=inv.remaining_amount!==undefined?inv.remaining_amount:inv.remaining;
                $('#modal-remaining').text(Number(rem||0).toFixed(2));
                $('#modal-profit').text(Number(inv.profit||0).toFixed(2));

                // show
                $('#invoice-loading').hide();
                $('#invoice-content').show();
            })
            .fail(()=>alert('خطأ في الاتصال بالخادم'));
    });
</script>
@endpush

<style>
/* Tab Styling */
.tab-button {
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.4rem 0.6rem;
    border: none;
    background: transparent;
    transition: all 0.2s ease;
    color: #374151 !important;
}

.tab-button.active {
    background: white !important;
    border-bottom: 2px solid var(--bs-primary) !important;
    color: #1f2937 !important;
}

.tab-button:hover {
    color: #1f2937 !important;
}

.tab-text {
    font-size: 0.8rem;
    font-weight: 600;
    color: inherit;
}

/* Control Labels */
.control-label {
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #374151;
    display: block;
}

/* Control Inputs */
.control-input {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

.control-input:focus {
    background-color: white;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

/* Action Buttons */
.action-button {
    border: 1px solid #e2e8f0;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    transition: all 0.2s ease;
}

.action-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Discount Input Group */
.discount-input-group {
    border-radius: 0.375rem;
    overflow: hidden;
}

.discount-amount {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-left: none !important;
    flex: 1;
}

.discount-type {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
    max-width: 70px;
}

/* Compact Payment Preview */
.payment-preview-card-compact {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 0.5rem;
    height: 100%;
    transition: all 0.2s ease;
}

.payment-preview-card-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}

.preview-header-compact {
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
}

.preview-input-compact {
    width: 100%;
    border: none;
    background: white;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
    color: #059669;
    border-radius: 0.25rem;
    padding: 0.125rem;
}

.preview-input-compact:focus {
    outline: none;
    box-shadow: 0 0 0 1px var(--bs-primary);
}

.preview-amount-compact {
    font-size: 0.85rem;
    font-weight: 700;
    color: #dc2626;
    text-align: center;
    background: white;
    border-radius: 0.25rem;
    padding: 0.25rem;
}

.paid-card {
    border-left: 2px solid #059669;
}

.remaining-card {
    border-left: 2px solid #dc2626;
}

/* Compact Form Controls */
.control-label-compact {
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #374151;
    display: block;
}

.control-input-compact {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

.control-input-compact:focus {
    background-color: white;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

/* Compact Buttons */
.btn-modern-compact {
    border: 1px solid #e2e8f0;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    transition: all 0.2s ease;
    border-radius: 0.25rem;
}

.btn-modern-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Compact Discount */
.discount-compact {
    border-radius: 0.375rem;
    overflow: hidden;
}

.discount-amount-compact {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-left: none !important;
    flex: 1;
}

.discount-type-compact {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: none !important;
    max-width: 70px;
}

/* Compact Alert */
.alert-message-compact {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 0.75rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
}

/* Compact Credit Info */
.credit-info-compact {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #7dd3fc;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
    overflow: hidden;
}

.credit-header-compact {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.credit-row-compact {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-bottom: 1px solid #bae6fd;
}

.credit-row-compact:last-child {
    border-bottom: none;
}

.credit-row-compact.highlight {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    font-weight: 600;
}

.credit-value-compact {
    font-weight: 600;
    color: #0369a1;
}

.credit-value-compact.available {
    color: #059669;
}

/* Summary Cards */
.summary-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    height: 100%;
}

.summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--bs-primary), var(--bs-info));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.summary-content {
    flex: 1;
    min-width: 0;
}

.summary-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.125rem;
    display: block;
}

.summary-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

/* Profit Cards */
.profit-card {
    cursor: pointer;
}

.profit-icon {
    background: linear-gradient(135deg, var(--bs-success), #10b981);
}

.profit-value {
    color: var(--bs-success);
}

/* Total Amount Card */
.total-amount-card {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 1px solid #93c5fd;
    border-radius: 0.5rem;
    padding: 0.75rem;
}

.total-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-primary);
    margin-bottom: 0;
}

.total-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--bs-primary);
    line-height: 1;
}

/* Payment Tab Styling */
.payment-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #f59e0b;
    margin-bottom: 0.25rem;
    display: block;
}

.payment-input {
    font-size: 0.9rem;
    font-weight: 600;
    background-color: #fefce8;
    border: 1px solid #fbbf24;
    text-align: center;
}

.payment-input:focus {
    background-color: white;
    border-color: #f59e0b;
    box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.15);
}

.remaining-card {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fca5a5;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

.remaining-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 0;
}

.remaining-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #dc2626;
    line-height: 1;
}

/* Payment Action Buttons */
.payment-action-btn {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    transition: all 0.2s ease;
}

.payment-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* Alert Messages */
.alert-message {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 0.75rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
}

/* Credit Info Card */
.credit-info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #7dd3fc;
    border-radius: 0.5rem;
    padding: 0.5rem;
    margin-top: 0.5rem;
}

.credit-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.credit-row:last-child {
    margin-bottom: 0;
}

.credit-value {
    font-weight: 600;
    color: #0369a1;
}

/* Enhanced Animations */
.compact-controls {
    transition: all 0.3s ease;
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .tab-text {
        display: none;
    }
    
    .summary-card {
        padding: 0.5rem;
    }
    
    .summary-icon {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .total-value {
        font-size: 1.1rem;
    }
}
</style>

<script>
    // Function to auto-fill paid amount when empty
    function autoFillPaidAmount() {
        const paidInput = document.getElementById('paid-amount');
        const previewPaidInput = document.getElementById('preview-paid');
        const totalEl = document.getElementById('total');
        if (totalEl) {
            const totalVal = parseFloat(totalEl.textContent) || 0;
            const roundedTotal = Math.round(totalVal * 100) / 100;
            
            if (paidInput && (isNaN(parseFloat(paidInput.value)) || parseFloat(paidInput.value) === 0)) {
                paidInput.value = roundedTotal.toFixed(2);
            }
            if (previewPaidInput && (isNaN(parseFloat(previewPaidInput.value)) || parseFloat(previewPaidInput.value) === 0)) {
                previewPaidInput.value = roundedTotal.toFixed(2);
            }
            updateRemainingAmounts();
        }
    }

    // Function to update remaining amounts
    function updateRemainingAmounts() {
        const totalEl = document.getElementById('total');
        const paidInput = document.getElementById('paid-amount');
        const previewPaidInput = document.getElementById('preview-paid');
        const remainingEl = document.getElementById('remaining');
        const previewRemainingEl = document.getElementById('preview-remaining');
        
        if (totalEl) {
            const totalVal = parseFloat(totalEl.textContent) || 0;
            const paidVal = paidInput ? parseFloat(paidInput.value) || 0 : 0;
            const previewPaidVal = previewPaidInput ? parseFloat(previewPaidInput.value) || 0 : 0;
            
            const remaining = totalVal - paidVal;
            const previewRemaining = totalVal - previewPaidVal;
            
            if (remainingEl) remainingEl.textContent = remaining.toFixed(2);
            if (previewRemainingEl) previewRemainingEl.textContent = previewRemaining.toFixed(2);
        }
    }

    // Set up event listeners
    window.addEventListener('DOMContentLoaded', function() {
        const paymentTab = document.querySelector('button[data-bs-target="#payment-content"]');
        if (paymentTab) {
            paymentTab.addEventListener('shown.bs.tab', autoFillPaidAmount);
        }
        
        const saveBtn = document.getElementById('save-invoice');
        const savePrintBtn = document.getElementById('save-print-invoice');
        if (saveBtn) saveBtn.addEventListener('click', autoFillPaidAmount, true);
        if (savePrintBtn) savePrintBtn.addEventListener('click', autoFillPaidAmount, true);
        
        const paidInput = document.getElementById('paid-amount');
        const previewPaidInput = document.getElementById('preview-paid');
        if (paidInput) paidInput.addEventListener('input', updateRemainingAmounts);
        if (previewPaidInput) previewPaidInput.addEventListener('input', updateRemainingAmounts);
    });

    // Call auto-fill on initial load
    window.addEventListener('load', function() {
        autoFillPaidAmount();
        
        const customerSelect = document.getElementById('customer-id');
        if (customerSelect) {
            customerSelect.addEventListener('change', function() {
                handleCustomerChange(this);
            });
        }
    });

    // Customer change function
    function handleCustomerChange(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const priceTypeSelect = document.getElementById('price-type');
        
        if (!priceTypeSelect) return;
        
        const customerDefaultPriceTypeCode = selectedOption.getAttribute('data-default-price-type-code');
        
        if (customerDefaultPriceTypeCode && customerDefaultPriceTypeCode !== '') {
            for (let i = 0; i < priceTypeSelect.options.length; i++) {
                const option = priceTypeSelect.options[i];
                if (option.value === customerDefaultPriceTypeCode) {
                    priceTypeSelect.value = customerDefaultPriceTypeCode;
                    priceTypeSelect.dispatchEvent(new Event('change'));
                    break;
                }
            }
        }
    }
    window.handleCustomerChange = handleCustomerChange;
</script>

<!-- Invoice Details Modal (copied structure) -->
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة <span id="invoice-number"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="invoice-loading">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>
                    <p class="mt-2">جاري تحميل بيانات الفاتورة...</p>
                </div>
                <div id="invoice-content" style="display:none;">
                    <!-- same internal layout as reports/all-invoices -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">معلومات الفاتورة</h6>
                            <p><strong>رقم الفاتورة:</strong> <span id="modal-invoice-number"></span></p>
                            <p><strong>التاريخ:</strong> <span id="modal-invoice-date"></span></p>
                            <p><strong>نوع الفاتورة:</strong> <span id="modal-invoice-type"></span></p>
                            <p><strong>نوع الطلب:</strong> <span id="modal-invoice-order-type"></span></p>
                            <p><strong>الحالة:</strong> <span id="modal-invoice-status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">معلومات العميل</h6>
                            <p><strong>الاسم:</strong> <span id="modal-customer-name"></span></p>
                            <p><strong>الهاتف:</strong> <span id="modal-customer-phone"></span></p>
                            <p><strong>العنوان:</strong> <span id="modal-customer-address"></span></p>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-3">منتجات الفاتورة</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered"><thead class="table-dark"><tr><th>#</th><th>المنتج</th><th>الوحدة</th><th>الكمية</th><th>السعر</th><th>الخصم</th><th>الإجمالي</th><th>الربح</th></tr></thead><tbody id="modal-invoice-items"></tbody></table>
                    </div>
                    <h6 class="fw-bold mb-3">دفعات الفاتورة</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered"><thead class="table-light"><tr><th>طريقة الدفع</th><th>المبلغ</th><th>المرجع</th></tr></thead><tbody id="modal-invoice-payments"></tbody></table>
                    </div>
                    <div class="row mt-4"><div class="col-md-6 offset-md-6"><table class="table table-bordered"><tbody><tr><th>المجموع قبل الخصم</th><td id="modal-subtotal"></td></tr><tr><th>الخصم</th><td id="modal-discount"></td></tr><tr class="table-primary"><th>الإجمالي</th><td id="modal-total"></td></tr><tr><th>المدفوع</th><td id="modal-paid"></td></tr><tr><th>المتبقي</th><td id="modal-remaining"></td></tr><tr class="table-success"><th>إجمالي الربح</th><td id="modal-profit"></td></tr></tbody></table></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <a href="#" class="btn btn-primary" id="print-invoice-btn" target="_blank"><i class="fas fa-print me-1"></i> طباعة الفاتورة</a>
            </div>
        </div>
    </div>
</div>