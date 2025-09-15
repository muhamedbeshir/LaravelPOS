<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockMovementsExport;
use App\Exports\LowStockExport;
use App\Exports\ExpiryAlertsExport;
use App\Services\CacheService;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Get cached dashboard statistics
        $dashboardStats = CacheService::getDashboardStats();

        // Extract statistics from the cached data
        $todaySales = $dashboardStats['todaySales'];
        $productsCount = $dashboardStats['productsCount'];
        $customersCount = $dashboardStats['customersCount'];
        $lowStockCount = $dashboardStats['lowStockCount'];
        $todayMovementsCount = $dashboardStats['todayMovementsCount'];
        $totalStockValue = $dashboardStats['totalStockValue'];

        // تحديد نطاق التاريخ
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::today();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::today();

        // Get cached low stock products
        $lowStockProducts = CacheService::getLowStockProducts(5);

        // Get cached recent stock movements
        $recentMovements = CacheService::getRecentStockMovements(10);

        // Get cached top selling products
        $topSellingProducts = CacheService::getTopSellingProducts();

        // حساب الربح المتوقع باستخدام آخر سعر بيع وشراء
        $expectedProfit = Product::where('products.is_active', true)
            ->leftJoin('purchase_items', function($join) {
                $join->on('products.id', '=', 'purchase_items.product_id')
                    ->whereRaw('purchase_items.id = (
                        SELECT id FROM purchase_items 
                        WHERE product_id = products.id 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )');
            })
            ->selectRaw('COALESCE(SUM(products.stock_quantity * (COALESCE(purchase_items.selling_price, 0) - COALESCE(purchase_items.purchase_price, 0))), 0) as total_profit')
            ->first()
            ->total_profit;

        // Expiry alerts - keep this uncached as it's time-sensitive
        $expiryAlerts = Product::where('products.is_active', true)
            ->join('purchase_items', function($join) {
                $join->on('products.id', '=', 'purchase_items.product_id')
                    ->whereNotNull('purchase_items.expiry_date')
                    ->whereRaw('purchase_items.id = (
                        SELECT id FROM purchase_items 
                        WHERE product_id = products.id 
                        AND expiry_date IS NOT NULL
                        ORDER BY expiry_date ASC 
                        LIMIT 1
                    )');
            })
            ->where(function($query) {
                $query->where('purchase_items.expiry_date', '<=', now()->addDays(30))
                      ->where('purchase_items.expiry_date', '>', now());
            })
            ->select('products.*', 
                    'purchase_items.expiry_date',
                    'purchase_items.production_date',
                    'purchase_items.purchase_price')
            ->orderBy('purchase_items.expiry_date')
            ->limit(5)
            ->get();

        return view('home', compact(
            'todaySales',
            'productsCount',
            'customersCount',
            'lowStockCount',
            'todayMovementsCount',
            'totalStockValue',
            'lowStockProducts',
            'expiryAlerts',
            'recentMovements',
            'topSellingProducts',
            'expectedProfit'
        ));
    }

    public function exportStockMovements(Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::today();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::today();

        return Excel::download(
            new StockMovementsExport($startDate, $endDate),
            'stock_movements_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportLowStock()
    {
        return Excel::download(
            new LowStockExport(),
            'low_stock_products_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportExpiryAlerts()
    {
        return Excel::download(
            new ExpiryAlertsExport(),
            'expiry_alerts_' . Carbon::now()->format('Y-m-d') . '.xlsx'
        );
    }
}
