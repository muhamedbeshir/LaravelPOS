<!-- LEFT COLUMN: Products search and selection -->
<style>
/* Remove spinner arrows from number inputs */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

/* Improved table styling for products */
#invoice-items tbody td {
    vertical-align: middle;
    padding: 8px 6px;
}

#invoice-items tbody td.text-center {
    text-align: center !important;
}

#invoice-items tbody td.text-start {
    text-align: start !important;
}

/* Input styling in table cells */
#invoice-items .form-control {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 0.875rem;
    padding: 4px 8px;
    min-height: 32px;
}

#invoice-items .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Compact button styling */
#invoice-items .btn-sm {
    padding: 2px 6px;
    font-size: 0.75rem;
    line-height: 1.2;
}

/* Product image styling */
#invoice-items .product-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #invoice-items th,
    #invoice-items td {
        padding: 4px 2px;
        font-size: 0.8rem;
    }
    
    #invoice-items .form-control {
        font-size: 0.8rem;
        padding: 2px 4px;
        min-height: 28px;
    }
}
</style>
<div class="col-lg-8 col-md-7 pe-lg-1">
    <!-- Search bar and quick actions -->
    <div class="card shadow-sm sales-card mb-2">
        <div class="card-body p-2">
            <div class="row g-2">
                <div class="col-md-8">
                    <div class="input-group search-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-barcode"></i>
                        </span>
                        <input type="text" class="form-control" id="search-input" 
                               placeholder="ابحث بالباركود أو اسم المنتج..." autofocus>
                        <button class="btn btn-primary" type="button" id="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="search-suggestions" class="search-suggestions d-none">
                        <div class="suggestions-container">
                            <!-- الاقتراحات ستظهر هنا -->
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <button class="btn btn-outline-primary btn-sm quick-action-btn me-1" data-bs-toggle="modal" data-bs-target="#categories-modal">
                <i class="fas fa-th-large"></i>
                        <span class="d-none d-md-inline-block ms-1">المجموعات</span>
            </button>
                    <button class="btn btn-outline-primary btn-sm quick-action-btn me-1" data-bs-toggle="modal" data-bs-target="#products-modal">
                <i class="fas fa-box"></i>
                        <span class="d-none d-md-inline-block ms-1">المنتجات</span>
            </button>
                    <button class="btn btn-outline-warning btn-sm quick-action-btn" id="btn-show-suspended-sales" data-bs-toggle="modal" data-bs-target="#suspended-sales-modal">
                        <i class="fas fa-pause-circle"></i>
                        <span class="d-none d-md-inline-block ms-1">المعلقة</span>
            </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice items table -->
    <div class="card shadow-sm sales-card">
        <div class="card-header bg-primary text-white py-1">
            <h6 class="card-title mb-0"><i class="fas fa-shopping-cart me-1"></i>قائمة المنتجات</h6>
        </div>
<div class="card-body p-0">
            <div class="table-responsive" style="height: calc(100vh - 190px);">
                <table class="table table-hover table-sm table-striped mb-0" id="invoice-items">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="60" class="text-center">الصورة</th>
                            <th class="text-start">المنتج</th>
                            <th width="90" class="text-center">السعر</th>
                            <th width="80" class="text-center">الكمية</th>
                            <th width="90" class="text-center">الإجمالي</th>
                            <th width="130" class="text-center">الخصم</th>
                            <th width="90" class="text-center">الصافي</th>
                            <th width="90" class="profit-column text-center" style="{{ !$settings->get('show_profit_in_sales_table') ? 'display:none;' : '' }}">الربح</th>
                            <th width="50" class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- سيتم إضافة المنتجات هنا ديناميكيًا -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 

@push('scripts')
<script>
    document.addEventListener('keydown', function(event) {
        if (event.key === 'F2') {
            event.preventDefault();
            const rows = document.querySelectorAll('#invoice-items tbody tr');
            if (rows.length > 0) {
                const lastRow = rows[rows.length - 1];
                const removeBtn = lastRow.querySelector('button.btn-danger');
                if (removeBtn) {
                    removeBtn.click();
                }
            }
        }
    });
</script>
@endpush 