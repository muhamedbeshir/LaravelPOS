@extends('layouts.app')

@section('title', 'تقرير المبيعات والفواتير')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>تقرير المبيعات والفواتير</h5>
                        {{-- Removed export button from here as it's below with other actions --}}
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter section -->
                    <form method="GET" action="{{ route('reports.all-invoices') }}" class="mb-4 p-3 border rounded bg-light">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date', date('Y-01-01')) }}">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}">
                            </div>

                            <!-- Quick Range -->
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">نطاق زمني سريع</label>
                                <select class="form-select form-select-sm" id="quick-range">
                                    <option value="">اختر...</option>
                                    <option value="today">اليوم</option>
                                    <option value="yesterday">أمس</option>
                                    <option value="this_week">الأسبوع الحالي</option>
                                    <option value="last_7">آخر 7 أيام</option>
                                    <option value="this_month">الشهر الحالي</option>
                                    <option value="last_month">الشهر الماضي</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">نوع الفاتورة</label>
                                <select class="form-select form-select-sm" name="invoice_type">
                                    <option value="">الكل</option>
                                    <option value="cash" {{ request('invoice_type') == 'cash' ? 'selected' : '' }}>كاش</option>
                                    <option value="credit" {{ request('invoice_type') == 'credit' ? 'selected' : '' }}>آجل</option>
                                    <option value="visa" {{ request('invoice_type') == 'visa' ? 'selected' : '' }}>فيزا</option>
                                    <option value="transfer" {{ request('invoice_type') == 'transfer' ? 'selected' : '' }}>تحويل</option>
                                    <option value="mixed" {{ request('invoice_type') == 'mixed' ? 'selected' : '' }}>متعدد</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">نوع الطلب</label>
                                <select class="form-select form-select-sm" name="order_type">
                                    <option value="">الكل</option>
                                    <option value="takeaway" {{ request('order_type') == 'takeaway' ? 'selected' : '' }}>تيك أواي</option>
                                    <option value="delivery" {{ request('order_type') == 'delivery' ? 'selected' : '' }}>دليفري</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">الحالة</label>
                                <select class="form-select form-select-sm" name="status">
                                    <option value="">الكل</option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلقة</option>
                                    <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>ملغية</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">العميل</label>
                                <select class="form-select form-select-sm" name="customer_id">
                                    <option value="">كل العملاء</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 col-lg-auto mt-3 mt-lg-0 text-end">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter me-1"></i>تصفية
                                </button>
                                <a href="{{ route('reports.all-invoices') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-redo me-1"></i>إعادة تعيين
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                             <div class="col-md-12 text-start">
                                <button type="button" class="btn btn-success btn-sm" id="export-excel">
                                    <i class="fas fa-file-excel me-1"></i>تصدير Excel
                                </button>
               
                            </div>
                        </div>
                    </form>

                    <!-- Summary boxes -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">إجمالي الفواتير</h5>
                                    <h2 class="mb-0">{{ number_format($summary['total_invoices'] ?? 0) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">إجمالي المبيعات</h5>
                                    <h2 class="mb-0">{{ number_format($summary['total_sales'] ?? 0, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">متوسط قيمة الفاتورة</h5>
                                    <h2 class="mb-0">{{ number_format($summary['average_invoice_value'] ?? 0, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">إجمالي الأرباح</h5>
                                    <h2 class="mb-0">{{ number_format($summary['total_profit'] ?? 0, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="invoices-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>نوع الفاتورة</th>
                                    <th>نوع الطلب</th>
                                    <th>الحالة</th>
                                    <th>المجموع</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>الربح</th>
                                    <th>العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                <tr class="{{ $invoice->status == 'canceled' ? 'table-danger' : ($invoice->status == 'pending' ? 'table-warning' : '') }}">
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $invoice->customer->name }}</td>
                                    @php
                                        $typeBadgeClasses = [
                                            'cash'     => 'bg-success',
                                            'credit'   => 'bg-warning',
                                            'visa'     => 'bg-primary',
                                            'transfer' => 'bg-info',
                                            'mixed'    => 'bg-secondary',
                                        ];
                                        $typeLabels = [
                                            'cash'     => 'كاش',
                                            'credit'   => 'آجل',
                                            'visa'     => 'فيزا',
                                            'transfer' => 'تحويل',
                                            'mixed'    => 'متعدد',
                                        ];
                                        $badgeClass = $typeBadgeClasses[$invoice->type] ?? 'bg-secondary';
                                        $label      = $typeLabels[$invoice->type] ?? $invoice->type;
                                    @endphp
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $orderBadge = $invoice->order_type == 'takeaway' ? 'bg-primary' : 'bg-info';
                                            $orderLabel = $invoice->order_type == 'takeaway' ? 'تيك أواي' : 'دليفري';
                                            $orderIcon  = $invoice->order_type == 'takeaway' ? 'fa-shopping-bag' : 'fa-motorcycle';
                                        @endphp
                                        <span class="badge {{ $orderBadge }} d-flex align-items-center gap-1">
                                            <i class="fas {{ $orderIcon }}"></i> {{ $orderLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($invoice->status == 'completed')
                                            <span class="badge bg-success">مكتملة</span>
                                        @elseif($invoice->status == 'pending')
                                            <span class="badge bg-warning">معلقة</span>
                                        @elseif($invoice->status == 'canceled')
                                            <span class="badge bg-danger">ملغية</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($invoice->total, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->remaining_amount, 2) }}</td>
                                    <td>
                                        <span class="{{ $invoice->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($invoice->profit, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="#" class="btn btn-primary view-invoice" data-id="{{ $invoice->id }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('sales.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-info">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">لا توجد فواتير مطابقة للبحث</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light rounded">
                        <div class="pagination-info">
                            <small class="text-muted">
                                عرض {{ $invoices->firstItem() ?? 0 }} إلى {{ $invoices->lastItem() ?? 0 }} 
                                من أصل {{ $invoices->total() }} فاتورة
                            </small>
                        </div>
                        <div class="pagination-nav">
                            {{ $invoices->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة <span id="invoice-number"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="invoice-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل بيانات الفاتورة...</p>
                </div>
                
                <div id="invoice-content" style="display: none;">
                    <!-- Invoice Header -->
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
                    
                    <!-- Invoice Items -->
                    <h6 class="fw-bold mb-3">منتجات الفاتورة</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الوحدة</th>
                                    <th>الكمية</th>
                                    <th>السعر</th>
                                    <th>الخصم</th>
                                    <th>الإجمالي</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items">
                                <!-- Items will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Payments Table -->
                    <h6 class="fw-bold mb-3">دفعات الفاتورة</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>طريقة الدفع</th>
                                    <th>المبلغ</th>
                                    <th>المرجع</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-payments">
                                <!-- Filled dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Invoice Summary -->
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th>المجموع قبل الخصم</th>
                                        <td id="modal-subtotal"></td>
                                    </tr>
                                    <tr>
                                        <th>الخصم</th>
                                        <td id="modal-discount"></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <th>الإجمالي</th>
                                        <td id="modal-total"></td>
                                    </tr>
                                    <tr>
                                        <th>المدفوع</th>
                                        <td id="modal-paid"></td>
                                    </tr>
                                    <tr>
                                        <th>المتبقي</th>
                                        <td id="modal-remaining"></td>
                                    </tr>
                                    <tr class="table-success">
                                        <th>إجمالي الربح</th>
                                        <td id="modal-profit"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <a href="#" class="btn btn-primary" id="print-invoice-btn" target="_blank">
                    <i class="fas fa-print me-1"></i> طباعة الفاتورة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Quick range helper
        const startInput = $('#start_date');
        const endInput   = $('#end_date');

        function formatDate(d){
            const pad = n => n.toString().padStart(2,'0');
            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
        }

        $('#quick-range').change(function(){
            const val = $(this).val();
            const today = new Date();
            let start = new Date();
            let end   = new Date();

            switch(val){
                case 'today':
                    // start & end already today
                    break;
                case 'yesterday':
                    start.setDate(today.getDate()-1);
                    end.setDate(today.getDate()-1);
                    break;
                case 'this_week':
                    const day = today.getDay(); // 0=Sun
                    const diff = day === 0 ? 6 : day-1; // Monday as first day
                    start.setDate(today.getDate()-diff);
                    break;
                case 'last_7':
                    start.setDate(today.getDate()-6);
                    break;
                case 'this_month':
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'last_month':
                    start = new Date(today.getFullYear(), today.getMonth()-1, 1);
                    end   = new Date(today.getFullYear(), today.getMonth(), 0); // last day prev month
                    break;
                default:
                    return; // do nothing
            }

            startInput.val(formatDate(start));
            endInput.val(formatDate(end));
        });
        
        // Print Report
        $('#print-report').click(function() {
            window.print();
        });
        
        // Export to Excel - Send to backend
        $('#export-excel').click(function() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = "{{ route('reports.all-invoices') }}/export?" + params.toString();
        });
        
        // View Invoice Details
        $('.view-invoice').click(function(e) {
            e.preventDefault();
            const invoiceId = $(this).data('id');
            
            // Reset modal content
            $('#invoice-loading').show();
            $('#invoice-content').hide();
            $('#invoice-items').empty();
            $('#invoice-payments').empty();
            
            // Show modal
            $('#invoiceDetailsModal').modal('show');
            
            // Set the print button URL
            $('#print-invoice-btn').attr('href', `{{ url('sales/invoices') }}/${invoiceId}/print`);
            
            // Fetch invoice details
            $.ajax({
                url: `{{ url('api/sales/invoices') }}/${invoiceId}`,
                method: 'GET',
                success: function(response) {
                    console.log('API Response:', response);
                    if (response.success) {
                        const invoice = response.invoice;
                        
                        // Set invoice details
                        $('#invoice-number, #modal-invoice-number').text(invoice.invoice_number);
                        $('#modal-invoice-date').text(new Date(invoice.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        }));
                        
                        // Handle different field name variations
                        const typeMap = {
                            'cash'    : 'كاش',
                            'credit'  : 'آجل',
                            'visa'    : 'فيزا',
                            'transfer': 'تحويل',
                            'mixed'   : 'متعدد'
                        };
                        const invoiceType = invoice.type || invoice.invoice_type || 'cash';
                        $('#modal-invoice-type').text(typeMap[invoiceType] ?? invoiceType);
                        
                        $('#modal-invoice-order-type').text(invoice.order_type === 'takeaway' ? 'تيك أواي' : 'دليفري');
                        
                        let statusText = '';
                        if (invoice.status === 'completed') {
                            statusText = '<span class="badge bg-success">مكتملة</span>';
                        } else if (invoice.status === 'pending') {
                            statusText = '<span class="badge bg-warning">معلقة</span>';
                        } else if (invoice.status === 'canceled') {
                            statusText = '<span class="badge bg-danger">ملغية</span>';
                        }
                        $('#modal-invoice-status').html(statusText);
                        
                        // Set customer details
                        if (invoice.customer) {
                            $('#modal-customer-name').text(invoice.customer.name);
                            $('#modal-customer-phone').text(invoice.customer.phone || 'غير متوفر');
                            $('#modal-customer-address').text(invoice.customer.address || 'غير متوفر');
                        }
                        
                        // Set invoice items
                        if (invoice.items && invoice.items.length > 0) {
                            $.each(invoice.items, function(index, item) {
                                try {
                                    // Safely get values with defaults
                                    const quantity = Number(item.quantity || 0);
                                    const unitPrice = Number(item.unit_price || 0);
                                    const total = Number(item.total || 0);
                                    const profit = Number(item.profit || 0);
                                    const discountPercentage = Number(item.discount_percentage || 0);
                                    const discountValue = Number(item.discount_value || 0);
                                    
                                    const discountText = discountPercentage > 0 
                                        ? `${discountPercentage}%` 
                                        : `${discountValue}`;
                                        
                                    const row = `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${item.product ? item.product.name : 'غير متوفر'}</td>
                                            <td>${item.product_unit && item.product_unit.unit ? item.product_unit.unit.name : 'غير متوفر'}</td>
                                            <td>${quantity}</td>
                                            <td>${unitPrice.toFixed(2)}</td>
                                            <td>${discountText}</td>
                                            <td>${total.toFixed(2)}</td>
                                            <td>${profit.toFixed(2)}</td>
                                        </tr>
                                    `;
                                    $('#invoice-items').append(row);
                                } catch (err) {
                                    console.error('Error processing invoice item:', err, item);
                                    // Add a row with error indication
                                    $('#invoice-items').append(`
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td colspan="7" class="text-danger">خطأ في عرض تفاصيل المنتج</td>
                                        </tr>
                                    `);
                                }
                            });
                        } else {
                            $('#invoice-items').html('<tr><td colspan="8" class="text-center">لا توجد منتجات</td></tr>');
                        }
                        
                        // Set payments table
                        if (invoice.payments && invoice.payments.length) {
                            invoice.payments.forEach(function(pay){
                                const methodLabel = (function(m){
                                    switch(m){
                                        case 'cash': return 'كاش';
                                        case 'credit': return 'آجل';
                                        case 'visa': return 'فيزا';
                                        case 'transfer': return 'تحويل';
                                        default: return m;
                                    }
                                })(pay.method);
                                $('#invoice-payments').append(`
                                    <tr>
                                        <td>${methodLabel}</td>
                                        <td>${Number(pay.amount).toFixed(2)}</td>
                                        <td>${pay.reference ?? '-'}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            // إذا لم توجد سجلات دفعات، نعرض صف استناداً إلى بيانات الفاتورة نفسها
                            const fallbackLabel = typeMap[invoiceType] ?? invoiceType;
                            if (parseFloat(invoice.paid_amount) > 0) {
                                $('#invoice-payments').html(`
                                    <tr>
                                        <td>${fallbackLabel}</td>
                                        <td>${Number(invoice.paid_amount).toFixed(2)}</td>
                                        <td>-</td>
                                    </tr>
                                `);
                            } else {
                                $('#invoice-payments').html('<tr><td colspan="3" class="text-center">لا توجد دفعات</td></tr>');
                            }
                        }

                        // Set invoice summary
                        try {
                            const subtotal = Number(invoice.subtotal || 0);
                            const discountPercentage = Number(invoice.discount_percentage || 0);
                            const discountValue = Number(invoice.discount_value || 0);
                            const total = Number(invoice.total || 0);
                            const paidAmount = Number(invoice.paid_amount || 0);
                            // Handle different remaining field names
                            const remainingAmount = invoice.remaining_amount !== undefined ? Number(invoice.remaining_amount) : Number(invoice.remaining || 0);
                            const profit = Number(invoice.profit || 0);
                            
                            $('#modal-subtotal').text(`${subtotal.toFixed(2)}`);
                            
                            let discountText = '';
                            if (discountPercentage > 0) {
                                discountText = `${discountPercentage}% (${discountValue.toFixed(2)})`;
                            } else if (discountValue > 0) {
                                discountText = `${discountValue.toFixed(2)}`;
                            } else {
                                discountText = '0.00';
                            }
                            $('#modal-discount').text(discountText);
                            
                            $('#modal-total').text(`${total.toFixed(2)}`);
                            $('#modal-paid').text(`${paidAmount.toFixed(2)}`);
                            $('#modal-remaining').text(`${remainingAmount.toFixed(2)}`);
                            $('#modal-profit').text(`${profit.toFixed(2)}`);
                        } catch (err) {
                            console.error('Error processing invoice summary:', err);
                            alert('حدث خطأ في عرض ملخص الفاتورة');
                        }
                        
                        // Hide loading, show content
                        $('#invoice-loading').hide();
                        $('#invoice-content').show();
                    } else {
                        alert('حدث خطأ أثناء تحميل بيانات الفاتورة');
                        console.error('Error response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    alert('حدث خطأ في الاتصال بالخادم');
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Status:', status);
                    $('#invoiceDetailsModal').modal('hide');
                }
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Pagination Styling */
    .pagination-nav .pagination {
        margin-bottom: 0;
    }
    
    .pagination-nav .page-link {
        color: #495057;
        background-color: #fff;
        border: 1px solid #dee2e6;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        margin: 0 2px;
        transition: all 0.2s ease;
    }
    
    .pagination-nav .page-link:hover {
        color: #0056b3;
        background-color: #e9ecef;
        border-color: #dee2e6;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .pagination-nav .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
        box-shadow: 0 2px 4px rgba(0,123,255,0.25);
    }
    
    .pagination-nav .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
        opacity: 0.5;
    }
    
    .pagination-nav .page-item:first-child .page-link {
        border-radius: 0.375rem;
    }
    
    .pagination-nav .page-item:last-child .page-link {
        border-radius: 0.375rem;
    }
    
    .pagination-info {
        font-weight: 500;
        color: #495057;
    }
    
    /* Responsive Pagination */
    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .pagination-nav .pagination {
            justify-content: center;
        }
        
        .pagination-nav .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin: 0 1px;
        }
    }
    
    @media print {
        .navbar, .sidebar, form, .btn, .pagination, footer, .pagination-nav, .pagination-info {
            display: none !important;
        }
        
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        
        .table-dark th {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 2px solid #dee2e6 !important;
        }
        
        .badge {
            border: 1px solid #000 !important;
            color: #000 !important;
            background-color: transparent !important;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .container-fluid {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
@endpush 