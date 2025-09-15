<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\SuspendedSaleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BulkProductController;
use App\Http\Controllers\ShippingCompanyController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShiftReportController;
use App\Http\Controllers\PriceTypeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\DepositSourceController;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\SalesReturnPageController;
use App\Http\Controllers\DeliveryTransactionController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\EmployeeAdvanceController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\PurchaseReturnController;


// مسارات المصادقة
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// مسارات خارج المصادقة
// إضافة مسار جديد للحصول على رقم الفاتورة الحالية في الوردية كمسار عام
Route::get('/sales/current-invoice-number', [SalesController::class, 'getCurrentShiftInvoiceNumber'])->name('sales.current-invoice-number');
Route::get('/sales/current-shift-invoices', [SalesController::class, 'getCurrentShiftInvoices'])->name('sales.current-shift-invoices');

// API route for getting price type by ID (kept for potential future use)
Route::get('/api/price-types/{id}', [PriceTypeController::class, 'show'])->name('api.price-types.show');

// تطبيق middleware auth على الصفحة الرئيسية وجميع المسارات الأخرى
Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/البيع', [SalesController::class, 'pos'])->name('pos');
    
    // مسارات المستخدمين
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // مسارات الملف الشخصي
    Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
    Route::patch('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    
    // مسارات إدارة الأدوار والصلاحيات
    Route::middleware(['auth'])->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/roles', 'index')->name('roles.index');
            Route::get('/roles/create', 'create')->name('roles.create');
            Route::post('/roles', 'store')->name('roles.store');
            Route::get('/roles/{role}', 'show')->name('roles.show');
            Route::get('/roles/{role}/edit', 'edit')->name('roles.edit');
            Route::put('/roles/{role}', 'update')->name('roles.update');
            Route::delete('/roles/{role}', 'destroy')->name('roles.destroy');
        });
    });
    
    Route::resource('units', UnitController::class);
    Route::post('units/{unit}/toggle-active', [UnitController::class, 'toggleActive'])->name('units.toggle-active');
    Route::get('units-export', [UnitController::class, 'export'])->name('units.export');

    Route::resource('categories', CategoryController::class);
    Route::post('categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])->name('categories.toggle-active');

    Route::get('/products-export', [ProductController::class, 'export'])->name('products.export');
    Route::post('products-import', [ProductController::class, 'import'])->name('products.import');
    Route::get('products/print-labels', [ProductController::class, 'printLabels'])->name('products.print-labels');

    Route::get('/products/print-all-barcodes', [ProductController::class, 'printAllBarcodes'])->name('products.print-all-barcodes');
    Route::get('/products/price-analytics', [ProductController::class, 'priceAnalytics'])->name('products.price-analytics');
    Route::get('/products/get-price-analytics-data', [ProductController::class, 'getPriceAnalyticsData'])->name('products.get-price-analytics-data');

    Route::get('/products/{product}/price-history', [ProductController::class, 'priceHistory'])->name('products.price-history');
    Route::get('/products/{product}/edit-prices', [ProductController::class, 'editPrices'])->name('products.edit-prices');
    Route::put('/products/{product}/update-prices', [ProductController::class, 'updatePrices'])->name('products.update-prices');
    Route::get('/products-bulk-edit-prices', [ProductController::class, 'bulkEditPrices'])->name('products.bulk-edit-prices');
    
    // Fix the route to accept both POST and PUT methods for compatibility
    Route::match(['post', 'put'], '/products-bulk-update-prices', [ProductController::class, 'bulkUpdatePrices'])->name('products.bulk-update-prices');

    Route::resource('products', ProductController::class);
    Route::post('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
    Route::get('products/{product}/print-barcode', [ProductController::class, 'printBarcode'])->name('products.print-barcode');

    // New bulk barcode printing routes
    Route::get('/bulk-barcode-print', [App\Http\Controllers\BulkBarcodeController::class, 'index'])->name('bulk-barcodes.index');
    Route::get('/bulk-barcode-print/products', [App\Http\Controllers\BulkBarcodeController::class, 'getProducts'])->name('bulk-barcodes.get-products');
    Route::post('/bulk-barcode-print', [App\Http\Controllers\BulkBarcodeController::class, 'printLabels'])->name('bulk-barcodes.print');

    // مسارات إضافة المنتجات بشكل جماعي
    Route::get('products-bulk-create', [BulkProductController::class, 'create'])->name('products.bulk-create');
    Route::post('products-bulk-store', [BulkProductController::class, 'store'])->name('products.bulk-store');
    Route::get('api/products/generate-barcode', [BulkProductController::class, 'generateBarcode'])->name('api.products.generate-barcode');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
    Route::post('settings/update', [SettingController::class, 'store'])->name('settings.update');
    Route::post('settings/verify-delete-password', [SettingController::class, 'verifyDeletePassword'])->name('settings.verify-delete-password');
    Route::get('api/settings/inventory', [SettingController::class, 'getInventorySettings'])->name('api.settings.inventory');
    Route::get('/settings/inventory', [App\Http\Controllers\SettingController::class, 'inventory']);

    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/add-payment', [SupplierController::class, 'addPayment'])->name('suppliers.add-payment');
    Route::get('suppliers-export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('suppliers-notifications', [SupplierController::class, 'getNotifications'])->name('suppliers.notifications');
    Route::get('suppliers/{supplier}/statement/pdf', [SupplierController::class, 'exportStatementPDF'])->name('suppliers.statement.pdf');
    Route::get('suppliers/{supplier}/statement/excel', [SupplierController::class, 'exportStatementExcel'])->name('suppliers.statement.excel');
    Route::post('api/suppliers', [SupplierController::class, 'storeAsApi'])->name('api.suppliers.store');
    Route::resource('promotions', PromotionController::class);

    // مسارات إدارة الوظائف
    Route::resource('job-titles', JobTitleController::class)->except(['show']);
    Route::patch('job-titles/{jobTitle}/toggle-active', [JobTitleController::class, 'toggleActive'])->name('job-titles.toggle-active');

    // مسارات إدارة الموظفين
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggle-active');
    Route::get('employees-export', [EmployeeController::class, 'export'])->name('employees.export');

    // مسارات الرواتب
    Route::get('employees/{employee}/salary', [EmployeeController::class, 'showSalary'])->name('employees.salary');
    
    Route::middleware(['auth'])->group(function () {
        Route::get('salary-management', [EmployeeController::class, 'salariesIndex'])->name('employees.salaries.index');
        Route::get('salary-management/data', [EmployeeController::class, 'getSalariesData'])->name('employees.salaries.data');
    });

    Route::post('employees/{employee}/pay-salary', [EmployeeController::class, 'paySalary'])->name('employees.pay-salary');
    Route::post('employees/pay-multiple-salaries', [EmployeeController::class, 'payMultipleSalaries'])->name('employees.pay-multiple-salaries');
    Route::get('employees/{employee}/salary-history', [EmployeeController::class, 'getSalaryHistory'])->name('employees.salary-history');

    // مسارات السلف للموظفين
    Route::resource('employee-advances', EmployeeAdvanceController::class);
    Route::post('employee-advances/{employeeAdvance}/repay', [EmployeeAdvanceController::class, 'repay'])->name('employee-advances.repay');
    Route::get('employees/{employee}/advances', [EmployeeAdvanceController::class, 'employeeAdvances'])->name('employees.advances');

    // مسارات الحضور والانصراف
    Route::get('employees/{employee}/attendance', [EmployeeController::class, 'showAttendance'])->name('employees.attendance');
    Route::post('employees/{employee}/check-in', [EmployeeController::class, 'checkIn'])->name('employees.check-in');
    Route::post('employees/{employee}/check-out', [EmployeeController::class, 'checkOut'])->name('employees.check-out');
    Route::get('employees-attendance', [EmployeeController::class, 'attendanceIndex'])->name('employees.attendance.index');

    // مسارات التقارير
    Route::get('employees-reports', [EmployeeController::class, 'reports'])->name('employees.reports');
    Route::get('employees-reports/data', [EmployeeController::class, 'getReportsData'])->name('employees.reports.data');
    Route::get('employees-notifications', [EmployeeController::class, 'getNotifications'])->name('employees.notifications');

    // مسارات المشتريات
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/complete-payment', [PurchaseController::class, 'completePayment'])->name('purchases.completePayment');
    Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'exportToPdf'])->name('purchases.pdf');
    Route::get('purchases-profit-analytics', [PurchaseController::class, 'getProfitAnalytics'])->name('purchases.profit-analytics');
    Route::get('purchases-expiry-check', [PurchaseController::class, 'checkExpiryDates'])->name('purchases.expiry-check');

    // مسارات مرتجع المشتريات
    Route::resource('purchase-returns', PurchaseReturnController::class);
    Route::post('purchases/{purchase}/return-full', [PurchaseReturnController::class, 'returnFullPurchase'])->name('purchases.return-full');
    Route::get('purchase-returns/{purchaseReturn}/pdf', [PurchaseReturnController::class, 'exportToPdf'])->name('purchase-returns.pdf');
    Route::get('/purchase-returns/report', [PurchaseReturnController::class, 'report'])->name('purchase-returns.report');

    // مسارات تصدير البيانات
    Route::get('/export/stock-movements', [HomeController::class, 'exportStockMovements'])->name('export.stock-movements');
    Route::get('/export/low-stock', [HomeController::class, 'exportLowStock'])->name('export.low-stock');
    Route::get('/export/expiry-alerts', [HomeController::class, 'exportExpiryAlerts'])->name('export.expiry-alerts');

    // مسارات المخزون
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/adjustment', [InventoryController::class, 'stockAdjustment'])->name('adjustment');
        Route::post('/adjustment', [InventoryController::class, 'saveStockAdjustment'])->name('save-adjustment');
        Route::get('/count', [InventoryController::class, 'stockCount'])->name('count');
        Route::post('/count', [InventoryController::class, 'saveStockCount'])->name('save-count');
        Route::get('/report', [InventoryController::class, 'report'])->name('report');
        Route::get('/export-report', [InventoryController::class, 'exportReport'])->name('export-report');
        Route::get('/stock-report', [InventoryController::class, 'stockReport'])->name('stock-report');
    });

    // Customer Routes
    Route::get('customers/dashboard/summary', [CustomerController::class, 'getDashboardSummary'])->middleware('auth')->name('customers.dashboard.summary');
    
    // Apply auth middleware to all customer routes
    Route::middleware('auth')->group(function () {
        // Customer routes without permission requirements
        Route::get('customers/{customer}/info', [CustomerController::class, 'getCustomerInfo'])->name('customers.info');
        Route::get('customers/{customer}/invoices', [CustomerController::class, 'getCustomerInvoices'])->name('customers.invoices');
        Route::get('customers/{customer}/report', [CustomerController::class, 'getCustomerReport'])->name('customers.report');
        Route::get('credit-customers', [CustomerController::class, 'getCreditCustomers'])->name('customers.credit');
        Route::get('customers/{customer}/export-invoices', [CustomerController::class, 'exportInvoices'])->name('customers.export-invoices');
        Route::get('customers/export-report', [CustomerController::class, 'exportCustomerReport'])->name('customers.export-report');
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');

        // Routes that require view-customers permission
        Route::get('customers', [CustomerController::class, 'index'])->middleware('can:view-customers')->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->middleware('can:view-customers')->name('customers.show');
        
        // Routes that require create-customers permission
        Route::get('customers/create', [CustomerController::class, 'create'])->middleware('can:create-customers')->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->middleware('can:create-customers')->name('customers.store');
        
        // Routes that require edit-customers permission
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('can:edit-customers')->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('can:edit-customers')->name('customers.update');
        Route::patch('customers/{customer}', [CustomerController::class, 'update'])->middleware('can:edit-customers');
        
        // Routes that require delete-customers permission
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('can:delete-customers')->name('customers.destroy');
        
        // Customer Settings Routes
        Route::get('customer-settings', [CustomerController::class, 'getSettings'])->name('customer-settings.index');
        Route::post('customer-settings', [CustomerController::class, 'updateSettings'])->name('customer-settings.update');

        // Customer Payments Routes
        Route::post('customer-payments', [CustomerController::class, 'storePayment'])->name('customer-payments.store');
    });

    // مسارات التقارير والتحليلات
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales-by-period', [ReportController::class, 'salesByPeriod'])->name('sales-by-period');
        Route::get('/sales-by-product', [ReportController::class, 'salesByProduct'])->name('sales-by-product');
        Route::get('/profit-analysis', [ReportController::class, 'profitAnalysis'])->name('profit-analysis');
        Route::get('/customer-analysis', [ReportController::class, 'customerAnalysis'])->name('customer-analysis');
        Route::get('/delivery-analysis', [ReportController::class, 'deliveryAnalysis'])->name('delivery-analysis');
        Route::get('/all-invoices', [ReportController::class, 'allInvoices'])->name('all-invoices');
        Route::get('/all-invoices/export', [ReportController::class, 'exportAllInvoices'])->name('export-all-invoices');
        
        // New sales analysis comprehensive report
        Route::get('/sales-analysis', [ReportController::class, 'salesAnalysis'])->name('sales-analysis');
        Route::get('/sales-analysis/export', [ReportController::class, 'exportSalesAnalysis'])->name('sales-analysis.export');
        
        // تقرير تحليل أرباح المشتريات
        Route::get('/purchases-profit-analytics', [ReportController::class, 'purchasesProfitAnalytics'])->name('purchases-profit-analytics');

        // تقرير إجمالي قيمة المخزون
        Route::get('/inventory-summary', [ReportController::class, 'inventorySummary'])->name('inventory-summary');

        // التقرير المالي الشامل
        Route::get('/financial-summary', [FinancialReportController::class, 'index'])->name('financial-summary');

        // Shift Reports Routes
        Route::get('/shifts', [ShiftReportController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/search', [ShiftReportController::class, 'search'])->name('shifts.search');
        Route::get('/shifts/{shift}', [ShiftReportController::class, 'show'])->name('shifts.show');
        Route::get('/shifts/{shift}/print', [ShiftReportController::class, 'print'])->name('shifts.print');

        // Visa Sales and Transfer Sales
        Route::get('/visa-sales', [ReportController::class, 'visaSales'])->name('visa-sales');
        Route::get('/transfer-sales', [ReportController::class, 'transferSales'])->name('transfer-sales');
        
        // **FIXED**: Sales Returns Report (Moved inside the reports group for consistency)
        Route::get('/sales-returns', [ReportController::class, 'salesReturns'])->name('sales-returns.report');
        Route::get('/sales-returns/export', [ReportController::class, 'exportSalesReturns'])->name('sales-returns.export');
    });

    // Sales routes
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/pos', [SalesController::class, 'pos'])->name('sales.pos.page');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/products', [SalesController::class, 'getProducts'])->name('sales.products');
    Route::get('/sales/products/search', [SalesController::class, 'searchProduct'])->name('sales.products.search');
    Route::get('/sales/product/{product}', [SalesController::class, 'getProductDetails'])->name('sales.product.details');
    Route::get('/sales/get-product-units/{product}', [SalesController::class, 'getProductUnits'])->name('sales.product.units');
    Route::get('/sales/test-search', function() {
        return 'Search route is working!';
    });

    // Add a new dedicated route for category products just before the search route
    Route::get('/sales/products/category/{category}', [SalesController::class, 'getProductsByCategory'])->name('sales.products.by-category');

    // Invoice routes
    Route::post('/sales/invoices', [SalesController::class, 'storeInvoice'])->name('sales.invoices.store');
    Route::get('/sales/invoices/{invoice}/print', [SalesController::class, 'printInvoice'])->name('sales.invoices.print');

    // Shifts routes
    Route::prefix('shifts')->name('shifts.')->middleware('auth')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->middleware(PermissionMiddleware::class . ':view-shifts')->name('index');
        Route::get('/create', [ShiftController::class, 'create'])->middleware(PermissionMiddleware::class . ':create-shifts')->name('create');
        Route::post('/', [ShiftController::class, 'store'])->middleware(PermissionMiddleware::class . ':create-shifts')->name('store');
        Route::get('/{shift}', [ShiftController::class, 'show'])->middleware(PermissionMiddleware::class . ':view-shifts')->name('show');
        Route::get('/{shift}/edit', [ShiftController::class, 'edit'])->middleware(PermissionMiddleware::class . ':edit-shifts')->name('edit');
        Route::put('/{shift}', [ShiftController::class, 'update'])->middleware(PermissionMiddleware::class . ':edit-shifts')->name('update');
        Route::post('/{shift}/withdraw', [ShiftController::class, 'withdraw'])->middleware(PermissionMiddleware::class . ':edit-shifts')->name('withdraw');
        Route::post('/{shift}/close', [ShiftController::class, 'close'])->middleware(PermissionMiddleware::class . ':edit-shifts')->name('close');
        Route::get('/{shift}/print', [ShiftController::class, 'print'])->middleware(PermissionMiddleware::class . ':view-shifts')->name('print');
        
        // Route to check if there's an open shift for the current user
        Route::get('/check/status', function () {
            $openShift = \App\Models\Shift::getCurrentOpenShift(true);
            return response()->json([
                'hasOpenShift' => $openShift && !$openShift->is_closed,
                'shift' => $openShift ? [
                    'id' => $openShift->id,
                    'shift_number' => $openShift->shift_number,
                    'is_closed' => $openShift->is_closed
                ] : null
            ]);
        })->name('check');
    });

    Route::put('price-types/{priceType}/toggle-active', [PriceTypeController::class, 'toggleActive'])->middleware('can:edit-price-types')->name('price-types.toggle-active');
    Route::get('/api/price-types/available', [PriceTypeController::class, 'getAvailablePriceTypes'])->name('api.price-types.available');
    Route::resource('price-types', PriceTypeController::class)->except(['show']);

    // Expenses Routes
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->middleware(PermissionMiddleware::class . ':view-expenses')->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->middleware(PermissionMiddleware::class . ':create-expenses')->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->middleware(PermissionMiddleware::class . ':create-expenses')->name('store');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->middleware(PermissionMiddleware::class . ':edit-expenses')->name('edit');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->middleware(PermissionMiddleware::class . ':edit-expenses')->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->middleware(PermissionMiddleware::class . ':delete-expenses')->name('destroy');
    });

    // Deposits Routes
    Route::prefix('deposits')->name('deposits.')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->middleware(PermissionMiddleware::class . ':view-deposits')->name('index');
        Route::get('/create', [DepositController::class, 'create'])->middleware(PermissionMiddleware::class . ':create-deposits')->name('create');
        Route::post('/', [DepositController::class, 'store'])->middleware(PermissionMiddleware::class . ':create-deposits')->name('store');
        Route::get('/{deposit}/edit', [DepositController::class, 'edit'])->middleware(PermissionMiddleware::class . ':edit-deposits')->name('edit');
        Route::put('/{deposit}', [DepositController::class, 'update'])->middleware(PermissionMiddleware::class . ':edit-deposits')->name('update');
        Route::delete('/{deposit}', [DepositController::class, 'destroy'])->middleware(PermissionMiddleware::class . ':delete-deposits')->name('destroy');
    });

    // Expense Categories Routes
    Route::prefix('expense-categories')->name('expense-categories.')->group(function () {
        Route::get('/', [ExpenseCategoryController::class, 'index'])->middleware(PermissionMiddleware::class . ':view-expense-categories')->name('index');
        Route::get('/create', [ExpenseCategoryController::class, 'create'])->middleware(PermissionMiddleware::class . ':create-expense-categories')->name('create');
        Route::post('/', [ExpenseCategoryController::class, 'store'])->middleware(PermissionMiddleware::class . ':create-expense-categories')->name('store');
        Route::get('/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->middleware(PermissionMiddleware::class . ':edit-expense-categories')->name('edit');
        Route::put('/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->middleware(PermissionMiddleware::class . ':edit-expense-categories')->name('update');
        Route::delete('/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->middleware(PermissionMiddleware::class . ':delete-expense-categories')->name('destroy');
        Route::patch('/{expenseCategory}/toggle-active', [ExpenseCategoryController::class, 'toggleActive'])->middleware(PermissionMiddleware::class . ':edit-expense-categories')->name('toggle-active');
    });

    // Deposit Sources Routes
    Route::prefix('deposit-sources')->name('deposit-sources.')->group(function () {
        Route::get('/', [DepositSourceController::class, 'index'])->middleware(PermissionMiddleware::class . ':view-deposit-sources')->name('index');
        Route::get('/create', [DepositSourceController::class, 'create'])->middleware(PermissionMiddleware::class . ':create-deposit-sources')->name('create');
        Route::post('/', [DepositSourceController::class, 'store'])->middleware(PermissionMiddleware::class . ':create-deposit-sources')->name('store');
        Route::get('/{depositSource}/edit', [DepositSourceController::class, 'edit'])->middleware(PermissionMiddleware::class . ':edit-deposit-sources')->name('edit');
        Route::put('/{depositSource}', [DepositSourceController::class, 'update'])->middleware(PermissionMiddleware::class . ':edit-deposit-sources')->name('update');
        Route::delete('/{depositSource}', [DepositSourceController::class, 'destroy'])->middleware(PermissionMiddleware::class . ':delete-deposit-sources')->name('destroy');
        Route::patch('/{depositSource}/toggle-active', [DepositSourceController::class, 'toggleActive'])->middleware(PermissionMiddleware::class . ':edit-deposit-sources')->name('toggle-active');
    });

    // Backup Routes
    Route::prefix('backups')->name('backups.')->middleware(PermissionMiddleware::class . ':manage-backups')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::post('/upload', [BackupController::class, 'upload'])->name('upload');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
        Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('destroy');
        Route::post('/restore/{filename}', [BackupController::class, 'restore'])->name('restore');
    });

    // API: Get stock quantity for a product in a specific unit
    Route::get('/inventory/get-stock-quantity/{productId}/{unitId}', [InventoryController::class, 'getStockQuantityAjax'])->name('inventory.get-stock-quantity');

    // **FIXED**: Sales Returns Page (Standardized using Route::resource)
    Route::resource('sales-returns', SalesReturnPageController::class)->only(['index', 'create', 'store', 'show']);

    // مسارات معاملات التوصيل
    Route::prefix('delivery-transactions')->name('delivery-transactions.')->group(function() {
        Route::get('/', [DeliveryTransactionController::class, 'index'])->name('index');
        Route::get('/incomplete', [DeliveryTransactionController::class, 'getIncompleteOrders'])->name('incomplete');
        Route::get('/current-shift', [DeliveryTransactionController::class, 'getCurrentShiftTransactions'])->name('current-shift');
        Route::get('/{transaction}', [DeliveryTransactionController::class, 'show'])->name('show');
        Route::post('/{transaction}/update-status', [DeliveryTransactionController::class, 'updateDeliveryStatus'])->name('update-status');
        Route::post('/{transaction}/update-shipping-status', [DeliveryTransactionController::class, 'updateShippingStatus'])->name('update-shipping-status');
        Route::post('/{transaction}/add-payment', [DeliveryTransactionController::class, 'addPayment'])->name('add-payment');
        Route::post('/{transaction}/update-delivery-time', [DeliveryTransactionController::class, 'updateDeliveryTime'])->name('update-delivery-time');
        Route::put('/{transaction}', [DeliveryTransactionController::class, 'update'])->name('update');
        Route::post('/{transaction}/return', [DeliveryTransactionController::class, 'returnDelivery'])->name('return');
    });
    // مسارات الألوان
    Route::get('colors', [App\Http\Controllers\ColorController::class, 'index'])->name('colors.index');
    Route::get('colors/create', [App\Http\Controllers\ColorController::class, 'create'])->name('colors.create');
    Route::post('colors', [App\Http\Controllers\ColorController::class, 'store'])->name('colors.store');
    Route::get('colors/{color}/edit', [App\Http\Controllers\ColorController::class, 'edit'])->name('colors.edit');
    Route::put('colors/{color}', [App\Http\Controllers\ColorController::class, 'update'])->name('colors.update');
    Route::delete('colors/{color}', [App\Http\Controllers\ColorController::class, 'destroy'])->name('colors.destroy');
    Route::get('api/colors', [App\Http\Controllers\ColorController::class, 'getColors'])->name('api.colors');

    // مسارات المقاسات
    Route::get('sizes', [App\Http\Controllers\SizeController::class, 'index'])->name('sizes.index');
    Route::get('sizes/create', [App\Http\Controllers\SizeController::class, 'create'])->name('sizes.create');
    Route::post('sizes', [App\Http\Controllers\SizeController::class, 'store'])->name('sizes.store');
    Route::get('sizes/{size}/edit', [App\Http\Controllers\SizeController::class, 'edit'])->name('sizes.edit');
    Route::put('sizes/{size}', [App\Http\Controllers\SizeController::class, 'update'])->name('sizes.update');
    Route::delete('sizes/{size}', [App\Http\Controllers\SizeController::class, 'destroy'])->name('sizes.destroy');
    Route::get('api/sizes', [App\Http\Controllers\SizeController::class, 'getSizes'])->name('api.sizes');

    // مسارات إدارة شركات الشحن
    Route::resource('shipping-companies', ShippingCompanyController::class);
    Route::post('shipping-companies/{shippingCompany}/toggle-active', [ShippingCompanyController::class, 'toggleActive'])->name('shipping-companies.toggle-active');
    Route::get('shipping-companies-report', [ShippingCompanyController::class, 'report'])->name('shipping-companies.report');

    // مسارات العروض الترويجية
    Route::resource('promotions', PromotionController::class);
    Route::post('promotions/{promotion}/toggle-active', [PromotionController::class, 'toggleActive'])->name('promotions.toggle-active');

    // Activation routes
    Route::get('/activate', [ActivationController::class, 'showForm'])->name('activation.form');
    Route::post('/activate/submit', [ActivationController::class, 'submit'])->name('activation.submit');

    // Loyalty Points Routes
    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        // Main dashboard
        Route::get('/dashboard', [App\Http\Controllers\LoyaltyController::class, 'dashboard'])
            ->middleware('can:view-loyalty-points')
            ->name('dashboard');
            
        // Settings
        Route::get('/settings', [App\Http\Controllers\LoyaltyController::class, 'settings'])
            ->middleware('can:manage-loyalty-settings')
            ->name('settings');
        Route::put('/settings', [App\Http\Controllers\LoyaltyController::class, 'updateSettings'])
            ->middleware('can:manage-loyalty-settings')
            ->name('settings.update');

        // Customer loyalty management
        Route::get('/customers', [App\Http\Controllers\LoyaltyController::class, 'customersIndex'])
            ->middleware('can:view-loyalty-points')
            ->name('customers');
        Route::get('/customers/{customer}/dashboard', [App\Http\Controllers\LoyaltyController::class, 'customerDashboard'])
            ->middleware('can:view-loyalty-points')
            ->name('customer.dashboard');

        // Transactions history
        Route::get('/transactions', [App\Http\Controllers\LoyaltyController::class, 'transactionsIndex'])
            ->middleware('can:view-loyalty-points')
            ->name('transactions');

        // Points management actions
        Route::post('/redeem-to-balance', [App\Http\Controllers\LoyaltyController::class, 'redeemToBalance'])
            ->middleware('can:manage-loyalty-points')
            ->name('redeem-to-balance');
        Route::post('/adjust-points', [App\Http\Controllers\LoyaltyController::class, 'adjustPoints'])
            ->middleware('can:manage-loyalty-points')
            ->name('adjust-points');
        Route::post('/reset-points', [App\Http\Controllers\LoyaltyController::class, 'resetPoints'])
            ->middleware('can:manage-loyalty-points')
            ->name('reset-points');
    });

    // Loyalty Points AJAX/API Routes (moved from api.php for web authentication)
    Route::prefix('loyalty/api')->name('loyalty.api.')->group(function () {
        // Customer loyalty information
        Route::get('/customers/{customer}/summary', [App\Http\Controllers\LoyaltyController::class, 'getCustomerSummary'])
            ->name('customer.summary');
        Route::get('/customers/{customer}/history', [App\Http\Controllers\LoyaltyController::class, 'getCustomerHistory'])
            ->name('customer.history');
        Route::get('/customers/{customer}/max-discount', [App\Http\Controllers\LoyaltyController::class, 'getMaximumDiscount'])
            ->name('customer.max-discount');

        // Points calculation for sales system
        Route::post('/calculate-points', [App\Http\Controllers\LoyaltyController::class, 'calculatePointsForAmount'])
            ->name('calculate-points');
        Route::post('/calculate-discount', [App\Http\Controllers\LoyaltyController::class, 'calculateDiscountForPoints'])
            ->name('calculate-discount');

        // Points redemption (for sales system)
        Route::post('/apply-invoice-discount', [App\Http\Controllers\LoyaltyController::class, 'applyInvoiceDiscount'])
            ->name('apply-invoice-discount');

        // Statistics
        Route::get('/statistics', [App\Http\Controllers\LoyaltyController::class, 'getStatistics'])
            ->name('statistics');
        
        // Settings API (for AJAX calls from customer management page)
        Route::get('/settings', [App\Http\Controllers\LoyaltyController::class, 'getSettings'])
            ->name('settings');
    });

    // عن النظام (About) صفحة
    Route::get('/about', function () {
        return view('about');
    })->name('about');
});

// مسارات مرتجعات المشتريات
// Supplier Payments
Route::middleware(['auth'])->prefix('supplier-payments')->name('supplier-payments.')->group(function () {
    Route::get('/create', [SupplierPaymentController::class, 'create'])->name('create');
    Route::post('/', [SupplierPaymentController::class, 'store'])->name('store');
    Route::get('/get-invoices/{supplierId}', [SupplierPaymentController::class, 'getSupplierInvoices'])->name('get-invoices');
});

Route::get('/reports/product-sales', [ReportController::class, 'productSales'])->name('reports.product-sales');
Route::get('/products/{product}/log', [ProductController::class, 'showLog'])->name('products.log');