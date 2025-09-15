@extends('layouts.app')

@section('title', 'تقرير مرتجعات المبيعات')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-undo-alt me-2"></i>تقرير مرتجعات المبيعات</h5>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter section -->
                    <form method="GET" action="{{ route('reports.sales-returns.report') }}" class="mb-4 p-3 border rounded bg-light no-print">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date', $startDate) }}">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ request('end_date', $endDate) }}">
                            </div>

                            <!-- Quick Range -->
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">نطاق زمني سريع</label>
                                <select class="form-select form-select-sm" id="quick-range">
                                    <option value="">اختر...</option>
                                    <option value="today">اليوم</option>
                                    <option value="yesterday">أمس</option>
                                    <option value="this_week">الأسبوع الحالي</option>
                                    <option value="last_week">الأسبوع الماضي</option>
                                    <option value="last_7">آخر 7 أيام</option>
                                    <option value="last_15">آخر 15 يوم</option>
                                    <option value="last_30">آخر 30 يوم</option>
                                    <option value="this_month">الشهر الحالي</option>
                                    <option value="last_month">الشهر الماضي</option>
                                    <option value="this_quarter">الربع الحالي</option>
                                    <option value="last_quarter">الربع الماضي</option>
                                    <option value="this_year">العام الحالي</option>
                                    <option value="last_year">العام الماضي</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">نوع الإرجاع</label>
                                <select class="form-select form-select-sm" name="return_type">
                                    <option value="">الكل</option>
                                    <option value="item" {{ request('return_type') == 'item' ? 'selected' : '' }}>إرجاع صنف</option>
                                    <option value="full_invoice" {{ request('return_type') == 'full_invoice' ? 'selected' : '' }}>إرجاع فاتورة كاملة</option>
                                    <option value="partial_invoice" {{ request('return_type') == 'partial_invoice' ? 'selected' : '' }}>إرجاع جزئي من فاتورة</option>
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
                            
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label">المستخدم</label>
                                <select class="form-select form-select-sm" name="user_id">
                                    <option value="">كل المستخدمين</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-12 col-lg-auto mt-3 mt-lg-0 text-end">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-filter me-1"></i>تصفية
                                </button>
                                <a href="{{ route('reports.sales-returns.report') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-redo me-1"></i>إعادة تعيين
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                             <div class="col-md-12 text-start">
                                <button type="button" class="btn btn-success btn-sm" id="export-excel">
                                    <i class="fas fa-file-excel me-1"></i>تصدير Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm ms-2" id="export-pdf">
                                    <i class="fas fa-file-pdf me-1"></i>تصدير PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm ms-2" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>طباعة
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Summary boxes -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-danger h-100">
                                <div class="card-body text-center">
                                    <div class="display-6 text-danger">{{ number_format($totalReturns) }}</div>
                                    <small class="text-muted">إجمالي المرتجعات</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-warning h-100">
                                <div class="card-body text-center">
                                    <div class="display-6 text-warning">{{ number_format($totalAmount, 2) }}</div>
                                    <small class="text-muted">إجمالي قيمة المرتجعات</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-info h-100">
                                <div class="card-body text-center">
                                    <div class="display-6 text-info">{{ $totalReturns > 0 ? number_format($totalAmount / $totalReturns, 2) : '0.00' }}</div>
                                    <small class="text-muted">متوسط قيمة المرتجع</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card border-success h-100">
                                <div class="card-body text-center">
                                    <div class="display-6 text-success">{{ $salesReturns->currentPage() }}</div>
                                    <small class="text-muted">الصفحة الحالية</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Return type statistics -->
                    @if($returnTypeStats->count() > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>إحصائيات حسب نوع الإرجاع:</h6>
                            <div class="row">
                                @foreach($returnTypeStats as $stat)
                                <div class="col-md-4 mb-2">
                                    <div class="card border-left-primary h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>
                                                        @switch($stat->return_type)
                                                            @case('item') إرجاع صنف @break
                                                            @case('full_invoice') إرجاع فاتورة كاملة @break
                                                            @case('partial_invoice') إرجاع جزئي من فاتورة @break
                                                            @default {{ $stat->return_type }}
                                                        @endswitch
                                                    </strong>
                                                    <br>
                                                    <small class="text-muted">العدد: {{ $stat->count }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong class="text-danger">{{ number_format($stat->total_amount, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>توزيع المرتجعات حسب النوع</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="returnTypeChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>اتجاه المرتجعات خلال الفترة</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="returnTrendChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Breakdown Section -->
                    @if($topReturnedProducts->count() > 0)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-cube me-2"></i>المنتجات الأكثر إرجاعاً</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>المنتج</th>
                                                    <th>الوحدة</th>
                                                    <th>الكمية</th>
                                                    <th>القيمة</th>
                                                    <th>عدد المرتجعات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topReturnedProducts as $product)
                                                <tr>
                                                    <td>{{ $product->product_name }}</td>
                                                    <td>{{ $product->unit_name }}</td>
                                                    <td><span class="badge bg-warning">{{ number_format($product->total_quantity) }}</span></td>
                                                    <td><strong class="text-danger">{{ number_format($product->total_amount, 2) }}</strong></td>
                                                    <td><span class="badge bg-info">{{ $product->return_count }}</span></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>العملاء الأكثر إرجاعاً</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>اسم العميل</th>
                                                    <th>الهاتف</th>
                                                    <th>عدد المرتجعات</th>
                                                    <th>إجمالي القيمة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topReturningCustomers as $customer)
                                                <tr>
                                                    <td>{{ $customer->customer_name }}</td>
                                                    <td><small class="text-muted">{{ $customer->customer_phone ?? 'غير محدد' }}</small></td>
                                                    <td><span class="badge bg-warning">{{ $customer->return_count }}</span></td>
                                                    <td><strong class="text-danger">{{ number_format($customer->total_amount, 2) }}</strong></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>ملاحظة:</strong> تفاصيل المنتجات المرتجعة غير متاحة حالياً. قد تحتاج إلى إنشاء جدول <code>return_items</code> في قاعدة البيانات أو تشغيل الترحيلات.
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم المرتجع</th>
                                    <th>تاريخ الإرجاع</th>
                                    <th>رقم الفاتورة</th>
                                    <th>تاريخ الفاتورة</th>
                                    <th>العميل</th>
                                    <th>نوع الإرجاع</th>
                                    <th>مبلغ الإرجاع</th>
                                    <th>المستخدم</th>
                                    <th>الوردية</th>
                                    <th>ملاحظات</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesReturns as $return)
                                <tr>
                                    <td><span class="badge bg-secondary">#{{ $return->id }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($return->return_date)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($return->invoice_id)
                                            <span class="badge bg-primary">#{{ $return->invoice_id }}</span>
                                        @else
                                            <span class="text-muted">لا يوجد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return->invoice_date)
                                            {{ \Carbon\Carbon::parse($return->invoice_date)->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return->customer_name)
                                            <div>{{ $return->customer_name }}</div>
                                            @if($return->customer_phone)
                                                <small class="text-muted">{{ $return->customer_phone }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @switch($return->return_type)
                                                @case('item') bg-info @break
                                                @case('full_invoice') bg-danger @break
                                                @case('partial_invoice') bg-warning text-dark @break
                                                @default bg-secondary
                                            @endswitch
                                        ">
                                            @switch($return->return_type)
                                                @case('item') إرجاع صنف @break
                                                @case('full_invoice') إرجاع فاتورة كاملة @break
                                                @case('partial_invoice') إرجاع جزئي @break
                                                @default {{ $return->return_type }}
                                            @endswitch
                                        </span>
                                    </td>
                                    <td><strong class="text-danger">{{ number_format($return->total_returned_amount, 2) }}</strong></td>
                                    <td>{{ $return->user_name ?? 'غير محدد' }}</td>
                                    <td>
                                        @if($return->shift_id)
                                            <span class="badge bg-info">#{{ $return->shift_id }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return->notes)
                                            <span class="text-truncate" style="max-width: 150px;" title="{{ $return->notes }}">{{ $return->notes }}</span>
                                        @else
                                            <span class="text-muted">لا توجد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($return->invoice_id)
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewInvoiceDetails({{ $return->invoice_id }})">
                                                <i class="fas fa-eye"></i> عرض الفاتورة
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-info" onclick="viewReturnDetails({{ $return->id }})">
                                            <i class="fas fa-info-circle"></i> تفاصيل المرتجع
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>لا توجد مرتجعات في الفترة المحددة</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($salesReturns->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $salesReturns->appends(request()->query())->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceDetailsModalLabel">تفاصيل الفاتورة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoiceDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Return Details Modal -->
<div class="modal fade" id="returnDetailsModal" tabindex="-1" aria-labelledby="returnDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnDetailsModalLabel">تفاصيل المرتجع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="returnDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing sales returns report...');
    // Quick date range selector
    const quickRangeSelect = document.getElementById('quick-range');
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');

    quickRangeSelect.addEventListener('change', function() {
        const today = new Date();
        let startDate, endDate;

        switch(this.value) {
            case 'today':
                startDate = endDate = today.toISOString().split('T')[0];
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday.toISOString().split('T')[0];
                break;
            case 'this_week':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay());
                startDate = startOfWeek.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_week':
                const lastWeekStart = new Date(today);
                lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
                const lastWeekEnd = new Date(today);
                lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
                startDate = lastWeekStart.toISOString().split('T')[0];
                endDate = lastWeekEnd.toISOString().split('T')[0];
                break;
            case 'last_7':
                const sevenDaysAgo = new Date(today);
                sevenDaysAgo.setDate(today.getDate() - 7);
                startDate = sevenDaysAgo.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_15':
                const fifteenDaysAgo = new Date(today);
                fifteenDaysAgo.setDate(today.getDate() - 15);
                startDate = fifteenDaysAgo.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_30':
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(today.getDate() - 30);
                startDate = thirtyDaysAgo.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'this_month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_month':
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                startDate = lastMonth.toISOString().split('T')[0];
                endDate = lastDayOfLastMonth.toISOString().split('T')[0];
                break;
            case 'this_quarter':
                const currentQuarter = Math.floor(today.getMonth() / 3);
                const quarterStart = new Date(today.getFullYear(), currentQuarter * 3, 1);
                startDate = quarterStart.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_quarter':
                const lastQuarter = Math.floor(today.getMonth() / 3) - 1;
                let lastQuarterYear = today.getFullYear();
                let adjustedLastQuarter = lastQuarter;
                if (lastQuarter < 0) {
                    adjustedLastQuarter = 3;
                    lastQuarterYear--;
                }
                const lastQuarterStart = new Date(lastQuarterYear, adjustedLastQuarter * 3, 1);
                const lastQuarterEnd = new Date(lastQuarterYear, (adjustedLastQuarter + 1) * 3, 0);
                startDate = lastQuarterStart.toISOString().split('T')[0];
                endDate = lastQuarterEnd.toISOString().split('T')[0];
                break;
            case 'this_year':
                startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_year':
                startDate = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                endDate = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
                break;
            default:
                return;
        }

        startDateInput.value = startDate;
        endDateInput.value = endDate;
    });

    // Export Excel functionality
    document.getElementById('export-excel').addEventListener('click', function() {
        const form = document.querySelector('form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        window.location.href = "{{ route('reports.sales-returns.export') }}?" + params.toString();
    });

    // Export PDF functionality
    document.getElementById('export-pdf').addEventListener('click', function() {
        const form = document.querySelector('form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        window.location.href = "{{ route('reports.sales-returns.export') }}?" + params.toString() + "&format=pdf";
    });

    // Initialize Charts
    initializeCharts();
    
    // Debug: Check if buttons exist
    console.log('Checking buttons...');
    const invoiceButtons = document.querySelectorAll('button[onclick*="viewInvoiceDetails"]');
    const returnButtons = document.querySelectorAll('button[onclick*="viewReturnDetails"]');
    console.log('Invoice buttons found:', invoiceButtons.length);
    console.log('Return buttons found:', returnButtons.length);
});

// Global functions for modal interactions
// View invoice details
window.viewInvoiceDetails = function(invoiceId) {
    console.log('viewInvoiceDetails called with:', invoiceId);
    const modal = new bootstrap.Modal(document.getElementById('invoiceDetailsModal'));
    const content = document.getElementById('invoiceDetailsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch invoice details from API
    fetch(`/api/invoices/${invoiceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const invoice = data.invoice;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>معلومات الفاتورة:</h6>
                            <p><strong>رقم الفاتورة:</strong> #${invoice.id}</p>
                            <p><strong>التاريخ:</strong> ${new Date(invoice.created_at).toLocaleString('ar-EG')}</p>
                            <p><strong>العميل:</strong> ${invoice.customer ? invoice.customer.name : 'غير محدد'}</p>
                            <p><strong>إجمالي الفاتورة:</strong> ${parseFloat(invoice.total).toFixed(2)}</p>
                            <p><strong>المبلغ المدفوع:</strong> ${parseFloat(invoice.paid_amount || 0).toFixed(2)}</p>
                            <p><strong>المبلغ المتبقي:</strong> ${parseFloat(invoice.remaining_amount || 0).toFixed(2)}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>تفاصيل إضافية:</h6>
                            <p><strong>نوع الدفع:</strong> ${invoice.payment_method || 'غير محدد'}</p>
                            <p><strong>نوع الطلب:</strong> ${invoice.order_type || 'غير محدد'}</p>
                            <p><strong>الحالة:</strong> ${invoice.status || 'غير محدد'}</p>
                        </div>
                    </div>
                    ${invoice.items ? `
                        <h6 class="mt-3">أصناف الفاتورة:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${invoice.items.map(item => `
                                        <tr>
                                            <td>${item.product ? item.product.name : 'غير محدد'}</td>
                                            <td>${item.quantity}</td>
                                            <td>${parseFloat(item.unit_price).toFixed(2)}</td>
                                            <td>${parseFloat(item.total_price).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : ''}
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">خطأ في تحميل بيانات الفاتورة</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">خطأ في الاتصال بالخادم</div>';
        });
}

// View return details
window.viewReturnDetails = function(returnId) {
    console.log('viewReturnDetails called with:', returnId);
    const modal = new bootstrap.Modal(document.getElementById('returnDetailsModal'));
    const content = document.getElementById('returnDetailsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch return details from API
    fetch(`/api/sales-returns/${returnId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const returnData = data.return;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>معلومات المرتجع:</h6>
                            <p><strong>رقم المرتجع:</strong> #${returnData.id}</p>
                            <p><strong>تاريخ الإرجاع:</strong> ${new Date(returnData.return_date).toLocaleString('ar-EG')}</p>
                            <p><strong>نوع الإرجاع:</strong> ${getReturnTypeText(returnData.return_type)}</p>
                            <p><strong>إجمالي المرتجع:</strong> ${parseFloat(returnData.total_returned_amount).toFixed(2)}</p>
                            <p><strong>الملاحظات:</strong> ${returnData.notes || 'لا توجد'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>معلومات إضافية:</h6>
                            <p><strong>المستخدم:</strong> ${returnData.user ? returnData.user.name : 'غير محدد'}</p>
                            <p><strong>الوردية:</strong> ${returnData.shift_id ? '#' + returnData.shift_id : 'غير محدد'}</p>
                            <p><strong>الفاتورة المرتبطة:</strong> ${returnData.invoice_id ? '#' + returnData.invoice_id : 'لا يوجد'}</p>
                        </div>
                    </div>
                    ${returnData.items ? `
                        <h6 class="mt-3">أصناف المرتجع:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الوحدة</th>
                                        <th>الكمية المرتجعة</th>
                                        <th>سعر الوحدة</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${returnData.items.map(item => `
                                        <tr>
                                            <td>${item.product ? item.product.name : 'غير محدد'}</td>
                                            <td>${item.unit ? item.unit.name : 'غير محدد'}</td>
                                            <td>${item.quantity_returned}</td>
                                            <td>${parseFloat(item.unit_price_returned).toFixed(2)}</td>
                                            <td>${parseFloat(item.sub_total_returned).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : ''}
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">خطأ في تحميل بيانات المرتجع</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">خطأ في الاتصال بالخادم</div>';
        });
}

// Global helper function
window.getReturnTypeText = function(type) {
    switch(type) {
        case 'item': return 'إرجاع صنف';
        case 'full_invoice': return 'إرجاع فاتورة كاملة';
        case 'partial_invoice': return 'إرجاع جزئي من فاتورة';
        default: return type;
    }
}

// Global chart functions
window.initializeCharts = function() {
    // Return Type Distribution Chart
    const returnTypeData = @json($returnTypeStats);
    const typeLabels = returnTypeData.map(item => getReturnTypeText(item.return_type));
    const typeCounts = returnTypeData.map(item => item.count);
    const typeAmounts = returnTypeData.map(item => parseFloat(item.total_amount));

    if (returnTypeData.length > 0) {
        const ctx1 = document.getElementById('returnTypeChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeCounts,
                    backgroundColor: [
                        '#007bff',
                        '#dc3545',
                        '#ffc107',
                        '#28a745',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const count = context.parsed;
                                const amount = typeAmounts[context.dataIndex];
                                return `${label}: ${count} (${amount.toFixed(2)} ج.م)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Return Trend Chart - Get daily returns data
    initializeReturnTrendChart();
}

// Global trend chart function
window.initializeReturnTrendChart = function() {
    // This would ideally get data from the controller, but for now we'll create a simple version
    const ctx2 = document.getElementById('returnTrendChart').getContext('2d');
    
    // For now, we'll show a placeholder chart
    // In a real implementation, you'd pass daily/weekly aggregated data from the controller
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: ['المجموع'],
            datasets: [{
                label: 'عدد المرتجعات',
                data: [{{ $totalReturns }}],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'قيمة المرتجعات',
                data: [{{ $totalAmount }}],
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'الفترة الزمنية'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'عدد المرتجعات'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'قيمة المرتجعات (ج.م)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 1) {
                                label += context.parsed.y.toFixed(2) + ' ج.م';
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}
</script>
@endsection

@section('styles')
<style>
.border-left-primary {
    border-left: 4px solid #007bff;
}

.table th {
    white-space: nowrap;
}

.text-truncate {
    display: inline-block;
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge {
    font-size: 0.8em;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .table {
        font-size: 11px;
    }
    
    .badge {
        border: 1px solid #333;
        color: #000 !important;
        background-color: transparent !important;
    }
    
    /* Hide charts for printing as they don't print well */
    canvas {
        display: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
}
</style>
@endsection