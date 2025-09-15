@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
 

    <!-- عنوان الصفحة والترحيب -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow-lg border-0 rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1 fw-bold"><i class="fas fa-store-alt me-2"></i>مرحباً بك في نظام نقاط البيع</h2>
                            <p class="mb-0"><i class="far fa-calendar-alt me-1"></i> {{ now()->format('Y-m-d') }} | <i class="far fa-clock me-1"></i> {{ now()->format('h:i A') }}</p>
                        </div>
                        <div class="text-end">
                            <h4><i class="fas fa-building me-1"></i> الفرع الرئيسي</h4>
                            <button class="btn btn-light btn-sm hover-scale" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-1"></i> تحديث البيانات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- العمليات السريعة - قسم واحد مع خيارات أكثر تنوعاً  -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <h5 class="fw-bold text-dark section-title"><i class="fas fa-bolt me-2"></i>العمليات السريعة</h5>
            <hr class="mt-2 section-divider">
        </div>
        @can('pos')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a id="home-sales-link" href="#" class="text-decoration-none" onclick="checkShiftBeforeSales(event); return false;">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-primary p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-cart-plus text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">نقطة البيع</h6>
                        <p class="small text-muted mb-0">إنشاء فاتورة جديدة</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('view-products')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('products.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-success p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-boxes text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">المنتجات</h6>
                        <p class="small text-muted mb-0">إدارة المنتجات</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('view-inventory')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('inventory.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-danger p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-dolly-flatbed text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">المخزون</h6>
                        <p class="small text-muted mb-0">حركة المخزون</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('view-purchases')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('purchases.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-secondary p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-shopping-basket text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">المشتريات</h6>
                        <p class="small text-muted mb-0">فواتير الشراء</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('view-employees')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('employees.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-info p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-user-tie text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">الموظفين</h6>
                        <p class="small text-muted mb-0">إدارة الموظفين</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('view-settings')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('settings.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-warning p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-sliders-h text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">الإعدادات</h6>
                        <p class="small text-muted mb-0">ضبط النظام</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        {{-- Added Quick Operations --}}
        @can('view-categories')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('categories.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-purple p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-sitemap text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">المجموعات</h6>
                        <p class="small text-muted mb-0">إدارة الفئات</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('view-units')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('units.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-blue p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-ruler-combined text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">الوحدات</h6>
                        <p class="small text-muted mb-0">وحدات القياس</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('view-suppliers')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('suppliers.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-teal p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-industry text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">الموردين</h6>
                        <p class="small text-muted mb-0">قائمة الموردين</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('view-customers')
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('customers.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-pink p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-user-friends text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">العملاء</h6>
                        <p class="small text-muted mb-0">قاعدة العملاء</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('view-shifts') {{-- Adjust permission if needed --}}
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('shifts.index') }}" class="text-decoration-none">
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-secondary p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-cash-register text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">الورديات</h6>
                        <p class="small text-muted mb-0">إدارة الورديات</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('view-sales-report') {{-- Adjust permission if needed --}}
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <a href="{{ route('reports.all-invoices') }}" class="text-decoration-none"> {{-- Adjust route if needed --}}
                <div class="card action-card text-center h-100">
                    <div class="card-body">
                        <div class="action-icon bg-gradient-orange p-3 rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fas fa-chart-line text-white fa-2x"></i>
                        </div>
                        <h6 class="mb-1 text-dark fw-semibold">التقارير</h6>
                        <p class="small text-muted mb-0">عرض التقارير</p>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        {{-- End Added Quick Operations --}}
    </div>

    <!-- الإحصائيات السريعة -->
    <div class="row mb-4">
        @can('pos')
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-gradient-success text-white h-100 shadow-sm border-0 rounded-lg hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title fw-bold"><i class="fas fa-chart-line me-1"></i> مبيعات اليوم</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($todaySales ?? 0, 2) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <div class="stats-icon-circle bg-white bg-opacity-25">
                                <i class="fas fa-cash-register fa-2x text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a id="home-stats-sales-link" href="#" class="text-white btn-link" onclick="checkShiftBeforeSales(event); return false;"><i class="fas fa-external-link-alt me-1"></i> فتح نقطة البيع</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('view-products')
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-gradient-info text-white h-100 shadow-sm border-0 rounded-lg hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title fw-bold"><i class="fas fa-cubes me-1"></i> عدد المنتجات</h6>
                            <h3 class="mb-0 fw-bold">{{ $productsCount ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <div class="stats-icon-circle bg-white bg-opacity-25">
                                <i class="fas fa-box-open fa-2x text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('products.index') }}" class="text-white btn-link"><i class="fas fa-external-link-alt me-1"></i> إدارة المنتجات</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('view-customers')
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-gradient-warning text-white h-100 shadow-sm border-0 rounded-lg hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title fw-bold"><i class="fas fa-user-friends me-1"></i> عدد العملاء</h6>
                            <h3 class="mb-0 fw-bold">{{ $customersCount ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <div class="stats-icon-circle bg-white bg-opacity-25">
                                <i class="fas fa-users fa-2x text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('customers.index') }}" class="text-white btn-link"><i class="fas fa-external-link-alt me-1"></i> إدارة العملاء</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        @can('view-inventory')
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card bg-gradient-danger text-white h-100 shadow-sm border-0 rounded-lg hover-lift">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title fw-bold"><i class="fas fa-inventory me-1"></i> منتجات منخفضة</h6>
                            <h3 class="mb-0 fw-bold">{{ $lowStockCount ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <div class="stats-icon-circle bg-white bg-opacity-25">
                                <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('inventory.index') }}" class="text-white btn-link"><i class="fas fa-external-link-alt me-1"></i> مراجعة المخزون</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <div class="row">
        {{-- Removed Recent Sales and Low Stock Products sections --}}
    </div>
</div>

<style>
/* تحسينات أساسية */
.card {
    box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05);
    border: none;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    border-top-left-radius: 0.75rem !important;
    border-top-right-radius: 0.75rem !important;
    padding: 1rem 1.25rem;
}

/* تدرجات الخلفية */
.bg-gradient-primary {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #059669, #10b981);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #0284c7, #38bdf8);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #d97706, #fbbf24);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #4b5563, #6b7280);
}

.bg-gradient-purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.bg-gradient-teal {
    background: linear-gradient(135deg, #0d9488, #14b8a6);
}

.bg-gradient-blue {
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
}

.bg-gradient-indigo {
    background: linear-gradient(135deg, #4338ca, #6366f1);
}

.bg-gradient-orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
}

.bg-gradient-pink {
    background: linear-gradient(135deg, #db2777, #ec4899);
}

/* تأثيرات التحويم */
.hover-lift:hover {
    transform: translateY(-5px);
}

.hover-scale:hover {
    transform: scale(1.03);
}

/* بطاقات العمليات */
.action-card {
    transition: all 0.2s ease;
    border-radius: 1rem;
    overflow: hidden;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.action-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.action-card:hover .action-icon {
    transform: scale(1.1);
}

/* دائرة أيقونة الإحصائيات */
.stats-icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* عناوين الأقسام */
.section-title {
    position: relative;
    padding-right: 15px;
}

.section-divider {
    background: linear-gradient(to right, #e5e7eb 0%, transparent 100%);
    height: 2px;
    opacity: 0.7;
    border-radius: 2px;
}

/* رابط الزر */
.btn-link {
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-block;
}

.btn-link:hover {
    transform: translateX(-5px);
}

/* الألوان والخلفيات */
.text-dark {
    color: #1f2937 !important;
}

.text-muted {
    color: #6b7280 !important;
}

.table {
    color: #374151;
}

.fw-semibold {
    font-weight: 600 !important;
}

/* ألوان الأقسام الأخرى */
.text-purple {
    color: #8b5cf6 !important;
}

.text-teal {
    color: #14b8a6 !important;
}

.text-indigo {
    color: #6366f1 !important;
}

.text-blue {
    color: #3b82f6 !important;
}

.text-orange {
    color: #f97316 !important;
}

.text-pink {
    color: #ec4899 !important;
}

.bg-purple {
    background-color: #8b5cf6 !important;
}

.bg-teal {
    background-color: #14b8a6 !important;
}

.bg-indigo {
    background-color: #6366f1 !important;
}

.bg-blue {
    background-color: #3b82f6 !important;
}

.bg-orange {
    background-color: #f97316 !important;
}

.bg-pink {
    background-color: #ec4899 !important;
}

.border-purple {
    border-color: #8b5cf6 !important;
}

.border-teal {
    border-color: #14b8a6 !important;
}

.border-indigo {
    border-color: #6366f1 !important;
}

.border-blue {
    border-color: #3b82f6 !important;
}

.border-orange {
    border-color: #f97316 !important;
}

.border-pink {
    border-color: #ec4899 !important;
}

/* تاثيرات إضافية للجداول */
.table tr {
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.table tr:hover {
    border-left: 3px solid #3b82f6;
    background-color: #f9fafb;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.rounded-pill {
    border-radius: 50rem !important;
}

.btn {
    font-weight: 500;
    padding: 0.375rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>

@endsection 