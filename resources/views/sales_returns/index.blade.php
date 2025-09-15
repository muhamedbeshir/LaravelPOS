@extends('layouts.app')

@section('title', 'مرتجع المبيعات')

@php
// Import necessary models
use App\Models\Customer;
use App\Models\Setting;
use App\Models\SalesReturn; // Assuming you have a SalesReturn model
use Illuminate\Support\Facades\Auth;

// Fetch any necessary settings or data for sales returns
// Example:
// $defaultReturnReason = Setting::get('default_return_reason', '');
// $recentReturns = SalesReturn::orderBy('created_at', 'desc')->take(5)->get();

// Get customers (might be useful for associating returns)
$customers = Customer::active()
    ->select(['id', 'name', 'phone'])
    ->orderBy('name')
    ->get();

// Get current user ID or default to 1 if not authenticated
$currentUserId = Auth::check() ? Auth::id() : 1;
@endphp

@section('content')
<div class="container-fluid sales-return-container pb-1">
    <!-- Hidden input for user ID -->
    <input type="hidden" id="user-id" value="{{ $currentUserId }}">
    
    <div class="row g-2">
        <!-- LEFT COLUMN: Return items search and selection -->
        <div class="col-lg-8 col-md-7 pe-lg-1">
            <!-- Search bar and return type selector -->
            <div class="card shadow-sm sales-card mb-2">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <div class="input-group search-group">
                                <span class="input-group-text bg-primary text-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="return-search-input"
                                       placeholder="ابحث برقم الفاتورة الأصلية أو اسم المنتج..." autofocus>
                                <button class="btn btn-primary" type="button" id="return-search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="return-search-suggestions" class="search-suggestions d-none">
                                <div class="suggestions-container">
                                    <!-- Search suggestions will appear here -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 d-flex justify-content-end">
                             <select class="form-select form-select-sm" id="return-mode-selector" style="max-width: 200px;">
                                <option value="invoice">إرجاع من فاتورة</option>
                           <!--      <option value="item">إرجاع منتج مباشر</option> -->
                            </select>
                        
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returned items table -->
            <div class="card shadow-sm sales-card">
                <div class="card-header bg-danger text-white py-1">
                    <h6 class="card-title mb-0"><i class="fas fa-undo-alt me-1"></i>قائمة الأصناف المرتجعة</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="height: calc(100vh - 230px);">
                        <table class="table table-hover table-sm table-striped mb-0" id="returned-items-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>المنتج/الوحدة</th>
                                    <th width="100">السعر الأصلي</th>
                                    <th width="100">الكمية المرتجعة</th>
                                    <th width="100">سعر الإرجاع</th>
                                    <th width="100">الإجمالي</th>
                                    <th width="100">السبب</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Returned items will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Return details and processing -->
        <div class="col-lg-4 col-md-5 ps-lg-1">
            <div class="compact-controls">
                <!-- Compact controls section with tabs -->
                <ul class="nav nav-tabs nav-fill bg-light rounded-top" id="returnControlTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active bg-white-hover" id="return-details-tab" data-bs-toggle="tab" data-bs-target="#return-details-content" type="button">
                            <i class="fas fa-file-invoice-dollar text-primary"></i> <span class="small text-dark">تفاصيل الإرجاع</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link bg-white-hover" id="return-summary-tab" data-bs-toggle="tab" data-bs-target="#return-summary-content" type="button">
                            <i class="fas fa-calculator text-success"></i> <span class="small text-dark">ملخص</span>
                        </button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link bg-white-hover" id="return-action-tab" data-bs-toggle="tab" data-bs-target="#return-action-content" type="button">
                            <i class="fas fa-cogs text-warning"></i> <span class="small text-dark">إجراء</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-2 bg-white border border-top-0 rounded-bottom mb-2 small">
                    <!-- Return Details Tab -->
                    <div class="tab-pane fade show active" id="return-details-content">
                        <div class="row g-2">
                            <div class="col-12 mb-2">
                                <label class="form-label small mb-0 fw-bold text-primary"><i class="fas fa-user"></i> العميل (اختياري)</label>
                                <select class="form-select form-select-sm bg-light select2" id="return-customer-id">
                                    <option value="">اختر العميل...</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone ?? 'N/A' }})</option>
                                    @endforeach
                                </select>
                            </div>
                             <div class="col-12 mb-2">
                                <label class="form-label small mb-0 fw-bold text-primary"><i class="fas fa-sticky-note"></i> سبب الإرجاع الرئيسي</label>
                                <select class="form-select form-select-sm bg-light" id="main-return-reason">
                                    <option value="">اختر سببًا...</option>
                                    <option value="defective">منتج تالف/معيب</option>
                                    <option value="wrong_item">منتج خاطئ</option>
                                    <option value="customer_dissatisfaction">عدم رضا العميل</option>
                                    <option value="other">سبب آخر</option>
                                </select>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label small mb-0 fw-bold text-primary"><i class="fas fa-comments"></i> ملاحظات إضافية</label>
                                <textarea class="form-control form-control-sm bg-light" id="return-notes" rows="3" placeholder="أية تفاصيل إضافية..."></textarea>
                            </div>
                             <div class="col-12 mb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="restock-items-switch" checked>
                                    <label class="form-check-label small" for="restock-items-switch">إعادة المنتجات إلى المخزون</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Tab -->
                    <div class="tab-pane fade" id="return-summary-content">
                        <div class="summary-box bg-light mb-2">
                            <label class="form-label small mb-0 d-block text-primary"><i class="fas fa-dollar-sign"></i> إجمالي قيمة المرتجعات</label>
                            <h5 id="return-subtotal" class="mb-0 text-dark">0.00</h5>
                        </div>
                        <div class="summary-box bg-light mb-2">
                            <label class="form-label small mb-0 d-block text-primary"><i class="fas fa-boxes"></i> عدد الأصناف المرتجعة</label>
                            <h5 id="return-items-count" class="mb-0 text-dark">0</h5>
                        </div>
                        <div class="total-amount-box">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label fw-bold text-danger"><i class="fas fa-money-bill-wave"></i> المبلغ المسترد للعميل</label>
                                <h4 class="text-danger mb-0" id="return-total-refund">0.00</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Action Tab -->
                    <div class="tab-pane fade" id="return-action-content">
                         <div class="mb-2">
                            <label class="form-label small mb-0 fw-bold text-primary"><i class="fas fa-hand-holding-usd"></i> طريقة استرداد المبلغ</label>
                            <select class="form-select form-select-sm bg-light" id="refund-method">
                                <option value="cash">نقداً</option>
                                <option value="store_credit">رصيد للمتجر</option>
                                <!-- Add other payment methods if applicable -->
                            </select>
                        </div>
                        <div class="d-grid gap-1 mt-3">
                            <button class="btn btn-danger btn-sm action-btn" id="process-return-btn">
                                <i class="fas fa-check-circle me-1"></i> تأكيد و معالجة الإرجاع
                            </button>
                            <button class="btn btn-outline-secondary btn-sm action-btn" id="clear-return-btn">
                                <i class="fas fa-eraser me-1"></i> مسح الكل
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick totals and actions panel -->
            <div class="card shadow-sm sales-card small-card">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div>
                            <span class="small fw-bold">إجمالي المرتجع:</span>
                            <span id="totals-return-subtotal" class="badge bg-primary">0.00</span>
                        </div>
                        <div>
                            <span class="small fw-bold">الأصناف:</span>
                            <span id="totals-return-items" class="badge bg-secondary">0</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">المبلغ المسترد:</span>
                        <span class="h5 mb-0 text-danger" id="totals-return-final">0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <button class="btn btn-danger btn-sm flex-grow-1" id="quick-process-return">
                            <i class="fas fa-check-circle"></i> (F1) تأكيد الإرجاع
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Original Invoice Items (for returning from invoice) -->
<div class="modal fade" id="original-invoice-items-modal" tabindex="-1" aria-labelledby="originalInvoiceItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2 bg-info text-white">
                <h5 class="modal-title" id="originalInvoiceItemsModalLabel"><i class="fas fa-file-invoice me-2"></i>أصناف الفاتورة الأصلية</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <p><strong>رقم الفاتورة:</strong> <span id="modal-invoice-number"></span></p>
                    <p><strong>العميل:</strong> <span id="modal-invoice-customer"></span></p>
                    <p><strong>التاريخ:</strong> <span id="modal-invoice-date"></span></p>
                </div>
                <div class="table-responsive" style="max-height: 45vh;">
                    <table class="table table-hover table-sm" id="modal-invoice-items-table">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>المنتج/الوحدة</th>
                                <th>السعر</th>
                                <th>الكمية المباعة</th>
                                <th>الكمية المرتجعة سابقاً</th>
                                <th>الكمية المتاحة للإرجاع</th>
                                <th width="120">كمية الإرجاع الحالية</th>
                                <th width="150">سبب الإرجاع</th>
                                <th width="50">اختيار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Invoice items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary btn-sm" id="add-selected-items-to-return-list-btn">
                    <i class="fas fa-plus-circle me-1"></i> إضافة المحدد إلى قائمة الإرجاع
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Recent Returns -->
<div class="modal fade" id="recent-returns-modal" tabindex="-1" aria-labelledby="recentReturnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title" id="recentReturnsModalLabel"><i class="fas fa-history me-2"></i>مرتجعات حديثة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>قائمة بآخر عمليات الإرجاع المسجلة.</p>
                <!-- Placeholder for recent returns list -->
                 <div class="text-center p-3"> (سيتم عرض قائمة المرتجعات الحديثة هنا) </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="{{ asset('/assets/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('/assets/select2-bootstrap-5-theme.min.css') }}" />
<style>
/* Styles from sales/index.blade.php, adapted for returns */
.sales-return-container {
    background-color: #f5f7fa;
}

.sales-card { /* Reusing sales-card style */
    border: none;
    border-radius: 8px;
    overflow: hidden;
}

.compact-controls {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 0.5rem;
}

.compact-controls .nav-tabs .nav-link {
    padding: 0.4rem 0.5rem;
    font-size: 0.8rem;
    transition: all 0.2s;
    border-radius: 0;
}
.bg-white-hover:hover {
    background-color: #ffffff !important;
}
.nav-tabs .nav-link {
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    background-color: #ffffff;
    border-bottom-color: transparent;
    font-weight: bold;
    color: #212529;
}
.nav-tabs .nav-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.5);
    border-color: #dee2e6 #dee2e6 #fff;
    color: #495057;
}
.compact-controls .tab-content {
    max-height: calc(100vh - 400px); /* Adjust as needed */
    overflow-y: auto;
}
.small-card {
    font-size: 0.85rem;
}
.card-header {
    border-bottom: 0;
    padding: 0.5rem 1rem;
}
.search-group .form-control {
    border: 1px solid #dee2e6;
    border-left: 0;
}
.search-group .form-control:focus {
    box-shadow: none;
    border-color: var(--bs-primary);
}
.search-group .input-group-text {
    border: 1px solid var(--bs-primary);
}
.quick-action-btn {
    border-width: 1px;
    min-width: 36px;
}
.form-control, .form-select {
    border: 1px solid #dee2e6;
}
.form-control:focus, .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.15rem rgba(var(--bs-primary-rgb), 0.25);
}
.summary-box {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 8px 10px;
    border-left: 3px solid var(--bs-primary);
    margin-bottom: 0.25rem;
}
.total-amount-box {
    background-color: #f8f9fa; /* Light background */
    border-radius: 6px;
    padding: 8px 10px;
    border-left: 4px solid var(--bs-danger); /* Danger color for refund */
}
.action-btn {
    border-radius: 6px;
    padding: 8px;
    transition: all 0.2s;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.12);
}

/* Table styling for returned items */
#returned-items-table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 100;
    font-weight: bold;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.8rem;
    padding: 0.5rem;
}
#returned-items-table tbody td {
    padding: 0.3rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
}
#returned-items-table .form-control,
#returned-items-table .form-select {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem; /* Smaller font for inputs in table */
    height: auto;
}

/* Search suggestions */
.search-suggestions {
    position: absolute;
    width: calc(100% - 40px); /* Adjust based on button width */
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    left: 0; /* Align with the input group */
    top: 100%; /* Position below the input group */
}
.suggestions-container {
    padding: 5px 0;
}
.suggestion-item {
    padding: 6px 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}
.suggestion-item:last-child {
    border-bottom: none;
}
.suggestion-item:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

/* Modal Table Adjustments */
#modal-invoice-items-table .form-control,
#modal-invoice-items-table .form-select {
     font-size: 0.8rem;
}
#modal-invoice-items-table th,
#modal-invoice-items-table td {
    font-size: 0.85rem;
    padding: 0.4rem;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('/assets/select2.min.js') }}"></script>
<script src="{{ asset('js/sales-returns.js') }}"></script>
<script>
    // Define CSRF_TOKEN for AJAX requests
    const CSRF_TOKEN = '{{ csrf_token() }}';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    // --- Global State ---
    let currentReturnItems = []; // Array to hold items being returned
    let originalInvoiceData = null; // To store details of the fetched original invoice

    // --- UI Elements ---
    const searchInput = document.getElementById('return-search-input');
    const searchBtn = document.getElementById('return-search-btn');
    const suggestionsDiv = document.getElementById('return-search-suggestions');
    const suggestionsContainer = suggestionsDiv.querySelector('.suggestions-container');
    const returnModeSelector = document.getElementById('return-mode-selector');

    const returnedItemsTableBody = document.getElementById('returned-items-table').querySelector('tbody');

    // Summary fields
    const returnSubtotalEl = document.getElementById('return-subtotal');
    const returnItemsCountEl = document.getElementById('return-items-count');
    const returnTotalRefundEl = document.getElementById('return-total-refund');

    // Quick totals
    const totalsReturnSubtotalEl = document.getElementById('totals-return-subtotal');
    const totalsReturnItemsEl = document.getElementById('totals-return-items');
    const totalsReturnFinalEl = document.getElementById('totals-return-final');

    // Buttons
    const processReturnBtn = document.getElementById('process-return-btn');
    const quickProcessReturnBtn = document.getElementById('quick-process-return');
    const clearReturnBtn = document.getElementById('clear-return-btn');
    const addSelectedItemsToReturnListBtn = document.getElementById('add-selected-items-to-return-list-btn');

    // Modals
    const originalInvoiceItemsModal = new bootstrap.Modal(document.getElementById('original-invoice-items-modal'));
    const modalInvoiceItemsTableBody = document.getElementById('modal-invoice-items-table').querySelector('tbody');


    // --- Event Listeners ---
    searchBtn.addEventListener('click', handleSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });
     searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length > 2) {
            // Implement live suggestions if needed
            // For now, rely on Enter/Search button
        } else {
            suggestionsDiv.classList.add('d-none');
        }
    });


    returnModeSelector.addEventListener('change', function() {
        clearReturnForm();
        searchInput.value = '';
        searchInput.placeholder = this.value === 'invoice' ? "ابحث برقم الفاتورة الأصلية..." : "ابحث باسم المنتج أو الباركود...";
    });

    processReturnBtn.addEventListener('click', processReturn);
    quickProcessReturnBtn.addEventListener('click', processReturn);
    clearReturnBtn.addEventListener('click', clearReturnForm);
    addSelectedItemsToReturnListBtn.addEventListener('click', addSelectedModalItemsToReturnList);


    // --- Core Functions ---
    function handleSearch() {
        const query = searchInput.value.trim();
        const mode = returnModeSelector.value;

        if (!query) {
            showAppAlert('يرجى إدخال رقم الفاتورة أو اسم المنتج للبحث.', 'warning');
            return;
        }

        if (mode === 'invoice') {
            fetchOriginalInvoice(query);
        } else {
            // Direct item return search - could be product search or adding a generic item
            // For simplicity, let's assume this will be a product search for now
            searchProductForDirectReturn(query);
        }
    }

    function fetchOriginalInvoice(invoiceId) {
        showLoading('جاري البحث عن الفاتورة...');
        
        // Actual API call
        fetch(`/api/sales/invoices/by-number/${invoiceId}`, { // CORRECTED API ROUTE
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN // Make sure CSRF_TOKEN is defined globally
            }
        })
        .then(response => {
            hideLoading();
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'حدث خطأ أثناء جلب الفاتورة. الحالة: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.invoice) { // CORRECTED SUCCESS CHECK
                originalInvoiceData = data.invoice; // CORRECTLY ACCESS NESTED INVOICE OBJECT
                populateInvoiceModal(originalInvoiceData);
                originalInvoiceItemsModal.show();
            } else {
                showAppAlert(data.message || 'لم يتم العثور على الفاتورة أو البيانات غير مكتملة.', 'danger');
                originalInvoiceData = null;
            }
        })
        .catch(error => {
            hideLoading();
            showAppAlert(error.message || 'فشل الاتصال بالخادم لجلب الفاتورة.', 'danger');
            originalInvoiceData = null;
        });
    }
    
    function fetchProductByBarcode(barcode) {
        showLoading('جاري البحث عن المنتج...');
        fetch(`/api/sales/products/search?barcode=${barcode}`)
            .then(response => {
                hideLoading();
                if (!response.ok) throw new Error('فشل البحث عن المنتج.');
                return response.json();
            })
            .then(data => {
                if (data.success && data.products.length > 0) {
                    const product = data.products[0]; // Assume first result is correct for barcode search
                    
                    // Now get units for this product
                    fetch(`/api/products-api/${product.id}/units`)
                        .then(res => res.json())
                        .then(unitData => {
                            if (unitData.success && unitData.units.length > 0) {
                                // For simplicity, add the main/first unit to the return list
                                const unit = unitData.units[0];
                                addOrUpdateReturnItem({
                                    product_id: product.id,
                                    unit_id: unit.id,
                                    product_name: product.name,
                                    unit_name: unit.name,
                                    original_price: unit.price, // Use default price as original
                                    return_price: unit.price,
                                    quantity_returned: 1,
                                    reason: '',
                                    original_invoice_item_id: null
                                });
                                updateReturnListUI();
                                calculateTotals();
                                searchInput.value = ''; // Clear search
                            } else {
                                showAppAlert('لم يتم العثور على وحدات لهذا المنتج.', 'danger');
                            }
                        });
                } else {
                    showAppAlert('لم يتم العثور على منتج بهذا الباركود.', 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showAppAlert(error.message, 'danger');
            });
    }

    function searchProductForDirectReturn(query) {
        // We'll primarily use this for barcode scanning
        fetchProductByBarcode(query);
    }

    function populateInvoiceModal(invoice) {
        document.getElementById('modal-invoice-number').textContent = invoice.invoice_number;
        document.getElementById('modal-invoice-customer').textContent = invoice.customer_name;
        document.getElementById('modal-invoice-date').textContent = invoice.date;

        modalInvoiceItemsTableBody.innerHTML = ''; // Clear previous items
        invoice.items.forEach(item => {
            const availableToReturn = item.quantity_sold - item.quantity_returned_previously;
            if (availableToReturn <= 0) return; // Skip if nothing to return

            const row = document.createElement('tr');
            row.dataset.invoiceItemId = item.id;
            row.dataset.productId = item.product_id;
            row.dataset.productName = item.product_name;
            row.dataset.unitId = item.unit_id;
            row.dataset.unitName = item.unit_name;
            row.dataset.price = item.price;
            row.dataset.quantitySold = item.quantity_sold;
            row.dataset.quantityReturnedPreviously = item.quantity_returned_previously;
            row.dataset.availableToReturn = availableToReturn;

            row.innerHTML = `
                <td>${item.product_name} <small class="text-muted">(${item.unit_name})</small></td>
                <td>${formatCurrency(item.price)}</td>
                <td>${item.quantity_sold}</td>
                <td>${item.quantity_returned_previously}</td>
                <td class="fw-bold ${availableToReturn > 0 ? 'text-success' : 'text-danger'}">${availableToReturn}</td>
                <td>
                    <input type="number" class="form-control form-control-sm modal-return-qty" value="0" min="0" max="${availableToReturn}" ${availableToReturn === 0 ? 'disabled' : ''}>
                </td>
                <td>
                    <select class="form-select form-select-sm modal-return-reason" ${availableToReturn === 0 ? 'disabled' : ''}>
                        <option value="">اختر...</option>
                        <option value="defective">تالف</option>
                        <option value="wrong_item">صنف خاطئ</option>
                        <option value="customer_dissatisfaction">لم يعجب العميل</option>
                        <option value="other">آخر</option>
                    </select>
                </td>
                <td>
                    <input class="form-check-input modal-item-select" type="checkbox" ${availableToReturn === 0 ? 'disabled' : ''}>
                </td>
            `;
            modalInvoiceItemsTableBody.appendChild(row);
        });
    }

    function addSelectedModalItemsToReturnList() {
        const selectedRows = modalInvoiceItemsTableBody.querySelectorAll('tr .modal-item-select:checked');
        selectedRows.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const returnQtyInput = row.querySelector('.modal-return-qty');
            const quantityToReturn = parseInt(returnQtyInput.value);

            if (quantityToReturn > 0) {
                const itemData = {
                    product_id: parseInt(row.dataset.productId),
                    unit_id: parseInt(row.dataset.unitId),
                    product_name: row.dataset.productName,
                    unit_name: row.dataset.unitName,
                    original_price: parseFloat(row.dataset.price),
                    return_price: parseFloat(row.dataset.price),
                    quantity_returned: quantityToReturn,
                    reason: row.querySelector('.modal-return-reason').value,
                    original_invoice_item_id: row.dataset.invoiceItemId
                };
                addOrUpdateReturnItem(itemData);
            }
        });
        updateReturnListUI();
        calculateTotals();
        originalInvoiceItemsModal.hide();
    }

    function addOrUpdateReturnItem(itemData) {
        // For simplicity, we'll assume each addition is a new line item in the return list.
        // A more complex scenario might involve updating quantity if the same item from the same invoice is added again.
        currentReturnItems.push(itemData);
    }

    function updateReturnListUI() {
        returnedItemsTableBody.innerHTML = ''; // Clear current list
        currentReturnItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.dataset.itemIndex = index; // To identify item for removal or update

            row.innerHTML = `
                <td>${item.product_name} <small class="text-muted">(${item.unit_name})</small></td>
                <td>${formatCurrency(item.original_price)}</td>
                <td>
                    <input type="number" class="form-control form-control-sm return-list-qty" value="${item.quantity_returned}" min="1" data-index="${index}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm return-list-price" value="${item.return_price.toFixed(2)}" step="0.01" min="0" data-index="${index}">
                </td>
                <td class="return-item-subtotal">${formatCurrency(item.quantity_returned * item.return_price)}</td>
                <td>
                     <select class="form-select form-select-sm return-list-reason" data-index="${index}">
                        <option value="">اختر...</option>
                        <option value="defective" ${item.reason === 'defective' ? 'selected' : ''}>تالف</option>
                        <option value="wrong_item" ${item.reason === 'wrong_item' ? 'selected' : ''}>صنف خاطئ</option>
                        <option value="customer_dissatisfaction" ${item.reason === 'customer_dissatisfaction' ? 'selected' : ''}>لم يعجب العميل</option>
                        <option value="other" ${item.reason === 'other' ? 'selected' : ''}>آخر</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-danger btn-sm remove-return-item-btn" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            returnedItemsTableBody.appendChild(row);
        });

        // Add event listeners for dynamically created elements
        returnedItemsTableBody.querySelectorAll('.remove-return-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentReturnItems.splice(this.dataset.index, 1);
                updateReturnListUI();
                calculateTotals();
            });
        });
        returnedItemsTableBody.querySelectorAll('.return-list-qty, .return-list-price, .return-list-reason').forEach(input => {
            input.addEventListener('change', function() {
                const index = this.dataset.index;
                if (this.classList.contains('return-list-qty')) {
                    currentReturnItems[index].quantity_returned = parseInt(this.value);
                } else if (this.classList.contains('return-list-price')) {
                    currentReturnItems[index].return_price = parseFloat(this.value);
                } else if (this.classList.contains('return-list-reason')) {
                     currentReturnItems[index].reason = this.value;
                }
                // Update subtotal for the specific row
                const row = this.closest('tr');
                row.querySelector('.return-item-subtotal').textContent = formatCurrency(currentReturnItems[index].quantity_returned * currentReturnItems[index].return_price);
                calculateTotals();
            });
        });
    }

    function calculateTotals() {
        let subtotal = 0;
        let itemsCount = 0;

        currentReturnItems.forEach(item => {
            subtotal += item.quantity_returned * item.return_price;
            itemsCount += item.quantity_returned;
        });

        // For now, total refund is same as subtotal. Could add restocking fees or other adjustments.
        const totalRefund = subtotal;

        returnSubtotalEl.textContent = formatCurrency(subtotal);
        returnItemsCountEl.textContent = itemsCount;
        returnTotalRefundEl.textContent = formatCurrency(totalRefund);

        totalsReturnSubtotalEl.textContent = formatCurrency(subtotal);
        totalsReturnItemsEl.textContent = itemsCount;
        totalsReturnFinalEl.textContent = formatCurrency(totalRefund);
    }

    function processReturn() {
        if (currentReturnItems.length === 0) {
            showAppAlert('قائمة الإرجاع فارغة. يرجى إضافة أصناف أولاً.', 'warning');
            return;
        }

        const customerId = document.getElementById('return-customer-id').value || null;
        const mainReason = document.getElementById('main-return-reason').value;
        const notes = document.getElementById('return-notes').value;
        const restockItems = document.getElementById('restock-items-switch').checked;
        const refundMethod = document.getElementById('refund-method').value;
        // const totalRefundAmount = parseFloat(returnTotalRefundEl.textContent.replace(/[^0-9.-]+/g,""));

        let apiUrl = '';
        let payload = {
            customer_id: customerId,
            notes: notes,
            restock_items: restockItems,
            // refund_method might be handled by backend or recorded differently
        };

        const returnMode = returnModeSelector.value; // 'invoice' or 'item'

        if (returnMode === 'invoice' && originalInvoiceData && originalInvoiceData.id) {
            apiUrl = `/api/sales-returns/invoice/partial`; 
            payload.invoice_id = originalInvoiceData.id;
            payload.items = currentReturnItems.map(item => {
                return {
                    product_id: item.product_id,
                    unit_id: item.unit_id,
                    quantity: item.quantity_returned,
                    unit_price: item.return_price
                };
            });
        } else if (returnMode === 'item') {
            if (currentReturnItems.length === 1) {
                apiUrl = '/api/sales-returns/item';
                const item = currentReturnItems[0];
                payload.product_id = item.product_id;
                payload.unit_id = item.unit_id; 
                payload.quantity = item.quantity_returned;
                payload.unit_price = item.return_price; // This is unit_price_returned
                // mainReason could be used as notes for direct item, or notes field directly
            } else {
                showAppAlert('للإرجاع المباشر لصنف واحد، يرجى التأكد من وجود صنف واحد فقط في القائمة.', 'warning');
                return;
            }
        } else {
            showAppAlert('لا يمكن تحديد وضع الإرجاع أو بيانات الفاتورة الأصلية مفقودة.', 'danger');
            return;
        }

        if (!apiUrl) {
            showAppAlert('لم يتم تحديد مسار API صالح للإرجاع.', 'danger');
            return;
        }

        showLoading('جاري معالجة الإرجاع...');

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            hideLoading();
            if (!response.ok) {
                return response.json().then(err => {
                    let errorMsg = err.message || 'فشل في معالجة الإرجاع. الحالة: ' + response.status;
                    if (err.errors) { // Handle Laravel validation errors
                        errorMsg += '<ul>';
                        for (const key in err.errors) {
                            errorMsg += `<li>${err.errors[key].join(', ')}</li>`;
                        }
                        errorMsg += '</ul>';
                    }
                    throw new Error(errorMsg);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAppAlert(data.message || 'تمت معالجة الإرجاع بنجاح!', 'success');
                clearReturnForm();
            } else {
                showAppAlert(data.message || 'حدث خطأ أثناء معالجة الإرجاع.', 'danger');
            }
        })
        .catch(error => {
            hideLoading();
            showAppAlert(error.message || 'فشل الاتصال بالخادم لمعالجة الإرجاع.', 'danger');
        });
    }

    function clearReturnForm() {
        currentReturnItems = [];
        originalInvoiceData = null;
        updateReturnListUI();
        calculateTotals();

        document.getElementById('return-customer-id').value = "";
        $('#return-customer-id').trigger('change'); // For Select2
        document.getElementById('main-return-reason').value = "";
        document.getElementById('return-notes').value = "";
        document.getElementById('restock-items-switch').checked = true;
        document.getElementById('refund-method').value = "cash";
        searchInput.value = "";
        showAppAlert('تم مسح النموذج.', 'info', 2000);
    }


    // --- Utility Functions ---
    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'); // Basic formatting
    }

    let alertTimeout;
    function showAppAlert(message, type = 'info', duration = 5000) {
        const existingAlert = document.querySelector('.app-alert-toast');
        if (existingAlert) {
            existingAlert.remove();
            clearTimeout(alertTimeout);
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} app-alert-toast position-fixed top-0 end-0 m-3 shadow-lg`;
        alertDiv.style.zIndex = "1055"; // Ensure it's above modals
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <div>${message}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss
        alertTimeout = setTimeout(() => {
            $(alertDiv).fadeOut(500, function() { $(this).remove(); });
        }, duration);
         // Clicking close button
        alertDiv.querySelector('.btn-close').addEventListener('click', function() {
            $(alertDiv).fadeOut(500, function() { $(this).remove(); });
            clearTimeout(alertTimeout);
        });
    }

    function showLoading(message = 'جاري التحميل...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    function hideLoading() {
        Swal.close();
    }

    // Initial calculation for empty form
    calculateTotals();

    document.addEventListener('keydown', function(event) {
        if (event.key === 'F1') {
            event.preventDefault();
            document.getElementById('quick-process-return').click();
        }
    });
});
</script>
@endpush
