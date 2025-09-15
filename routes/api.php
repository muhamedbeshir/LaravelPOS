<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\Api\SalesApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\UnitApiController;
use App\Http\Controllers\Api\PurchaseApiController;
use App\Http\Controllers\Api\PurchaseReturnApiController;
use App\Http\Controllers\Api\SupplierApiController;
use App\Http\Controllers\Api\JobTitleApiController;
use App\Http\Controllers\Api\SettingApiController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\CustomerPaymentApiController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\API\SalesController;
use App\Http\Controllers\Api\SuspendedSaleController;
use App\Http\Controllers\Api\SalesReturnController;
use App\Http\Controllers\Api\InvoiceController;
use App\Models\Shift;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Check if there's an open shift for the current user
Route::middleware('auth:sanctum')->get('/check-open-shift', function (Request $request) {
    $openShift = Shift::getCurrentOpenShift(true);
    return response()->json([
        'hasOpenShift' => $openShift && !$openShift->is_closed,
        'shift' => $openShift ? [
            'id' => $openShift->id,
            'shift_number' => $openShift->shift_number,
            'is_closed' => $openShift->is_closed
        ] : null
    ]);
});

// --- CORRECTED AND FINAL PRODUCT ROUTES ---
Route::get('products/search', [ProductApiController::class, 'search'])->name('api.products.search');
Route::get('products/select2', [ProductApiController::class, 'searchForSelect2'])->name('api.products.select2');
Route::get('products/find-one', [ProductApiController::class, 'findOneByTerm'])->name('api.products.find_one');
// --- END CORRECTED ROUTES ---


// Customer routes
Route::get('customers/dashboard/summary', [CustomerController::class, 'getDashboardSummary']);
Route::apiResource('customers', CustomerController::class)->names([
    'index' => 'api.customers.index',
    'store' => 'api.customers.store',
    'show' => 'api.customers.show',
    'update' => 'api.customers.update',
    'destroy' => 'api.customers.destroy',
]);
Route::get('customers/{customer}/invoices', [CustomerController::class, 'getCustomerInvoices']);
Route::get('customers/{customer}/report', [CustomerController::class, 'getCustomerReport']);
Route::get('credit-customers', [CustomerController::class, 'getCreditCustomers']);

// Export routes
Route::get('customers/{customer}/export-invoices', [CustomerController::class, 'exportInvoices']);
Route::get('customers/export-report', [CustomerController::class, 'exportCustomerReport']);

// Customer payment routes
Route::apiResource('customer-payments', CustomerPaymentController::class)->except(['update'])->names([
    'index' => 'api.customer-payments.index',
    'store' => 'api.customer-payments.store',
    'show' => 'api.customer-payments.show',
    'destroy' => 'api.customer-payments.destroy',
]);

// Customer settings routes
Route::get('customer-settings', [CustomerController::class, 'getSettings']);
Route::post('customer-settings', [CustomerController::class, 'updateSettings']);

// Sales API Routes
Route::prefix('sales')->group(function () {
    // Products
    Route::get('/products/search', [SalesApiController::class, 'searchProducts'])->name('api.sales.products.search');
    Route::get('/products', [SalesApiController::class, 'getProducts']);
    Route::get('/product/{id}', [SalesApiController::class, 'getProduct']);
    Route::get('/get-product-units/{id}', [SalesApiController::class, 'getProductUnits']);
    
    // Categories
    Route::get('/categories', [SalesApiController::class, 'getCategories']);
    
    // Customers
    Route::get('/customers', [SalesApiController::class, 'getCustomers']);
    Route::post('/customers', [SalesApiController::class, 'createCustomer']);
    
    // Employees
    Route::get('/delivery-employees', [SalesApiController::class, 'getDeliveryEmployees']);
    
    // Invoices
    Route::get('/invoices', [SalesApiController::class, 'getInvoices']);
    Route::get('/invoices/{id}', [SalesApiController::class, 'getInvoice']);
    Route::get('/invoices/by-number/{invoiceNumber}', [SalesApiController::class, 'getInvoiceByNumber']);
    Route::post('/invoices', [SalesApiController::class, 'storeInvoice']);
    
    // Loyalty Points Integration for Sales
    Route::post('/loyalty/calculate-points', [SalesApiController::class, 'calculateLoyaltyPoints']);
    Route::get('/loyalty/customer-status/{customerId}', [SalesApiController::class, 'getCustomerLoyaltyStatus']);
    Route::post('/loyalty/calculate-discount', [SalesApiController::class, 'calculatePointsDiscount']);
});

// Product API Routes
Route::prefix('products')->group(function () {
    Route::get('/{product}/units', [ProductApiController::class, 'getProductUnits']);
    Route::get('/', [ProductApiController::class, 'getAllProducts']);
    Route::get('/{id}', [ProductApiController::class, 'getProduct']);
    Route::post('/', [ProductApiController::class, 'storeProduct']);
    Route::put('/{id}', [ProductApiController::class, 'updateProduct']);
    Route::delete('/{id}', [ProductApiController::class, 'deleteProduct']);
    Route::patch('/{id}/toggle-status', [ProductApiController::class, 'toggleProductStatus']);
    Route::get('/{id}/price-history', [ProductApiController::class, 'getPriceHistory']);
});

// Inventory API Routes
Route::prefix('inventory')->group(function () {
    Route::get('/dashboard', [InventoryApiController::class, 'getDashboard']);
    Route::post('/adjust', [InventoryApiController::class, 'adjustStock']);
    Route::post('/count', [InventoryApiController::class, 'stockCount']);
    Route::get('/movements', [InventoryApiController::class, 'getStockMovements']);
    Route::get('/low-stock', [InventoryApiController::class, 'getLowStockProducts']);
});

// Report API Routes
Route::prefix('reports')->group(function () {
    Route::get('/dashboard-summary', [ReportApiController::class, 'getDashboardSummary']);
    Route::get('/sales-by-period', [ReportApiController::class, 'getSalesByPeriod']);
    Route::get('/sales-by-product', [ReportApiController::class, 'getSalesByProduct']);
    Route::get('/customer-analysis', [ReportApiController::class, 'getCustomerAnalysis']);
    Route::get('/category-analysis', [ReportApiController::class, 'getCategoryAnalysis']);
});

// Employee Routes
Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeApiController::class, 'getAllEmployees']);
    Route::get('/job-titles', [EmployeeApiController::class, 'getJobTitles']);
    Route::get('/{id}', [EmployeeApiController::class, 'getEmployee']);
    Route::post('/', [EmployeeApiController::class, 'storeEmployee']);
    Route::put('/{id}', [EmployeeApiController::class, 'updateEmployee']);
    Route::put('/{id}/toggle-status', [EmployeeApiController::class, 'toggleEmployeeStatus']);
    Route::post('/{id}/check-in', [EmployeeApiController::class, 'checkIn']);
    Route::post('/{id}/check-out', [EmployeeApiController::class, 'checkOut']);
    Route::post('/{id}/pay-salary', [EmployeeApiController::class, 'paySalary']);
    Route::get('/{id}/attendance', [EmployeeApiController::class, 'getAttendanceHistory']);
    Route::get('/{id}/salary-history', [EmployeeApiController::class, 'getSalaryHistory']);
});

// Category API Routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryApiController::class, 'getAllCategories']);
    Route::get('/{id}', [CategoryApiController::class, 'getCategory']);
    Route::post('/', [CategoryApiController::class, 'storeCategory']);
    Route::put('/{id}', [CategoryApiController::class, 'updateCategory']);
    Route::delete('/{id}', [CategoryApiController::class, 'deleteCategory']);
    Route::patch('/{id}/toggle-status', [CategoryApiController::class, 'toggleCategoryStatus']);
});

// Unit API Routes
Route::prefix('units')->group(function () {
    Route::get('/', [UnitApiController::class, 'getAllUnits']);
    Route::get('/{id}', [UnitApiController::class, 'getUnit']);
    Route::post('/', [UnitApiController::class, 'storeUnit']);
    Route::put('/{id}', [UnitApiController::class, 'updateUnit']);
    Route::delete('/{id}', [UnitApiController::class, 'deleteUnit']);
});

// Purchase API Routes
Route::prefix('purchases')->group(function () {
    Route::get('/', [PurchaseApiController::class, 'getAllPurchases']);
    Route::get('/{id}', [PurchaseApiController::class, 'getPurchase']);
    Route::post('/', [PurchaseApiController::class, 'storePurchase']);
    Route::put('/{id}', [PurchaseApiController::class, 'updatePurchase']);
    Route::delete('/{id}', [PurchaseApiController::class, 'deletePurchase']);
    Route::get('/suppliers/{id}/purchases', [PurchaseApiController::class, 'getSupplierPurchases']);
});

// Purchase Returns API Routes
Route::prefix('purchase-returns')->group(function () {
    Route::get('/', [PurchaseReturnApiController::class, 'getAllPurchaseReturns']);
    Route::get('/{id}', [PurchaseReturnApiController::class, 'getPurchaseReturn']);
    Route::post('/', [PurchaseReturnApiController::class, 'createPurchaseReturn']);
    Route::post('/full/{purchaseId}', [PurchaseReturnApiController::class, 'returnFullPurchase']);
    Route::get('/purchase/{purchaseId}', [PurchaseReturnApiController::class, 'getPurchaseDetailsForReturn']);
});

// Supplier API Routes
Route::prefix('suppliers')->group(function () {
    Route::get('/', [SupplierApiController::class, 'getAllSuppliers']);
    Route::get('/{id}', [SupplierApiController::class, 'getSupplier']);
    Route::post('/', [SupplierApiController::class, 'storeSupplier']);
    Route::put('/{id}', [SupplierApiController::class, 'updateSupplier']);
    Route::delete('/{id}', [SupplierApiController::class, 'deleteSupplier']);
    Route::post('/{id}/payments', [SupplierApiController::class, 'makePayment']);
    Route::get('/{id}/payment-history', [SupplierApiController::class, 'getPaymentHistory']);
});

// Job Title API Routes
Route::prefix('job-titles')->group(function () {
    Route::get('/', [JobTitleApiController::class, 'getAllJobTitles']);
    Route::get('/{id}', [JobTitleApiController::class, 'getJobTitle']);
    Route::post('/', [JobTitleApiController::class, 'storeJobTitle']);
    Route::put('/{id}', [JobTitleApiController::class, 'updateJobTitle']);
    Route::delete('/{id}', [JobTitleApiController::class, 'deleteJobTitle']);
    Route::patch('/{id}/toggle-status', [JobTitleApiController::class, 'toggleJobTitleStatus']);
});

// Settings API Routes
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingApiController::class, 'getAllSettings']);
    Route::get('/group/{group}', [SettingApiController::class, 'getSettingsByGroup']);
    Route::get('/key/{key}', [SettingApiController::class, 'getSettingByKey']);
    Route::post('/', [SettingApiController::class, 'updateSettings']);
    Route::post('/delete-password', [SettingApiController::class, 'updateDeletePassword']);
    Route::post('/verify-password', [SettingApiController::class, 'verifyDeletePassword']);
});

// Dedicated Customer API Routes
Route::prefix('customer-api')->group(function () {
    Route::get('/', [CustomerApiController::class, 'getAllCustomers']);
    Route::get('/dashboard/summary', [CustomerApiController::class, 'getDashboardSummary']);
    Route::get('/credit', [CustomerApiController::class, 'getCreditCustomers']);
    Route::get('/{id}', [CustomerApiController::class, 'getCustomer']);
    Route::post('/', [CustomerApiController::class, 'storeCustomer']);
    Route::put('/{id}', [CustomerApiController::class, 'updateCustomer']);
    Route::delete('/{id}', [CustomerApiController::class, 'deleteCustomer']);
    Route::patch('/{id}/toggle-status', [CustomerApiController::class, 'toggleCustomerStatus']);
    Route::get('/{id}/invoices', [CustomerApiController::class, 'getCustomerInvoices']);
    Route::get('/{id}/report', [CustomerApiController::class, 'getCustomerReport']);
    Route::get('/settings', [CustomerApiController::class, 'getSettings']);
    Route::post('/settings', [CustomerApiController::class, 'updateSettings']);
});

// Customer Payment API Routes
Route::prefix('customer-payments-api')->group(function () {
    Route::get('/', [CustomerPaymentApiController::class, 'getAllPayments']);
    Route::get('/summary', [CustomerPaymentApiController::class, 'getPaymentSummary']);
    Route::get('/{id}', [CustomerPaymentApiController::class, 'getPayment']);
    Route::post('/', [CustomerPaymentApiController::class, 'storePayment']);
    Route::delete('/{id}', [CustomerPaymentApiController::class, 'deletePayment']);
});

// Export API Routes
Route::prefix('exports')->group(function () {
    Route::get('/customers/{customer}/invoices', [ExportController::class, 'exportCustomerInvoices']);
});

// Check for open shifts
Route::get('/check-shifts', function () {
    $openShifts = \App\Models\Shift::where('is_closed', false)->get();
    return response()->json([
        'open_shifts_count' => $openShifts->count(),
        'open_shifts' => $openShifts
    ]);
});

// Sales settlement routes
Route::get('/sales/settlement/today', [SalesController::class, 'getTodaySettlement']);

// مسارات وحدات المنتجات
Route::get('/product-units/{productUnit}/prices', 'App\\Http\Controllers\\Api\\ProductUnitPriceController@getPrices');
Route::get('/product-units/{productUnit}/last-purchase-price', 'App\\Http\Controllers\\Api\\ProductUnitPriceController@getLastPurchasePrice');

// Suspended Sale API Routes
Route::apiResource('suspended-sales', SuspendedSaleController::class)->except(['update']);

// Invoice Search API endpoint
Route::get('/invoices/search', [InvoiceController::class, 'search'])->name('api.invoices.search');

// Sales Return API Routes
Route::prefix('sales-returns')->name('sales-returns.')->group(function () {
    Route::post('/item', [SalesReturnController::class, 'returnByItem'])->name('item');
    Route::post('/invoice/full', [SalesReturnController::class, 'returnFullInvoice'])->name('invoice.full');
    Route::post('/invoice/partial', [SalesReturnController::class, 'returnPartialInvoice'])->name('invoice.partial');
});

// Sales Returns API
Route::post('/sales-returns/direct', [SalesReturnController::class, 'storeDirectReturn']);

// This was a source of conflict and has been removed
// Route::get('/products-api/search', [ProductApiController::class, 'search']); 
Route::get('/products-api/{product}/units', [ProductApiController::class, 'getUnits']);

Route::get('/shifts/{shift}/multiple-payment-invoices', [\App\Http\Controllers\Api\ShiftApiController::class, 'getMultiplePaymentInvoices']);

// Sales Returns Report API endpoints
Route::get('/invoices/{invoice}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
Route::get('/sales-returns/{return}', [\App\Http\Controllers\Api\SalesReturnController::class, 'show']);

