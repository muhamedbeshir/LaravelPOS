@props(['currentShift'])

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Auth;
    
    // Define menu sections for active route detection
    $menuSections = [
        'pos' => ['sales.index', 'sales.pos.page'],
        'products' => ['products.*', 'categories.*', 'units.*', 'price-types.*'],
        // Inventory main pages (exclude inventory.stock-report & inventory.report to avoid double highlighting)
        'inventory' => ['inventory.index', 'inventory.adjustment', 'purchases.*', 'purchase-returns.*', 'suppliers.*'],
        'sales_delivery' => ['customers.*', 'shifts.*', 'sales-returns.*', 'loyalty.*', 'delivery-transactions.*', 'promotions.*'],
        'employees' => ['employees.*', 'job-titles.*', 'employee-advances.*', 'employees.advances'],
        'accounts' => ['expenses.*', 'deposits.*', 'expense-categories.*', 'deposit-sources.*'],
        'reports' => ['reports.*', 'inventory.report', 'inventory.stock-report'],
        'settings' => ['settings.*', 'users.*', 'roles.*', 'backups.*']
    ];

    // Helper function to check if current route matches any pattern
    function isActiveRoute($patterns) {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }
        return false;
    }
@endphp

<style>
.active-indicator {
    color: #fbbf24;
    font-weight: bold;
    text-shadow: 0 0 3px rgba(251, 191, 36, 0.5);
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.nav-link.active {
    background-color: rgba(255, 255, 255, 0.15) !important;
    border-radius: 0.5rem;
    color: #fff !important;
}

.dropdown-item.active {
    background-color: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    font-weight: 600;
}

.dropdown-item.active i {
    color: #3b82f6;
}

.dropdown-header {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.5rem 1rem 0.25rem;
}

.compact-menu {
    max-height: 70vh;
    overflow-y: auto;
}

.compact-menu .dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.compact-menu .dropdown-item:hover {
    background-color: rgba(59, 130, 246, 0.1);
    transform: translateX(-2px);
}

.navbar-buttons-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-buttons-group .btn {
    border-radius: 0.5rem;
    font-weight: 600;
}

@media (max-width: 991.98px) {
    .navbar-buttons-group {
        margin-top: 1rem;
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .navbar-buttons-group .btn {
        flex: 1;
        min-width: 120px;
    }
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="{{ asset('bulldozer-favicon.svg') }}" alt="البلدوزر" width="28" height="28" class="me-2">
            <span>البلدوزر</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav me-auto py-1 flex-wrap">

                {{-- 1. البيع (POS) - Core Sales Function --}}
                <li class="nav-item">
                    @can('pos')
                    <a id="sales-nav-link" class="nav-link d-flex align-items-center justify-content-center {{ request()->routeIs('sales.index') || request()->routeIs('sales.pos.page') ? 'active' : '' }}" href="#" onclick="checkShiftBeforeSales(event); return false;">
                        <i class="fas fa-cash-register me-1"></i> 
                        <span>الكاشير</span>
                    </a>
                    @endcan
                </li>

                {{-- Divider for Mobile --}}
                <li class="nav-divider d-lg-none my-2"><div class="border-bottom border-light opacity-25"></div></li>

                {{-- 2. المنتجات - Products Management --}}
                @canany(['view-products', 'view-categories', 'view-units', 'view-price-types'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['products']) ? 'active' : '' }}" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-box me-1"></i> 
                        <span>المنتجات</span>
                        @if(isActiveRoute($menuSections['products']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="productsDropdown">
                        @can('view-products')
                        <li><a class="dropdown-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                            <i class="fas fa-box text-warning"></i> المنتجات
                        </a></li>
                        @endcan
                        @can('view-categories')
                        <li><a class="dropdown-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                            <i class="fas fa-layer-group text-info"></i> المجموعات
                        </a></li>
                        @endcan
                        @can('view-units')
                        <li><a class="dropdown-item {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                            <i class="fas fa-balance-scale text-secondary"></i> الوحدات
                        </a></li>
                        @endcan
                        @can('view-price-types')
                        <li><a class="dropdown-item {{ request()->routeIs('price-types.*') ? 'active' : '' }}" href="{{ route('price-types.index') }}">
                            <i class="fas fa-tags text-danger"></i> أنواع الأسعار
                        </a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- 3. المخزون - Inventory Management --}}
                @canany(['view-inventory', 'view-purchases', 'view-suppliers'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['inventory']) ? 'active' : '' }}" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-warehouse me-1"></i> 
                        <span>المخزون</span>
                        @if(isActiveRoute($menuSections['inventory']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="inventoryDropdown">
                        @can('view-inventory')
                        <li><a class="dropdown-item {{ request()->routeIs('inventory.index') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                            <i class="fas fa-clipboard-list text-primary"></i> نظرة عامة
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('inventory.adjustment') ? 'active' : '' }}" href="{{ route('inventory.adjustment') }}">
                            <i class="fas fa-edit text-warning"></i> تعديل المخزون
                        </a></li>
                        @endcan
                        @can('view-purchases')
                        <li><a class="dropdown-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                            <i class="fas fa-shopping-cart text-success"></i> المشتريات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}">
                            <i class="fas fa-undo-alt text-danger"></i> مرتجع المشتريات
                        </a></li>
                        @endcan
                        @can('view-suppliers')
                        <li><a class="dropdown-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                            <i class="fas fa-truck text-info"></i> الموردين
                        </a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- 4. العمليات - Sales & Delivery --}}
                @canany(['view-sales', 'view-customers', 'view-shifts'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['sales_delivery']) ? 'active' : '' }}" href="#" id="salesDeliveryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-store me-1"></i> 
                        <span>العمليات</span>
                        @if(isActiveRoute($menuSections['sales_delivery']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="salesDeliveryDropdown">
                        {{-- Sales Operations --}}
                        <li class="dropdown-header">المبيعات</li>
                        @can('view-customers')
                        <li><a class="dropdown-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                            <i class="fas fa-users text-primary"></i> العملاء
                        </a></li>
                        @endcan
                        @can('view-promotions')
                        <li><a class="dropdown-item {{ request()->routeIs('promotions.*') ? 'active' : '' }}" href="{{ route('promotions.index') }}">
                            <i class="fas fa-tags text-success"></i> العروض والخصومات
                        </a></li>
                        @endcan
                        @can('view-shifts')
                        <li><a class="dropdown-item {{ request()->routeIs('shifts.*') ? 'active' : '' }}" href="{{ route('shifts.index') }}">
                            <i class="fas fa-clock text-success"></i> الورديات
                        </a></li>
                        @endcan
                        <li><a class="dropdown-item {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" href="{{ route('sales-returns.index') }}">
                            <i class="fas fa-undo text-danger"></i> المرتجع
                        </a></li>
                        
                        {{-- Loyalty Points --}}
                        @canany(['view-loyalty-points', 'manage-loyalty-settings'])
                        <li><a class="dropdown-item {{ request()->routeIs('loyalty.*') ? 'active' : '' }}" href="{{ route('loyalty.dashboard') }}">
                            <i class="fas fa-star text-warning"></i> نقاط الولاء
                        </a></li>
                        @endcanany
                        
                        {{-- Delivery Operations --}}
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header">التوصيل</li>
                        <li><a class="dropdown-item {{ request()->routeIs('delivery-transactions.index') ? 'active' : '' }}" href="{{ route('delivery-transactions.index') }}">
                            <i class="fas fa-list-alt text-secondary"></i> كل الطلبات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('delivery-transactions.incomplete') ? 'active' : '' }}" href="{{ route('delivery-transactions.incomplete') }}">
                            <i class="fas fa-hourglass-half text-warning"></i> قيد التنفيذ
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('delivery-transactions.current-shift') ? 'active' : '' }}" href="{{ route('delivery-transactions.current-shift') }}">
                            <i class="fas fa-motorcycle text-info"></i> الوردية الحالية
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('shipping-companies.*') ? 'active' : '' }}" href="{{ route('shipping-companies.index') }}">
                            <i class="fas fa-shipping-fast text-primary"></i> شركات الشحن
                        </a></li>
                    </ul>
                </li>
                @endcanany

                {{-- Divider for Mobile --}}
                <li class="nav-divider d-lg-none my-2"><div class="border-bottom border-light opacity-25"></div></li>
                
                {{-- 5. الموظفين - HR Management --}}
                @canany(['view-employees', 'view-job-titles'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['employees']) ? 'active' : '' }}" href="#" id="employeesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i> 
                        <span>الموظفين</span>
                        @if(isActiveRoute($menuSections['employees']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="employeesDropdown">
                        @can('view-employees')
                        <li><a class="dropdown-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                            <i class="fas fa-user-tie text-primary"></i> الموظفين
                        </a></li>
                        @endcan
                        @can('view-employee-advances')
                        <li><a class="dropdown-item {{ request()->routeIs('employee-advances.*') ? 'active' : '' }}" href="{{ route('employee-advances.index') }}">
                            <i class="fas fa-hand-holding-usd text-warning"></i> السلف
                        </a></li>
                        @endcan
                        @can('view-job-titles')
                        <li><a class="dropdown-item {{ request()->routeIs('job-titles.*') ? 'active' : '' }}" href="{{ route('job-titles.index') }}">
                            <i class="fas fa-id-badge text-info"></i> الوظائف
                        </a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- 6. الحسابات - Financial Management --}}
                @canany(['view-expenses', 'view-deposits', 'view-expense-categories', 'view-deposit-sources'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['accounts']) ? 'active' : '' }}" href="#" id="accountsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calculator me-1"></i> 
                        <span>الحسابات</span>
                        @if(isActiveRoute($menuSections['accounts']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="accountsDropdown">
                        @can('view-expenses')
                        <li><a class="dropdown-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
                            <i class="fas fa-credit-card text-danger"></i> المصروفات
                        </a></li>
                        @endcan
                        
                        @can('view-expense-categories')
                        <li><a class="dropdown-item {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}" href="{{ route('expense-categories.index') }}">
                            <i class="fas fa-folder text-warning"></i> فئات المصروفات
                        </a></li>
                        @endcan

                        @can('view-deposits')
                        <li><a class="dropdown-item {{ request()->routeIs('deposits.*') ? 'active' : '' }}" href="{{ route('deposits.index') }}">
                            <i class="fas fa-piggy-bank text-success"></i> الإيداعات
                        </a></li>
                        @endcan
                        
                        @can('view-deposit-sources')
                        <li><a class="dropdown-item {{ request()->routeIs('deposit-sources.*') ? 'active' : '' }}" href="{{ route('deposit-sources.index') }}">
                            <i class="fas fa-handshake text-info"></i> مصادر الإيداعات
                        </a></li>
                        @endcan

                    </ul>
                </li>
                @endcanany

                {{-- 7. التقارير - Reports & Analytics --}}
                @canany(['view-sales-report', 'view-purchases-report', 'view-customers-report', 'view-inventory-report', 'view-shifts-report', 'view-employees-report', 'export-products', 'export-employees'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['reports']) ? 'active' : '' }}" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-line me-1"></i> 
                        <span>التقارير</span>
                        @if(isActiveRoute($menuSections['reports']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="reportsDropdown">
                        {{-- Financial Reports --}}
                        @can('view-sales-report')
                        <li class="dropdown-header">التقارير المالية</li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.financial-summary') ? 'active' : '' }}" href="{{ route('reports.financial-summary') }}">
                            <i class="fas fa-chart-pie text-success"></i> التقرير المالي الشامل
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.all-invoices') ? 'active' : '' }}" href="{{ route('reports.all-invoices') }}">
                            <i class="fas fa-receipt text-primary"></i> تقرير المبيعات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.sales-returns.report') ? 'active' : '' }}" href="{{ route('reports.sales-returns.report') }}">
                            <i class="fas fa-undo-alt text-danger"></i> تقرير مرتجعات المبيعات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.product-sales') ? 'active' : '' }}" href="{{ route('reports.product-sales') }}">
                            <i class="fas fa-box-open text-primary"></i> تقرير مبيعات المنتجات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.sales-analysis') ? 'active' : '' }}" href="{{ route('reports.sales-analysis') }}">
                            <i class="fas fa-chart-bar text-info"></i> تحليل المبيعات
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.visa-sales') ? 'active' : '' }}" href="{{ route('reports.visa-sales') }}">
                            <i class="fab fa-cc-visa text-primary"></i> مبيعات فيزا
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.transfer-sales') ? 'active' : '' }}" href="{{ route('reports.transfer-sales') }}">
                            <i class="fas fa-exchange-alt text-warning"></i> التحويلات المالية
                        </a></li>
                        @endcan
                        
                        {{-- Inventory Reports --}}
                        @can('view-inventory-report')
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header">تقارير المخزون</li>
                        <li><a class="dropdown-item {{ request()->routeIs('reports.inventory-summary') ? 'active' : '' }}" href="{{ route('reports.inventory-summary') }}">
                            <i class="fas fa-boxes text-primary"></i> إجمالي المخزون
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('inventory.stock-report') ? 'active' : '' }}" href="{{ route('inventory.stock-report') }}">
                            <i class="fas fa-clipboard-list text-info"></i> المخزون التفصيلي
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('inventory.report') ? 'active' : '' }}" href="{{ route('inventory.report') }}">
                            <i class="fas fa-arrows-alt text-warning"></i> حركات المخزون
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}">
                            <i class="fas fa-undo-alt text-danger"></i> مرتجع المشتريات
                        </a></li>
                        @endcan
                        
                    
                        {{-- Other Reports --}}
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header">تقارير أخرى</li>
                        @can('view-shifts-report')
                        <li><a class="dropdown-item {{ request()->routeIs('reports.shifts.*') ? 'active' : '' }}" href="{{ route('reports.shifts.index') }}">
                            <i class="fas fa-business-time text-secondary"></i> تقارير الورديات
                        </a></li>
                        @endcan
                        @can('view-customers')
                        <li><a class="dropdown-item {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                            <i class="fas fa-user-friends text-primary"></i> تقرير العملاء
                        </a></li>
                        @endcan
                        @can('view-employees-report')
                        <li><a class="dropdown-item {{ request()->routeIs('employees.reports') ? 'active' : '' }}" href="{{ route('employees.reports') }}">
                            <i class="fas fa-users-cog text-info"></i> تقارير الموظفين
                        </a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Divider for Mobile --}}
                <li class="nav-divider d-lg-none my-2"><div class="border-bottom border-light opacity-25"></div></li>

                {{-- 8. الإعدادات - System Settings --}}
                @canany(['view-settings', 'manage-users', 'view-roles', 'view-backups'])
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-center {{ isActiveRoute($menuSections['settings']) ? 'active' : '' }}" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i> 
                        <span>الإعدادات</span>
                        @if(isActiveRoute($menuSections['settings']))
                            <span class="active-indicator ms-1">•</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu compact-menu" aria-labelledby="settingsDropdown">
                        @can('view-settings')
                        <li><a class="dropdown-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                            <i class="fas fa-sliders-h text-primary"></i> الإعدادات العامة
                        </a></li>
                        @endcan
                        @can('manage-users')
                        <li><a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fas fa-users-cog text-info"></i> إدارة المستخدمين
                        </a></li>
                        @endcan
                        @can('view-roles')
                        <li><a class="dropdown-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="fas fa-shield-alt text-warning"></i> الصلاحيات
                        </a></li>
                        @endcan
                        @can('manage-backups')
                        <li><a class="dropdown-item {{ request()->routeIs('backups.*') || request()->is('backups') ? 'active' : '' }}" href="{{ route('backups.index') }}">
                            <i class="fas fa-database text-success"></i> النسخ الاحتياطي
                        </a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany
            </ul>
            
            {{-- Navbar End Buttons & User Dropdown --}}
            <div class="navbar-buttons-group">
                <!-- Shift Buttons -->
                @if($currentShift && $currentShift->is_closed === false)
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#closeShiftNavbarModal">
                        <i class="fas fa-lock me-1"></i> <span class="d-none d-lg-inline">إغلاق الوردية</span>
                    </button>
                @else
                    @can('create-shifts')
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#openShiftRequiredModal">
                        <i class="fas fa-unlock me-1"></i> <span class="d-none d-lg-inline">فتح وردية</span>
                    </button>
                    @endcan
                @endif


                <!-- User Dropdown -->
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center px-2" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                        <span class="ms-1 user-name">{{ Auth::user()->name ?? 'الحساب' }}</span> 
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdown">
                        <li>
                            <div class="px-3 py-2 mb-1 text-center border-bottom">
                                <strong class="d-block">{{ Auth::user()->name ?? 'المستخدم' }}</strong>
                                <small class="d-block text-muted">{{ Auth::user()->email ?? '' }}</small>
                            </div>
                        </li>
                        <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-cog"></i> الملف الشخصي</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal"><i class="fas fa-info-circle text-info"></i> عن النظام</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- Shift Status -->
                        @if($currentShift && $currentShift->is_closed === false)
                            <li class="shift-status-item active-shift">
                                <i class="fas fa-check-circle"></i> <span>وردية مفتوحة (#{{ $currentShift->shift_number }})</span>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" onclick="closeShift(); return false;">
                                    <i class="fas fa-lock"></i> إغلاق الوردية الحالية
                                </a>
                            </li>
                        @elseif($currentShift && $currentShift->is_closed === true)
                            <li class="shift-status-item text-muted">
                                 <i class="fas fa-history"></i> <span>آخر وردية مغلقة</span>
                            </li>
                        @else
                            <li class="shift-status-item no-shift">
                                @can('create-shifts')
                                <a class="dropdown-item text-success" href="#" data-bs-toggle="modal" data-bs-target="#openShiftRequiredModal">
                                    <i class="fas fa-unlock"></i> فتح وردية جديدة
                                </a>
                                @else
                                <span class="text-muted"><i class="fas fa-times-circle"></i> لا توجد وردية مفتوحة</span>
                                @endcan
                            </li>
                        @endif
                        <!-- Logout -->
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div> <!-- End Buttons and User Dropdown Group -->
        </div> <!-- End Collapse -->
    </div> <!-- End Container -->
</nav>

<script>
    function closeShift() {
        @if($currentShift)
            fetch('{{ route('shifts.close', $currentShift->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '{{ route('shifts.show', $currentShift->id) }}';
                } else {
                    alert(data.message || 'حدث خطأ أثناء إغلاق الوردية');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إغلاق الوردية');
            });
        @else
            alert('لا توجد وردية نشطة لإغلاقها');
        @endif
    }
</script> 