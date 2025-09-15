@extends('layouts.app')

@section('title', 'تحليل المبيعات الشامل')

@section('content')
<div class="container-fluid py-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>تحليل المبيعات الشامل</h5>
                      
                    </div>
                </div>
                
                <div class="card-body py-2">
                    <!-- Filter section -->
                    <form method="GET" action="{{ route('reports.sales-analysis') }}" id="sales-report-form" class="mb-3">
                        @csrf
                        <input type="hidden" name="generate_report" value="1">
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية البيانات</h5>
                            </div>
                            <div class="card-body py-3 bg-light">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">نوع التقرير</label>
                                        <select class="form-select" name="report_type" id="report-type">
                                            <option value="period" {{ request('report_type', 'period') == 'period' ? 'selected' : '' }}>تقرير فترة زمنية</option>
                                            <option value="day" {{ request('report_type') == 'day' ? 'selected' : '' }}>تقرير يوم محدد</option>
                                            <option value="comparison" {{ request('report_type') == 'comparison' ? 'selected' : '' }}>تقرير مقارنة بين فترتين</option>
                                        </select>
                                    </div>

                                    <!-- Period Report Fields - Start -->
                                    <div class="col-md-4 col-sm-6 period-field">
                                        <label class="form-label fw-bold">من تاريخ</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date', date('Y-01-01')) }}">
                                    </div>
                                    <div class="col-md-4 col-sm-6 period-field">
                                        <label class="form-label fw-bold">إلى تاريخ</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4 col-sm-6 period-field">
                                        <label class="form-label fw-bold">تجميع حسب</label>
                                        <select class="form-select" name="group_by">
                                            <option value="daily" {{ request('group_by', 'daily') == 'daily' ? 'selected' : '' }}>يومي</option>
                                            <option value="weekly" {{ request('group_by') == 'weekly' ? 'selected' : '' }}>أسبوعي</option>
                                            <option value="monthly" {{ request('group_by') == 'monthly' ? 'selected' : '' }}>شهري</option>
                                        </select>
                                    </div>
                                    <!-- Period Report Fields - End -->

                                    <!-- Day Report Fields - Start -->
                                    <div class="col-md-4 col-sm-6 day-field d-none">
                                        <label class="form-label fw-bold">اختر اليوم</label>
                                        <input type="date" class="form-control" name="specific_day" value="{{ request('specific_day', date('Y-m-d')) }}">
                                    </div>
                                    <!-- Day Report Fields - End -->

                                    <!-- Comparison Report Fields - Start -->
                                    <div class="col-md-3 col-sm-6 comparison-field d-none">
                                        <label class="form-label fw-bold">الفترة الأولى: من</label>
                                        <input type="date" class="form-control" name="first_period_start" value="{{ request('first_period_start', date('Y-m-d', strtotime('-1 month'))) }}">
                                    </div>
                                    <div class="col-md-3 col-sm-6 comparison-field d-none">
                                        <label class="form-label fw-bold">الفترة الأولى: إلى</label>
                                        <input type="date" class="form-control" name="first_period_end" value="{{ request('first_period_end', date('Y-m-d', strtotime('-15 days'))) }}">
                                    </div>
                                    <div class="col-md-3 col-sm-6 comparison-field d-none">
                                        <label class="form-label fw-bold">الفترة الثانية: من</label>
                                        <input type="date" class="form-control" name="second_period_start" value="{{ request('second_period_start', date('Y-m-d', strtotime('-14 days'))) }}">
                                    </div>
                                    <div class="col-md-3 col-sm-6 comparison-field d-none">
                                        <label class="form-label fw-bold">الفترة الثانية: إلى</label>
                                        <input type="date" class="form-control" name="second_period_end" value="{{ request('second_period_end', date('Y-m-d')) }}">
                                    </div>
                                    <!-- Comparison Report Fields - End -->

                                    <!-- Common Filter Fields - Start -->
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">العميل</label>
                                        <select class="form-select" name="customer_id">
                                            <option value="">كل العملاء</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">نوع الفاتورة</label>
                                        <select class="form-select" name="invoice_type">
                                            <option value="">الكل</option>
                                            <option value="cash" {{ request('invoice_type') == 'cash' ? 'selected' : '' }}>كاش</option>
                                            <option value="credit" {{ request('invoice_type') == 'credit' ? 'selected' : '' }}>آجل</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <label class="form-label fw-bold">ترتيب المنتجات</label>
                                        <select class="form-select" name="products_order">
                                            <option value="sales" {{ request('products_order', 'sales') == 'sales' ? 'selected' : '' }}>حسب المبيعات</option>
                                            <option value="profit" {{ request('products_order') == 'profit' ? 'selected' : '' }}>حسب الربح</option>
                                            <option value="quantity" {{ request('products_order') == 'quantity' ? 'selected' : '' }}>حسب الكمية</option>
                                        </select>
                                    </div>
                                    <input type="hidden" name="products_limit" value="all">
                                    <!-- Common Filter Fields - End -->

                                    <div class="col-12 text-center mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-chart-bar me-1"></i>إنشاء التقرير
                                        </button>
                                        <a href="{{ route('reports.sales-analysis') }}" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-redo me-1"></i>إعادة تعيين
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if(isset($summary) && request()->has('generate_report'))
                    @if(\App\Models\Invoice::count() == 0)
                    <!-- No invoices in system at all -->
                    <div class="alert alert-danger text-center my-3 p-3">
                        <h4><i class="fas fa-exclamation-circle me-2"></i>لا توجد فواتير في النظام!</h4>
                        <p class="mb-0">لم يتم تسجيل أي فواتير بيع في النظام بعد. يرجى إنشاء فواتير مبيعات أولاً قبل عرض تقارير المبيعات.</p>
                        <div class="mt-3">
                            <a href="{{ route('sales.index') }}" class="btn btn-danger">إنشاء فاتورة مبيعات جديدة</a>
                        </div>
                    </div>
                    @elseif(isset($summary['invoice_count']) && $summary['invoice_count'] == 0)
                    <!-- No data for the specified criteria -->
                    <div class="alert alert-warning text-center my-3 p-3">
                        <h4><i class="fas fa-exclamation-triangle me-2"></i>لا توجد بيانات للفترة المحددة</h4>
                        <p class="mb-0">لم يتم العثور على أي فواتير مبيعات تطابق معايير البحث التي حددتها. يرجى تغيير معايير البحث أو اختيار فترة زمنية مختلفة.</p>
                    </div>
                    @else
                    <!-- Enhanced Summary Statistics with Extra KPIs -->
                    <div class="card mb-2">
                        <div class="card-header bg-gradient-primary text-white py-2">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ملخص المبيعات</h5>
                        </div>
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-primary text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">إجمالي المبيعات</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['total_sales'] ?? 0, 2) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-chart-line fa-lg text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-success text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">إجمالي الربح</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['total_profit'] ?? 0, 2) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-coins fa-lg text-success"></i>
                                                </div>
                                            </div>
                                            <p class="mb-0 small"><strong>{{ number_format($summary['profit_margin'] ?? 0, 1) }}%</strong> نسبة الربح</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-danger text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">مصروفات الرواتب</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['salary_expenses'] ?? 0, 2) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-user-tie fa-lg text-danger"></i>
                                                </div>
                                            </div>
                                            <p class="mb-0 small">
                                                <strong>
                                                    @if(isset($summary['total_profit']) && $summary['total_profit'] > 0)
                                                        {{ number_format(($summary['salary_expenses'] / $summary['total_profit']) * 100, 1) }}%
                                                    @else
                                                        0%
                                                    @endif
                                                </strong> 
                                                من إجمالي الربح
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-dark text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">صافي الربح</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['net_profit'] ?? 0, 2) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-hand-holding-usd fa-lg text-dark"></i>
                                                </div>
                                            </div>
                                            <p class="mb-0 small">
                                                <strong>
                                                    @if(isset($summary['total_sales']) && $summary['total_sales'] > 0)
                                                        {{ number_format(($summary['net_profit'] / $summary['total_sales']) * 100, 1) }}%
                                                    @else
                                                        0%
                                                    @endif
                                                </strong> 
                                                من إجمالي المبيعات
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-info text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">عدد الفواتير</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['invoice_count'] ?? 0) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-file-invoice-dollar fa-lg text-info"></i>
                                                </div>
                                            </div>
                                            <p class="mb-0 small"><strong>{{ number_format($summary['average_invoice'] ?? 0, 2) }}</strong> متوسط قيمة الفاتورة</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="card bg-gradient-warning text-white h-100 shadow-sm">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title">عدد المنتجات المباعة</h6>
                                                    <h3 class="mb-0">{{ number_format($summary['total_items'] ?? 0) }}</h3>
                                                </div>
                                                <div class="rounded-circle bg-white p-2">
                                                    <i class="fas fa-shopping-basket fa-lg text-warning"></i>
                                                </div>
                                            </div>
                                            <p class="mb-0 small">
                                                <strong>{{ isset($topProducts) && count($topProducts) > 0 ? count($topProducts) : 0 }}</strong> 
                                                منتج مختلف
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products Table Section -->
                    @if(isset($topProducts) && count($topProducts) > 0)
                    <div class="card mb-2">
                        <div class="card-header bg-gradient-primary text-white py-2">
                            <h5 class="mb-0"><i class="fas fa-star me-2"></i>المنتجات حسب الأداء ({{ count($topProducts) }})</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>اسم المنتج</th>
                                            <th>الكمية المباعة</th>
                                            <th>إجمالي المبيعات</th>
                                            <th>إجمالي الربح</th>
                                            <th>نسبة الربح</th>
                                            <th>عدد الفواتير</th>
                                            <th>متوسط الكمية</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topProducts as $index => $product)
                                        @php
                                            // Check if the product has all necessary properties
                                            // المنتج قد يكون مصفوفة أو كائن حسب كيفية معالجته في وحدة التحكم
                                            $isArray = is_array($product);
                                            
                                            // الحصول على اسم المنتج بطريقة تعمل مع المصفوفات والكائنات
                                            $productName = $isArray ? ($product['name'] ?? 'منتج غير معروف') : ($product->name ?? 'منتج غير معروف');
                                            
                                            // التأكد من وجود قيمة
                                            if (empty($productName) || $productName == 'منتج غير معروف') {
                                                // نحاول الوصول إلى اسم المنتج بطريقة أخرى
                                                if ($isArray) {
                                                    // لوج المصفوفة للتصحيح
                                                    \Log::info('Product array', ['product' => $product]);
                                                    
                                                    // نحاول الوصول إلى البيانات بطرق مختلفة
                                                    if (isset($product['product_name'])) {
                                                        $productName = $product['product_name'];
                                                    }
                                                } else {
                                                    // لوج الكائن للتصحيح
                                                    \Log::info('Product object', ['product' => json_encode($product)]);
                                                    
                                                    // نحاول الوصول إلى البيانات بطرق مختلفة
                                                    if (isset($product->product_name)) {
                                                        $productName = $product->product_name;
                                                    }
                                                }
                                            }
                                            
                                            // Translate product name if it's in English
                                            if (preg_match('/^Product\s*(\d+)$/i', $productName, $matches)) {
                                                $productName = 'منتج ' . $matches[1];
                                            }
                                            
                                            // Get properties with defaults for missing values
                                            $totalQuantity = $isArray ? ($product['total_quantity'] ?? 0) : ($product->total_quantity ?? 0);
                                            $totalSales = $isArray ? ($product['total_sales'] ?? 0) : ($product->total_sales ?? 0);
                                            $totalProfit = $isArray ? ($product['total_profit'] ?? 0) : ($product->total_profit ?? 0);
                                            $numberOfOrders = $isArray ? ($product['number_of_orders'] ?? 0) : ($product->number_of_orders ?? 0);
                                            $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                                            
                                            // Calculate average quantity per order
                                            $avgQuantityPerOrder = $numberOfOrders > 0 ? $totalQuantity / $numberOfOrders : 0;
                                            
                                            // تسجيل بيانات المنتج للتصحيح
                                            \Log::info('Product data in view', [
                                                'product_name' => $productName,
                                                'total_quantity' => $totalQuantity,
                                                'total_sales' => $totalSales,
                                                'total_profit' => $totalProfit,
                                                'number_of_orders' => $numberOfOrders
                                            ]);
                                        @endphp
                                        <tr class="{{ $index < 3 ? 'table-success' : '' }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $productName }}</strong></td>
                                            <td>{{ number_format($totalQuantity) }}</td>
                                            <td>{{ number_format($totalSales, 2) }}</td>
                                            <td>{{ number_format($totalProfit, 2) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                        <div class="progress-bar bg-{{ $profitMargin >= 50 ? 'success' : ($profitMargin >= 30 ? 'info' : 'warning') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ min(100, max(5, $profitMargin)) }}%"></div>
                                                    </div>
                                                    <span>{{ number_format($profitMargin, 1) }}%</span>
                                                </div>
                                            </td>
                                            <td>{{ number_format($numberOfOrders) }}</td>
                                            <td>{{ number_format($avgQuantityPerOrder, 1) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th colspan="2">الإجمالي</th>
                                            <th>
                                            @php
                                                // حساب إجمالي الكمية بشكل صحيح
                                                $totalQuantity = 0;
                                                
                                                if (isset($topProducts) && !empty($topProducts)) {
                                                    if (is_array($topProducts)) {
                                                        foreach ($topProducts as $prod) {
                                                            $totalQuantity += is_array($prod) 
                                                                ? ($prod['total_quantity'] ?? 0) 
                                                                : ($prod->total_quantity ?? 0);
                                                        }
                                                    } else {
                                                        foreach ($topProducts as $prod) {
                                                            $totalQuantity += $prod->total_quantity ?? 0;
                                                        }
                                                    }
                                                }
                                                
                                                // حساب إجمالي المبيعات والأرباح
                                                $totalSumSales = 0;
                                                $totalSumProfit = 0;
                                                
                                                if (isset($topProducts) && !empty($topProducts)) {
                                                    if (is_array($topProducts)) {
                                                        foreach ($topProducts as $prod) {
                                                            $totalSumSales += is_array($prod) 
                                                                ? ($prod['total_sales'] ?? 0) 
                                                                : ($prod->total_sales ?? 0);
                                                            $totalSumProfit += is_array($prod) 
                                                                ? ($prod['total_profit'] ?? 0) 
                                                                : ($prod->total_profit ?? 0);
                                                        }
                                                    } else {
                                                        foreach ($topProducts as $prod) {
                                                            $totalSumSales += $prod->total_sales ?? 0;
                                                            $totalSumProfit += $prod->total_profit ?? 0;
                                                        }
                                                    }
                                                }
                                                
                                                // حساب هامش الربح
                                                $totalProfitMargin = ($totalSumSales > 0) ? ($totalSumProfit / $totalSumSales) * 100 : 0;
                                            @endphp
                                            {{ number_format($totalQuantity) }}</th>
                                            <th>{{ number_format($totalSumSales, 2) }}</th>
                                            <th>{{ number_format($totalSumProfit, 2) }}</th>
                                            <th>{{ number_format($totalProfitMargin, 1) }}%</th>
                                            <th>-</th>
                                            <th>-</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning text-center p-3 mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span>لم يتم العثور على بيانات منتجات للفترة المحددة</span>
                    </div>
                    @endif

                    <!-- Category Performance Section -->
                    @if(isset($categories) && isset($topProducts) && count($topProducts) > 0)
                    <div class="card mb-2">
                        <div class="card-header bg-gradient-primary text-white py-2">
                            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>أداء الفئات</h5>
                        </div>
                        <div class="card-body p-0">
                            @php
                                // تسجيل البيانات الواردة للتحليل
                                \Log::info('Top products data for categories', [
                                    'is_array' => is_array($topProducts),
                                    'count' => isset($topProducts) ? (is_array($topProducts) ? count($topProducts) : $topProducts->count()) : 0,
                                    'first_item' => isset($topProducts) && !empty($topProducts) ? 
                                        (is_array($topProducts) ? json_encode($topProducts[0] ?? null) : json_encode($topProducts->first())) : 'No products'
                                ]);
                                
                                // Ensure topProducts is a collection, handle null case
                                $topProductsCollection = isset($topProducts) ? (is_array($topProducts) ? collect($topProducts) : $topProducts) : collect([]);
                                
                                // Get categories from products and calculate totals
                                $categoryData = collect([]); // Default empty collection
                                
                                try {
                                    if ($topProductsCollection->isNotEmpty()) {
                                        // تحقق من نوع البيانات للتأكد من الوصول إلى الحقل الصحيح
                                        $firstItem = $topProductsCollection->first();
                                        $categoryField = null;
                                        
                                        // تحديد اسم الحقل الذي يحتوي على اسم الفئة
                                        if (is_array($firstItem)) {
                                            if (isset($firstItem['category_name'])) {
                                                $categoryField = 'category_name';
                                            }
                                        } elseif (is_object($firstItem)) {
                                            if (isset($firstItem->category_name)) {
                                                $categoryField = 'category_name';
                                            }
                                        }
                                        
                                        // تسجيل اسم الحقل المستخدم
                                        \Log::info('Category field used', ['field' => $categoryField]);
                                        
                                        if ($categoryField) {
                                            $categoryData = $topProductsCollection
                                                ->groupBy($categoryField)
                                    ->map(function($items, $categoryName) {
                                        // Translate category name if it's in English
                                                    $translatedName = $categoryName ?: 'بدون فئة';
                                        if (preg_match('/^Category\s*(\d+)$/i', $categoryName, $matches)) {
                                            $translatedName = 'فئة ' . $matches[1];
                                        }
                                                    
                                                    // حساب إجمالي المبيعات والأرباح للفئة
                                                    $totalSales = 0;
                                                    $totalProfit = 0;
                                                    
                                                    foreach ($items as $item) {
                                                        if (is_array($item)) {
                                                            $totalSales += $item['total_sales'] ?? 0;
                                                            $totalProfit += $item['total_profit'] ?? 0;
                                                        } else {
                                                            $totalSales += $item->total_sales ?? 0;
                                                            $totalProfit += $item->total_profit ?? 0;
                                                        }
                                        }
                                        
                                        return [
                                                        'name' => $translatedName,
                                                        'total_sales' => $totalSales,
                                                        'total_profit' => $totalProfit,
                                            'products_count' => $items->count(),
                                                        'profit_margin' => $totalSales > 0 
                                                            ? ($totalProfit / $totalSales) * 100 
                                                : 0
                                        ];
                                    })
                                    ->sortByDesc('total_sales')
                                    ->values();
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Error processing category data: ' . $e->getMessage());
                                }
                                
                                $totalSales = $categoryData->sum('total_sales');
                                
                                // تسجيل بيانات الفئات النهائية
                                \Log::info('Final category data', [
                                    'count' => $categoryData->count(),
                                    'total_sales' => $totalSales,
                                    'categories' => $categoryData->pluck('name')->toArray()
                                ]);
                            @endphp
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>اسم الفئة</th>
                                            <th>عدد المنتجات</th>
                                            <th>إجمالي المبيعات</th>
                                            <th>إجمالي الربح</th>
                                            <th>نسبة الربح</th>
                                            <th>نسبة من المبيعات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categoryData as $index => $category)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $category['name'] }}</strong></td>
                                            <td>{{ $category['products_count'] }}</td>
                                            <td>{{ number_format($category['total_sales'], 2) }}</td>
                                            <td>{{ number_format($category['total_profit'], 2) }}</td>
                                            <td>{{ number_format($category['profit_margin'], 1) }}%</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" 
                                                             style="width: {{ $totalSales > 0 ? ($category['total_sales'] / $totalSales) * 100 : 0 }}%"></div>
                                                    </div>
                                                    <span>{{ number_format($totalSales > 0 ? ($category['total_sales'] / $totalSales) * 100 : 0, 1) }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th colspan="2">الإجمالي</th>
                                            <th>{{ number_format($categoryData->sum('products_count')) }}</th>
                                            <th>{{ number_format($totalSales, 2) }}</th>
                                            <th>{{ number_format($categoryData->sum('total_profit'), 2) }}</th>
                                            <th>{{ number_format($categoryData->sum('total_profit') / ($totalSales ?: 1) * 100, 1) }}%</th>
                                            <th>100%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Daily Sales Details -->
                    @if(request('group_by', 'daily') == 'daily' && request('report_type', 'period') == 'period' && isset($salesByDay) && count($salesByDay) > 0)
                    <div class="card mb-2">
                        <div class="card-header bg-gradient-primary text-white py-2">
                            <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>تفاصيل المبيعات اليومية</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>اليوم</th>
                                            <th>عدد الفواتير</th>
                                            <th>إجمالي المبيعات</th>
                                            <th>إجمالي الربح</th>
                                            <th>نسبة الربح</th>
                                            <th>متوسط الفاتورة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($salesByDay as $day)
                                        <tr>
                                            <td>{{ $day->date ?? ($day->month_name ?? ($day->week_label ?? $day->hour_label ?? '-')) }}</td>
                                            <td>{{ number_format($day->total_orders) }}</td>
                                            <td>{{ number_format($day->total_sales, 2) }}</td>
                                            <td>{{ number_format($day->total_profit, 2) }}</td>
                                            <td>{{ number_format(($day->total_profit && $day->total_sales) ? (($day->total_profit / $day->total_sales) * 100) : 0, 1) }}%</td>
                                            <td>{{ number_format($day->average_order_value, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th>الإجمالي</th>
                                            <th>@php
                                                $totalOrders = is_array($salesByDay) ? collect($salesByDay)->sum('total_orders') : (isset($salesByDay) ? $salesByDay->sum('total_orders') : 0);
                                                $totalSales = is_array($salesByDay) ? collect($salesByDay)->sum('total_sales') : (isset($salesByDay) ? $salesByDay->sum('total_sales') : 0);
                                                $totalProfit = is_array($salesByDay) ? collect($salesByDay)->sum('total_profit') : (isset($salesByDay) ? $salesByDay->sum('total_profit') : 0);
                                                $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                                                $avgOrderValue = $totalOrders > 0 ? ($totalSales / $totalOrders) : 0;
                                            @endphp
                                            {{ number_format($totalOrders) }}</th>
                                            <th>{{ number_format($totalSales, 2) }}</th>
                                            <th>{{ number_format($totalProfit, 2) }}</th>
                                            <th>{{ number_format($profitMargin, 1) }}%</th>
                                            <th>{{ number_format($avgOrderValue, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($summary) && isset($summary['invoice_count']) && $summary['invoice_count'] > 0)
    <!-- Most Recent Invoices Section -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header bg-gradient-info text-white py-2">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>أحدث الفواتير</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>تاريخ</th>
                                    <th>العميل</th>
                                    <th>نوع الفاتورة</th>
                                    <th>عدد المنتجات</th>
                                    <th>المبلغ</th>
                                    <th>المدفوع</th>
                                    <th>الربح</th>
                                    <th class="no-print">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    try {
                                    // Get the most recent invoices based on the filter criteria
                                        $recentInvoices = \App\Models\Invoice::with(['customer', 'items.product.category'])
                                            ->where(function($query) {
                                            // Apply date filter if in period report
                                            if (request('report_type', 'period') == 'period') {
                                                $query->whereBetween('created_at', [
                                                        Carbon\Carbon::parse(request('start_date', now()->startOfYear()->format('Y-m-d')))->startOfDay(),
                                                    Carbon\Carbon::parse(request('end_date', now()->format('Y-m-d')))->endOfDay()
                                                ]);
                                            } elseif (request('report_type') == 'day') {
                                                $query->whereDate('created_at', request('specific_day', now()->format('Y-m-d')));
                                            }
                                            
                                            // Apply customer filter
                                            if (request()->filled('customer_id')) {
                                                $query->where('customer_id', request('customer_id'));
                                            }
                                            
                                            // Apply invoice type filter
                                            if (request()->filled('invoice_type')) {
                                                $query->where('type', request('invoice_type'));
                                            }
                                                
                                                // Apply status filter - use 'completed' for successful invoices
                                                $query->where('status', 'completed');
                                        })
                                        ->orderByDesc('created_at')
                                        ->limit(5)
                                        ->get();
                                    } catch (\Exception $e) {
                                        $recentInvoices = collect([]);
                                    }
                                @endphp
                                
                                @forelse ($recentInvoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->id ?? 'N/A' }}</td>
                                    <td>{{ isset($invoice->created_at) ? $invoice->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                    <td>{{ optional($invoice->customer)->name ?? 'عميل نقدي' }}</td>
                                    <td>
                                        <span class="badge bg-{{ ($invoice->type ?? '') == 'cash' ? 'success' : 'warning' }}">
                                            {{ ($invoice->type ?? '') == 'cash' ? 'كاش' : 'آجل' }}
                                        </span>
                                    </td>
                                    <td>{{ $invoice->items ? $invoice->items->sum('quantity') : 0 }}</td>
                                    <td>{{ number_format($invoice->total ?? 0, 2) }}</td>
                                    <td>{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                    <td>{{ number_format($invoice->profit ?? 0, 2) }}</td>
                                    <td class="no-print">
                                        <a href="{{ route('sales.invoices.print', $invoice->id ?? 0) }}" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('sales.invoices.print', $invoice->id ?? 0) }}" class="btn btn-sm btn-dark" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-3">
                                        <i class="fas fa-info-circle text-info me-1"></i>
                                        لا توجد فواتير حديثة لعرضها
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($recentInvoices) > 0)
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="4">الإجمالي ({{ count($recentInvoices) }} فواتير)</th>
                                    <th>{{ number_format($recentInvoices->sum(function($invoice) { return $invoice->items ? $invoice->items->sum('quantity') : 0; })) }}</th>
                                    <th>{{ number_format($recentInvoices->sum('total') ?? 0, 2) }}</th>
                                    <th>{{ number_format($recentInvoices->sum('paid_amount') ?? 0, 2) }}</th>
                                    <th>{{ number_format($recentInvoices->sum('profit') ?? 0, 2) }}</th>
                                    <th class="no-print">-</th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    @if(count($recentInvoices) >= 5)
                    <div class="text-center py-2">
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i> عرض كل الفواتير
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @else
    <!-- Initial state when no report has been generated yet -->
    <div class="alert alert-info text-center my-3 p-3">
        <i class="fas fa-chart-bar fa-2x mb-2"></i>
        <h4>مرحباً بك في تقرير تحليل المبيعات</h4>
        <p class="mb-2">هذا التقرير يساعدك على فهم أداء المبيعات في متجرك بطريقة سهلة وواضحة.</p>
        <p>يرجى تحديد الفترة الزمنية والمعايير المطلوبة من النموذج أعلاه ثم الضغط على زر "إنشاء التقرير" لعرض البيانات.</p>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle fields based on report type
        const reportType = document.getElementById('report-type');
        const periodFields = document.querySelectorAll('.period-field');
        const dayFields = document.querySelectorAll('.day-field');
        const comparisonFields = document.querySelectorAll('.comparison-field');
        
        function toggleFields() {
            const selectedValue = reportType.value;
            
            // Hide all fields first
            periodFields.forEach(field => field.classList.add('d-none'));
            dayFields.forEach(field => field.classList.add('d-none'));
            comparisonFields.forEach(field => field.classList.add('d-none'));
            
            // Show the relevant fields
            if (selectedValue === 'period') {
                periodFields.forEach(field => field.classList.remove('d-none'));
            } else if (selectedValue === 'day') {
                dayFields.forEach(field => field.classList.remove('d-none'));
            } else if (selectedValue === 'comparison') {
                comparisonFields.forEach(field => field.classList.remove('d-none'));
            }
        }
        
        // Add event listener if the element exists
        if (reportType) {
        reportType.addEventListener('change', toggleFields);
        toggleFields(); // Initialize on page load
        }

        // Print report
        const printButton = document.getElementById('print-report');
        if (printButton) {
            printButton.addEventListener('click', function() {
            // Add a temporary print-friendly class to the body
            document.body.classList.add('print-friendly');
            
            // Print the document
            window.print();
            
            // Remove the print-friendly class
            setTimeout(function() {
                document.body.classList.remove('print-friendly');
            }, 1000);
        });
        }

        // Export to Excel
        const exportButton = document.getElementById('export-excel');
        if (exportButton) {
            exportButton.addEventListener('click', function() {
            const form = document.getElementById('sales-report-form');
                if (!form) return;
                
            const formData = new FormData(form);
            formData.append('export', 'excel');
            
            // Create a new form for export
            const exportForm = document.createElement('form');
            exportForm.method = 'GET';
            exportForm.action = form.action + '/export';
            
            // Add all form data as hidden inputs
            for (const [key, value] of formData.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                exportForm.appendChild(input);
            }
            
            document.body.appendChild(exportForm);
            exportForm.submit();
            document.body.removeChild(exportForm);
        });
        }

        // Submit button confirmation
        const submitButton = document.querySelector('#sales-report-form button[type="submit"]');
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري إنشاء التقرير...';
                this.classList.add('disabled');
                
                // Allow form to submit
                return true;
            });
        }
    });
</script>

<style>
/* Better print styling */
@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 0.5rem !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .bg-gradient-primary, .bg-gradient-success, .bg-gradient-info, .bg-gradient-warning {
        color: #000 !important;
        background: #f5f5f5 !important;
    }
    
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    .table th, .table td {
        border: 1px solid #ddd !important;
    }
    
    .table-dark {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
}

/* Custom gradient backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important;
}

/* Improved card aesthetics */
.card {
    border-radius: 0.35rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 0.75rem;
}

.card-header {
    border-radius: 0.35rem 0.35rem 0 0 !important;
    padding: 0.5rem 1rem !important;
}

/* Table improvements */
.table {
    margin-bottom: 0;
}

.table td, .table th {
    padding: 0.4rem 0.6rem;
    vertical-align: middle;
    font-size: 0.9rem;
}

.table-dark th {
    background-color: #343a40 !important;
}

/* Compact progress bars */
.progress {
    height: 0.4rem !important;
}

/* General spacing improvements */
.py-2 {
    padding-top: 0.4rem !important;
    padding-bottom: 0.4rem !important;
}

.py-3 {
    padding-top: 0.6rem !important;
    padding-bottom: 0.6rem !important;
}

.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 0.75rem !important;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Force proper spacing between cards */
.card + .card {
    margin-top: 0.5rem;
}

/* Better table borders */
.table-bordered {
    border: 1px solid #dee2e6;
}

/* Total row styling */
tfoot.table-dark {
    font-weight: bold;
}

tfoot.table-dark th {
    border-top: 2px solid #fff !important;
    font-size: 1rem !important;
}

@media print {
    tfoot.table-dark th {
        background-color: #e9ecef !important;
        color: #000 !important;
        font-weight: bold !important;
        border-top: 2px solid #000 !important;
    }
}

/* Alert styles */
.alert {
    border: none !important;
    border-radius: 0.35rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.alert-info {
    background-color: #cce5ff !important;
    color: #004085 !important;
}

.alert-warning {
    background-color: #fff3cd !important;
    color: #856404 !important;
}

.alert-danger {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

/* Button enhancements */
.btn {
    border-radius: 0.25rem;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

/* Form control enhancements */
.form-control, .form-select {
    border-radius: 0.25rem;
    border-color: #d1d3e2;
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    border-color: #bac8f3;
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
}

.form-label {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}
</style>
@endsection 