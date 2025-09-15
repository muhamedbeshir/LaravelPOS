<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportApiController extends Controller
{
    /**
     * Get sales data grouped by time period
     */
    public function getSalesByPeriod(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'group_by' => 'required|in:daily,weekly,monthly,yearly'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $query = Invoice::whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
            
            // Apply filters if provided
            if ($request->has('invoice_type')) {
                $query->where('invoice_type', $request->input('invoice_type'));
            }
            
            if ($request->has('order_type')) {
                $query->where('order_type', $request->input('order_type'));
            }
            
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->input('customer_id'));
            }
            
            // Group by specified period
            switch ($request->group_by) {
                case 'daily':
                    $sales = $query->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(paid_amount) as total_paid'),
                        DB::raw('SUM(remaining) as total_remaining'),
                        DB::raw('SUM(total_discount) as total_discount'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('date')->get();
                    break;
                    
                case 'weekly':
                    $sales = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('WEEK(created_at) as week'),
                        DB::raw('MIN(DATE(created_at)) as week_start'),
                        DB::raw('MAX(DATE(created_at)) as week_end'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(paid_amount) as total_paid'),
                        DB::raw('SUM(remaining) as total_remaining'),
                        DB::raw('SUM(total_discount) as total_discount'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('year', 'week')->get();
                    break;
                    
                case 'monthly':
                    $sales = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(paid_amount) as total_paid'),
                        DB::raw('SUM(remaining) as total_remaining'),
                        DB::raw('SUM(total_discount) as total_discount'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('year', 'month')->get();
                    
                    // Format month names
                    $sales = $sales->map(function($item) {
                        $date = Carbon::createFromDate($item->year, $item->month, 1);
                        $item->month_name = $date->format('F');
                        return $item;
                    });
                    break;
                    
                case 'yearly':
                    $sales = $query->select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total) as total_sales'),
                        DB::raw('SUM(paid_amount) as total_paid'),
                        DB::raw('SUM(remaining) as total_remaining'),
                        DB::raw('SUM(total_discount) as total_discount'),
                        DB::raw('AVG(total) as average_order_value')
                    )->groupBy('year')->get();
                    break;
            }
            
            return response()->json([
                'success' => true,
                'group_by' => $request->group_by,
                'data' => $sales
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating sales by period report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating sales by period report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get sales data grouped by product
     */
    public function getSalesByProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'category_id' => 'nullable|exists:categories,id',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $query = DB::table('invoice_items')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereBetween('invoices.created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
                
            // Apply filters if provided
            if ($request->has('category_id')) {
                $query->where('products.category_id', $request->category_id);
            }
            
            if ($request->has('invoice_type')) {
                $query->where('invoices.invoice_type', $request->invoice_type);
            }
            
            if ($request->has('order_type')) {
                $query->where('invoices.order_type', $request->order_type);
            }
            
            $query->select(
                'products.id',
                'products.name',
                'products.barcode',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.subtotal) as total_subtotal'),
                DB::raw('SUM(invoice_items.total_discount) as total_discount'),
                DB::raw('SUM(invoice_items.total) as total_sales'),
                DB::raw('COUNT(DISTINCT invoices.id) as number_of_orders')
            )
            ->groupBy('products.id', 'products.name', 'products.barcode')
            ->orderByDesc('total_sales');
            
            // Apply limit if provided
            if ($request->has('limit')) {
                $query->limit($request->limit);
            }
            
            $sales = $query->get();
            
            // Get categories for products
            $productIds = $sales->pluck('id')->toArray();
            $categories = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereIn('products.id', $productIds)
                ->select('products.id as product_id', 'categories.id as category_id', 'categories.name as category_name')
                ->get()
                ->keyBy('product_id');
                
            // Add category info to results
            $sales = $sales->map(function($item) use ($categories) {
                if (isset($categories[$item->id])) {
                    $item->category_id = $categories[$item->id]->category_id;
                    $item->category_name = $categories[$item->id]->category_name;
                }
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'total_products' => count($sales),
                'data' => $sales
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating sales by product report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating sales by product report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer purchase analysis
     */
    public function getCustomerAnalysis(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $query = DB::table('invoices')
                ->join('customers', 'invoices.customer_id', '=', 'customers.id')
                ->whereBetween('invoices.created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
                
            // Apply filters
            if ($request->has('invoice_type')) {
                $query->where('invoices.invoice_type', $request->invoice_type);
            }
            
            if ($request->has('order_type')) {
                $query->where('invoices.order_type', $request->order_type);
            }
            
            $query->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.credit_balance',
                DB::raw('COUNT(invoices.id) as total_orders'),
                DB::raw('SUM(invoices.total) as total_spent'),
                DB::raw('SUM(invoices.paid_amount) as total_paid'),
                DB::raw('SUM(invoices.remaining) as total_remaining'),
                DB::raw('AVG(invoices.total) as average_order_value'),
                DB::raw('MAX(invoices.created_at) as last_purchase_date')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.credit_balance')
            ->orderByDesc('total_spent');
            
            // Apply limit if provided
            if ($request->has('limit')) {
                $query->limit($request->limit);
            }
            
            $customers = $query->get();
            
            // Format dates
            $customers = $customers->map(function($item) {
                $item->last_purchase_date = Carbon::parse($item->last_purchase_date)->format('Y-m-d H:i:s');
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'total_customers' => count($customers),
                'data' => $customers
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating customer analysis report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating customer analysis report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get category sales analysis
     */
    public function getCategoryAnalysis(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $categorySales = DB::table('invoice_items')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereBetween('invoices.created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
                
            // Apply filters
            if ($request->has('invoice_type')) {
                $categorySales->where('invoices.invoice_type', $request->invoice_type);
            }
            
            if ($request->has('order_type')) {
                $categorySales->where('invoices.order_type', $request->order_type);
            }
            
            $categorySales = $categorySales->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT invoice_items.product_id) as total_products'),
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total) as total_sales'),
                DB::raw('SUM(invoice_items.total_discount) as total_discount'),
                DB::raw('COUNT(DISTINCT invoices.id) as number_of_orders')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();
            
            // Get overall total for percentage calculation
            $overallTotal = $categorySales->sum('total_sales');
            
            // Calculate percentages
            $categorySales = $categorySales->map(function($item) use ($overallTotal) {
                $item->percentage = $overallTotal > 0 ? round(($item->total_sales / $overallTotal) * 100, 2) : 0;
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'overall_total' => $overallTotal,
                'data' => $categorySales
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating category analysis report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating category analysis report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get summary dashboard data
     */
    public function getDashboardSummary(Request $request)
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $thisWeekStart = Carbon::now()->startOfWeek();
            $thisMonthStart = Carbon::now()->startOfMonth();
            
            // Today's sales
            $todaySales = Invoice::whereDate('created_at', $today)
                ->select(
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(paid_amount) as total_paid'),
                    DB::raw('SUM(remaining) as total_credit'),
                    DB::raw('AVG(total) as average_order')
                )
                ->first();
                
            // This week's sales
            $thisWeekSales = Invoice::where('created_at', '>=', $thisWeekStart)
                ->select(
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(paid_amount) as total_paid'),
                    DB::raw('SUM(remaining) as total_credit'),
                    DB::raw('AVG(total) as average_order')
                )
                ->first();
                
            // This month's sales
            $thisMonthSales = Invoice::where('created_at', '>=', $thisMonthStart)
                ->select(
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(paid_amount) as total_paid'),
                    DB::raw('SUM(remaining) as total_credit'),
                    DB::raw('AVG(total) as average_order')
                )
                ->first();
                
            // Yesterday's sales for comparison
            $yesterdaySales = Invoice::whereDate('created_at', $yesterday)
                ->select(DB::raw('SUM(total) as total_sales'))
                ->first();
                
            // Calculate day-over-day change
            $dayOverDayChange = 0;
            if ($yesterdaySales && $yesterdaySales->total_sales > 0) {
                $dayOverDayChange = round((($todaySales->total_sales - $yesterdaySales->total_sales) / $yesterdaySales->total_sales) * 100, 2);
            }
            
            // Top selling products today
            $topProducts = DB::table('invoice_items')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->whereDate('invoices.created_at', $today)
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.total) as total_sales')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_sales')
                ->limit(5)
                ->get();
                
            // Latest invoices
            $latestInvoices = Invoice::with('customer')
                ->latest()
                ->limit(5)
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => [
                    'today' => $todaySales,
                    'day_over_day_change' => $dayOverDayChange,
                    'this_week' => $thisWeekSales,
                    'this_month' => $thisMonthSales,
                    'top_products' => $topProducts,
                    'latest_invoices' => $latestInvoices
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating dashboard summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 