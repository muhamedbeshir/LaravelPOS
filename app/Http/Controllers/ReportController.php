<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PriceType;
use App\Models\Salary;
use App\Models\SalaryPayment;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Supplier;
use App\Services\CacheService;
use App\Services\SalaryExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-sales-report' => ['only' => ['salesReport']],
        'permission:view-purchases-report' => ['only' => ['purchasesReport']],
        'permission:view-customers-report' => ['only' => ['customersReport']],
        'permission:view-suppliers-report' => ['only' => ['suppliersReport']],
        'permission:view-inventory-report' => ['only' => ['inventoryReport']],
    ];

    public function salesByPeriod(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:daily,weekly,monthly,yearly'
        ]);

        $query = Invoice::whereBetween('created_at', [
            Carbon::parse($request->start_date)->startOfDay(),
            Carbon::parse($request->end_date)->endOfDay()
        ]);

        switch ($request->group_by) {
            case 'daily':
                $sales = $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('AVG(total) as average_order_value')
                )->groupBy('date')->get();
                break;

            case 'weekly':
                $sales = $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('WEEK(created_at) as week'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('AVG(total) as average_order_value')
                )->groupBy('year', 'week')->get();
                break;

            case 'monthly':
                $sales = $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('AVG(total) as average_order_value')
                )->groupBy('year', 'month')->get();
                break;

            case 'yearly':
                $sales = $query->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total) as total_sales'),
                    DB::raw('SUM(profit) as total_profit'),
                    DB::raw('AVG(total) as average_order_value')
                )->groupBy('year')->get();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function salesByProduct(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $query = DB::table('invoice_items')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);

        if ($request->category_id) {
            $query->where('products.category_id', $request->category_id);
        }

        $sales = $query->select(
            'products.id',
            'products.name',
            DB::raw('SUM(invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(invoice_items.total) as total_sales'),
            DB::raw('SUM(invoice_items.profit) as total_profit'),
            DB::raw('COUNT(DISTINCT invoices.id) as number_of_orders')
        )
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('total_sales')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function profitAnalysis(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $profitByCategory = DB::table('invoice_items')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ])
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(invoice_items.profit) as total_profit'),
                DB::raw('SUM(invoice_items.total) as total_sales'),
                DB::raw('(SUM(invoice_items.profit) / SUM(invoice_items.total)) * 100 as profit_margin')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_profit')
            ->get();

        $profitTrend = Invoice::whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(profit) as daily_profit'),
                DB::raw('SUM(total) as daily_sales'),
                DB::raw('(SUM(profit) / SUM(total)) * 100 as profit_margin')
            )
            ->groupBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'profit_by_category' => $profitByCategory,
                'profit_trend' => $profitTrend
            ]
        ]);
    }

    public function customerAnalysis(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $customerMetrics = Invoice::with('customer')
            ->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ])
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_spent'),
                DB::raw('AVG(total) as average_order_value'),
                DB::raw('MAX(created_at) as last_order_date')
            )
            ->groupBy('customer_id')
            ->having('total_orders', '>', 0)
            ->orderByDesc('total_spent')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customerMetrics
        ]);
    }

    public function deliveryAnalysis(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $deliveryMetrics = DB::table('delivery_schedules')
            ->join('delivery_zones', 'delivery_schedules.delivery_zone_id', '=', 'delivery_zones.id')
            ->whereBetween('delivery_schedules.created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ])
            ->select(
                'delivery_zones.name as zone_name',
                DB::raw('COUNT(*) as total_deliveries'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as successful_deliveries'),
                DB::raw('AVG(delivery_cost) as average_delivery_cost'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_delivery_time)) as average_delivery_time')
            )
            ->groupBy('delivery_zones.id', 'delivery_zones.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deliveryMetrics
        ]);
    }

    public function allInvoices(Request $request)
    {
        // Default date range if none provided
        $startDate = $request->input('start_date', '2025-03-01');
        $endDate = $request->input('end_date', '2025-03-31');
        
        $query = Invoice::with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc');
            
        // Apply date filter
        $query->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate);
        
        // Apply other filters
        if ($request->filled('invoice_type')) {
            $query->where('type', $request->invoice_type);
        }
        
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Log the query for debugging
        \Log::info('Invoice Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // Clone the query for summary calculations before pagination
        $summaryQuery = clone $query;
        $invoices = $query->paginate(20);
        
        // Calculate summary data
        $summaryData = $summaryQuery->reorder()->selectRaw('COUNT(*) as total_invoices, SUM(total) as total_sales, SUM(profit) as total_profit')
                                 ->first();
        
        $totalInvoices = $invoices->total();
        $totalSales = $summaryData->total_sales ?? 0;
        $totalProfit = $summaryData->total_profit ?? 0;
        
        // Calculate average
        $avgInvoiceValue = $totalInvoices > 0 ? $totalSales / $totalInvoices : 0;
        
        // Build summary array with all keys defined
        $summary = [
            'total_invoices' => $totalInvoices,
            'total_sales' => $totalSales,
            'total_profit' => $totalProfit,
            'average_invoice_value' => $avgInvoiceValue
        ];
        
        $customers = Customer::orderBy('name')->get();
        
        return view('reports.all-invoices', [
            'invoices' => $invoices,
            'summary' => $summary,
            'customers' => $customers,
            'request' => $request
        ]);
    }
    
    public function exportAllInvoices(Request $request)
    {
        // Default date range if none provided
        $startDate = $request->input('start_date', '2025-03-01');
        $endDate = $request->input('end_date', '2025-03-31');
        
        $query = Invoice::with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc');
            
        // Apply date filter
        $query->whereDate('created_at', '>=', $startDate)
              ->whereDate('created_at', '<=', $endDate);
        
        // Apply other filters
        if ($request->filled('invoice_type')) {
            $query->where('type', $request->invoice_type);
        }
        
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        $invoices = $query->get();
        
        // Create CSV content
        $filename = 'all_invoices_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM to fix Arabic in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, [
                'رقم الفاتورة',
                'التاريخ',
                'العميل',
                'نوع الفاتورة',
                'نوع الطلب',
                'الحالة',
                'المجموع',
                'المدفوع',
                'المتبقي',
                'الربح',
            ]);
            
            // Add rows
            foreach ($invoices as $invoice) {
                $invoiceType = $invoice->type == 'cash' ? 'كاش' : 'آجل';
                $orderType = $invoice->order_type == 'takeaway' ? 'تيك أواي' : 'دليفري';
                
                $status = '';
                if ($invoice->status == 'completed') {
                    $status = 'مكتملة';
                } elseif ($invoice->status == 'pending') {
                    $status = 'معلقة';
                } elseif ($invoice->status == 'canceled') {
                    $status = 'ملغية';
                }
                
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->created_at->format('Y-m-d H:i'),
                    $invoice->customer->name,
                    $invoiceType,
                    $orderType,
                    $status,
                    $invoice->total,
                    $invoice->paid_amount,
                    $invoice->remaining_amount,
                    $invoice->profit,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Comprehensive sales analysis report
     */
    public function salesAnalysis(Request $request)
    {
        // Get categories and customers for the filter dropdowns
        $categories = \App\Models\Category::orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        // Default summary with zero values to avoid undefined index errors
        $defaultSummary = [
            'invoice_count' => 0,
            'total_sales' => 0,
            'total_profit' => 0,
            'total_items' => 0,
            'average_invoice' => 0,
            'profit_margin' => 0,
            'daily_sales' => [],
            'top_products' => []
        ];
        
        // Enhanced debugging information
        $debug = [
            'request_method' => $request->method(),
            'has_start_date' => $request->has('start_date'),
            'has_query_params' => !empty($request->getQueryString()),
            'start_date_value' => $request->input('start_date'),
            'end_date_value' => $request->input('end_date'),
            'generate_report' => $request->input('generate_report'),
            'report_type' => $request->input('report_type', 'period'),
            'all_params' => $request->all()
        ];
        
        // Check if we should just show the form (first page load or reset)
        $shouldShowFormOnly = !$request->filled('generate_report');
        
        $debug['should_show_form_only'] = $shouldShowFormOnly;
        
        // If we should just show the form
        if ($shouldShowFormOnly) {
            // Pass the default summary to avoid undefined index errors
            $summary = $defaultSummary;
            return view('reports.sales-analysis', compact('categories', 'customers', 'summary', 'debug'));
        }

        try {
            // Different handling based on report type
            $reportType = $request->input('report_type', 'period');
            
            // Use the optimized batch processing method for period reports
            if ($reportType == 'period') {
                // Get aggregated data with batch processing
                $data = $this->getAggregatedSalesData($request);
                
                // Extract data for the view
                $summary = $data['summary'];
                // Ensure all required keys exist in summary
                $summary = array_merge($defaultSummary, $summary);
                
                $chartData = [
                    'labels' => collect($data['daily_sales'])->pluck('date')->toArray(),
                    'sales' => collect($data['daily_sales'])->pluck('total_sales')->toArray(),
                    'profit' => collect($data['daily_sales'])->pluck('total_profit')->toArray(),
                ];
                $topProducts = $data['top_products'];
                
                return view('reports.sales-analysis', compact(
                    'categories', 
                    'customers', 
                    'summary', 
                    'chartData', 
                    'topProducts'
                ));
            } elseif ($reportType == 'day') {
                return $this->specificDayReport($request, $categories, $customers, $defaultSummary);
            } elseif ($reportType == 'comparison') {
                return $this->comparisonReport($request, $categories, $customers, $defaultSummary);
            } else {
                return $this->periodReport($request, $categories, $customers, $defaultSummary);
            }
        } catch (\Exception $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Error in sales analysis report: ' . $e->getMessage());
            
            // Add the error to debug information for display
            $debug['error'] = $e->getMessage();
            $debug['stack_trace'] = $e->getTraceAsString();
            
            // Return view with default summary to avoid errors
            $summary = $defaultSummary;
            
            return view('reports.sales-analysis', compact('categories', 'customers', 'summary', 'debug'))
                ->with('error', 'An error occurred while generating the report: ' . $e->getMessage());
        }
    }

    /**
     * Handle period-based sales report
     */
    private function periodReport($request, $categories, $customers, $defaultSummary)
    {
        $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'daily');
        
        // Debug info
        $debug = [
            'method' => 'periodReport',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groupBy' => $groupBy,
            'request_data' => $request->all()
        ];
        
        // Get cached sales report data
        $customerId = $request->filled('customer_id') ? $request->input('customer_id') : null;
        $invoiceType = $request->filled('invoice_type') ? $request->input('invoice_type') : null;
        
        $salesData = CacheService::getSalesReportData($startDate, $endDate, $groupBy, $customerId, $invoiceType);
        
        // Extract data from the cached results
        $summary = $salesData['summary'];
        // Ensure all required keys exist in summary
        $summary = array_merge($defaultSummary, $summary);
        
        $salesByDay = $salesData['salesByTime'];
        
        $debug['salesByDay_count'] = count($salesByDay);
        
        // Get chart data
        if ($groupBy === 'weekly') {
            $chartData = [
                'labels' => $salesByDay->pluck('week_label')->toArray(),
                'sales' => $salesByDay->pluck('total_sales')->toArray(),
                'profit' => $salesByDay->pluck('total_profit')->toArray(),
            ];
        } elseif ($groupBy === 'monthly') {
            $chartData = [
                'labels' => $salesByDay->pluck('month_name')->toArray(),
                'sales' => $salesByDay->pluck('total_sales')->toArray(),
                'profit' => $salesByDay->pluck('total_profit')->toArray(),
            ];
        } else {
            $chartData = [
                'labels' => $salesByDay->pluck('date')->toArray(),
                'sales' => $salesByDay->pluck('total_sales')->toArray(),
                'profit' => $salesByDay->pluck('total_profit')->toArray(),
            ];
        }
        
        // Ensure chart data is never empty
        if (empty($chartData['labels'])) {
            $today = now()->format('Y-m-d');
            $chartData = [
                'labels' => [$today],
                'sales' => [0],
                'profit' => [0],
            ];
        }
        
        // Get top products with caching
        $topProducts = $this->getTopProducts($request, $startDate, $endDate);
        $debug['topProducts_count'] = count($topProducts);
        
        return view('reports.sales-analysis', compact(
            'categories', 
            'customers', 
            'summary', 
            'salesByDay', 
            'chartData', 
            'topProducts',
            'debug'
        ));
    }

    /**
     * Handle specific day sales report
     */
    private function specificDayReport($request, $categories, $customers, $defaultSummary)
    {
        $day = $request->input('specific_day', now()->format('Y-m-d'));
        
        // Base query for invoices in the specified day
        $invoiceQuery = \App\Models\Invoice::whereDate('created_at', $day);
        
        // Apply filters
        if ($request->filled('customer_id')) {
            $invoiceQuery->where('customer_id', $request->input('customer_id'));
        }
        
        if ($request->filled('invoice_type')) {
            $invoiceQuery->where('type', $request->input('invoice_type'));
        }
        
        // Get sales summary
        $summaryData = $invoiceQuery->select(
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('SUM(profit) as total_profit'),
            DB::raw('AVG(total) as average_invoice')
        )->first();
        
        // Get total items sold from invoice_items table
        $totalItems = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereDate('invoices.created_at', $day);
            
        if ($request->filled('customer_id')) {
            $totalItems->where('invoices.customer_id', $request->input('customer_id'));
        }
        
        if ($request->filled('invoice_type')) {
            $totalItems->where('invoices.type', $request->input('invoice_type'));
        }
        
        $totalItemsCount = $totalItems->sum('invoice_items.quantity');
        
        // Create summary with fetched data
        $summary = [
            'invoice_count' => $summaryData->invoice_count ?? 0,
            'total_sales' => $summaryData->total_sales ?? 0,
            'total_profit' => $summaryData->total_profit ?? 0,
            'total_items' => $totalItemsCount ?? 0,
            'average_invoice' => $summaryData->average_invoice ?? 0,
            'profit_margin' => ($summaryData->total_sales > 0) ? (($summaryData->total_profit / $summaryData->total_sales) * 100) : 0
        ];
        
        // Ensure all required keys exist in summary
        $summary = array_merge($defaultSummary, $summary);
        
        // Get hourly breakdown for the day
        $salesByHour = $invoiceQuery->select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('SUM(profit) as total_profit')
        )->groupBy('hour')->orderBy('hour')->get();
        
        // Format hour labels
        $salesByHour->transform(function($item) {
            $item->hour_label = sprintf('%02d:00', $item->hour);
            return $item;
        });
        
        // Prepare chart data
        $chartData = [
            'labels' => $salesByHour->pluck('hour_label')->toArray(),
            'sales' => $salesByHour->pluck('total_sales')->toArray(),
            'profit' => $salesByHour->pluck('total_profit')->toArray(),
        ];
        
        // Get top products for the day
        $topProducts = $this->getTopProducts($request, $day, $day);
        
        $salesByDay = $salesByHour; // For the detailed table
        
        return view('reports.sales-analysis', compact(
            'categories', 
            'customers', 
            'summary', 
            'salesByDay', 
            'chartData', 
            'topProducts'
        ));
    }

    /**
     * Handle comparison report between two periods
     */
    private function comparisonReport($request, $categories, $customers, $defaultSummary)
    {
        // Period 1
        $period1Start = $request->input('period1_start', now()->subMonths(2)->format('Y-m-d'));
        $period1End = $request->input('period1_end', now()->subMonth()->subDay()->format('Y-m-d'));
        
        // Period 2
        $period2Start = $request->input('period2_start', now()->subMonth()->format('Y-m-d'));
        $period2End = $request->input('period2_end', now()->format('Y-m-d'));
        
        // Base filters to apply to both periods
        $baseFilters = function($query) use ($request) {
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->input('customer_id'));
            }
            
            if ($request->filled('invoice_type')) {
                $query->where('type', $request->input('invoice_type'));
            }
            
            if ($request->filled('category_id')) {
                $query->whereHas('items.product', function($q) use ($request) {
                    $q->where('category_id', $request->input('category_id'));
                });
            }
        };
        
        // Get Period 1 summary
        $period1Query = \App\Models\Invoice::whereBetween('created_at', [
            Carbon::parse($period1Start)->startOfDay(),
            Carbon::parse($period1End)->endOfDay()
        ]);
        $baseFilters($period1Query);
        
        $period1Summary = $period1Query->select(
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('SUM(profit) as total_profit'),
            DB::raw('AVG(total) as average_invoice')
        )->first();
        
        // Get Period 2 summary
        $period2Query = \App\Models\Invoice::whereBetween('created_at', [
            Carbon::parse($period2Start)->startOfDay(),
            Carbon::parse($period2End)->endOfDay()
        ]);
        $baseFilters($period2Query);
        
        $period2Summary = $period2Query->select(
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('SUM(total) as total_sales'),
            DB::raw('SUM(profit) as total_profit'),
            DB::raw('AVG(total) as average_invoice')
        )->first();

        // Get total items for period 2
        $totalItemsBaseQuery = function($query) use ($request) {
            if ($request->filled('customer_id')) {
                $query->where('invoices.customer_id', $request->input('customer_id'));
            }
            
            if ($request->filled('invoice_type')) {
                $query->where('invoices.type', $request->input('invoice_type'));
            }
            
            if ($request->filled('category_id')) {
                $query->where('products.category_id', $request->input('category_id'));
            }
        };

        // Get total items for period 2
        $period2Items = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->whereBetween('invoices.created_at', [
                Carbon::parse($period2Start)->startOfDay(),
                Carbon::parse($period2End)->endOfDay()
            ]);
        
        $totalItemsBaseQuery($period2Items);
        $totalItemsCount = $period2Items->sum('invoice_items.quantity');
        
        // Calculate changes
        $salesChange = ($period2Summary->total_sales ?? 0) - ($period1Summary->total_sales ?? 0);
        $salesChangePercent = ($period1Summary->total_sales > 0) 
            ? (($salesChange / $period1Summary->total_sales) * 100) 
            : 0;
            
        $profitChange = ($period2Summary->total_profit ?? 0) - ($period1Summary->total_profit ?? 0);
        $profitChangePercent = ($period1Summary->total_profit > 0) 
            ? (($profitChange / $period1Summary->total_profit) * 100) 
            : 0;
            
        $invoicesChange = ($period2Summary->invoice_count ?? 0) - ($period1Summary->invoice_count ?? 0);
        $invoicesChangePercent = ($period1Summary->invoice_count > 0) 
            ? (($invoicesChange / $period1Summary->invoice_count) * 100) 
            : 0;
            
        $averageChange = ($period2Summary->average_invoice ?? 0) - ($period1Summary->average_invoice ?? 0);
        $averageChangePercent = ($period1Summary->average_invoice > 0) 
            ? (($averageChange / $period1Summary->average_invoice) * 100) 
            : 0;
        
        // Prepare comparison data
        $comparison = [
            'period1' => [
                'start' => $period1Start,
                'end' => $period1End,
                'sales' => $period1Summary->total_sales ?? 0,
                'profit' => $period1Summary->total_profit ?? 0,
                'invoices' => $period1Summary->invoice_count ?? 0,
                'average' => $period1Summary->average_invoice ?? 0,
            ],
            'period2' => [
                'start' => $period2Start,
                'end' => $period2End,
                'sales' => $period2Summary->total_sales ?? 0,
                'profit' => $period2Summary->total_profit ?? 0,
                'invoices' => $period2Summary->invoice_count ?? 0,
                'average' => $period2Summary->average_invoice ?? 0,
            ],
            'change' => [
                'sales' => $salesChange,
                'sales_percent' => $salesChangePercent,
                'profit' => $profitChange,
                'profit_percent' => $profitChangePercent,
                'invoices' => $invoicesChange,
                'invoices_percent' => $invoicesChangePercent,
                'average' => $averageChange,
                'average_percent' => $averageChangePercent,
            ]
        ];
        
        // For overall summary (use period 2 data)
        $summary = [
            'invoice_count' => $period2Summary->invoice_count ?? 0,
            'total_sales' => $period2Summary->total_sales ?? 0,
            'total_profit' => $period2Summary->total_profit ?? 0,
            'total_items' => $totalItemsCount ?? 0,
            'average_invoice' => $period2Summary->average_invoice ?? 0,
            'profit_margin' => ($period2Summary->total_sales > 0) ? (($period2Summary->total_profit / $period2Summary->total_sales) * 100) : 0
        ];
        
        // Ensure all required keys exist in summary
        $summary = array_merge($defaultSummary, $summary);
        
        // Chart data comparing both periods
        $chartData = [
            'labels' => ['المبيعات', 'الربح', 'عدد الفواتير', 'متوسط الفاتورة'],
            'period1' => [
                $period1Summary->total_sales ?? 0,
                $period1Summary->total_profit ?? 0,
                $period1Summary->invoice_count ?? 0,
                $period1Summary->average_invoice ?? 0
            ],
            'period2' => [
                $period2Summary->total_sales ?? 0,
                $period2Summary->total_profit ?? 0,
                $period2Summary->invoice_count ?? 0,
                $period2Summary->average_invoice ?? 0
            ]
        ];
        
        // Get top products for period 2 (current period)
        $topProducts = $this->getTopProducts($request, $period2Start, $period2End);
        
        return view('reports.sales-analysis', compact(
            'categories',
            'customers',
            'summary',
            'comparison',
            'chartData',
            'topProducts'
        ));
    }

    /**
     * Get top products based on filters
     */
    private function getTopProducts($request, $startDate, $endDate)
    {
        $orderBy = $request->input('products_order', 'sales');
        $limit = $request->input('products_limit', 10);
        $categoryId = $request->input('category_id');
        $customerId = $request->input('customer_id');
        $invoiceType = $request->input('invoice_type');
        
        // عدم استخدام التخزين المؤقت لتجنب مشاكل البيانات القديمة أثناء تصحيح الأخطاء
        try {
            // استعلام مباشر لجلب المنتجات الأكثر مبيعًا
                $query = DB::table('invoice_items')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->whereBetween('invoices.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                ])
                ->where('invoices.status', '=', 'completed');
                
            // تطبيق الفلاتر
                if ($customerId) {
                    $query->where('invoices.customer_id', $customerId);
                }
                
                if ($invoiceType) {
                    $query->where('invoices.type', $invoiceType);
                }
                
                if ($categoryId) {
                    $query->where('products.category_id', $categoryId);
                }
                
            // تسجيل استعلام SQL
            \Log::info('Top Products SQL', [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // استعلام التجميع
            $groupQuery = clone $query;
            $products = $groupQuery->select(
                    'products.id',
                    'products.name',
                    'categories.name as category_name',
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.total_price) as total_sales'),
                    DB::raw('SUM(invoice_items.profit) as total_profit'),
                    DB::raw('COUNT(DISTINCT invoices.id) as number_of_orders')
                )
                ->groupBy('products.id', 'products.name', 'categories.name');
                
            // ترتيب حسب المقياس المحدد
                switch ($orderBy) {
                    case 'profit':
                    $products->orderByDesc('total_profit');
                        break;
                    case 'quantity':
                    $products->orderByDesc('total_quantity');
                        break;
                    case 'sales':
                    default:
                    $products->orderByDesc('total_sales');
                        break;
                }
                
            // تطبيق الحد إذا لم يكن 'all'
                if ($limit !== 'all') {
                $products->limit((int)$limit);
                }
                
            $productData = $products->get();
            
            // إذا لم نحصل على أي منتجات، دعنا نتحقق من البيانات الخام
            if ($productData->isEmpty()) {
                $rawData = $query->select('products.*', 'invoice_items.*', 'invoices.status')
                    ->limit(5)
                    ->get();
                
                \Log::info('Raw data check for products', [
                    'data' => $rawData,
                    'count' => $rawData->count()
                ]);
                
                // طريقة بديلة باستخدام eloquent
                $eloquentProducts = \App\Models\Product::whereHas('invoiceItems', function($q) use ($startDate, $endDate) {
                    $q->whereHas('invoice', function($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ])
                        ->where('status', 'completed');
                    });
                })
                ->with(['invoiceItems' => function($q) use ($startDate, $endDate) {
                    $q->whereHas('invoice', function($q2) use ($startDate, $endDate) {
                        $q2->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay()
                        ])
                        ->where('status', 'completed');
                    });
                }])
                ->get();
                
                // تحويل المنتجات إلى التنسيق المطلوب
                $productData = $eloquentProducts->map(function($product) {
                    $totalQuantity = $product->invoiceItems->sum('quantity');
                    $totalSales = $product->invoiceItems->sum('total_price');
                    $totalProfit = $product->invoiceItems->sum('profit');
                    
                    return (object)[
                        'id' => $product->id,
                        'name' => $product->name,
                        'category_name' => $product->category ? $product->category->name : 'بدون فئة',
                        'total_quantity' => $totalQuantity,
                        'total_sales' => $totalSales,
                        'total_profit' => $totalProfit,
                        'number_of_orders' => $product->invoiceItems->pluck('invoice_id')->unique()->count(),
                        'profit_margin' => $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0
                    ];
                })
                ->sortByDesc($orderBy == 'profit' ? 'total_profit' : ($orderBy == 'quantity' ? 'total_quantity' : 'total_sales'))
                ->values();
                
                \Log::info('Using Eloquent method', [
                    'product_count' => $productData->count()
                ]);
            } else {
                // حساب هامش الربح لكل منتج
                $productData->transform(function($product) {
                    $product->profit_margin = ($product->total_sales > 0) 
                        ? (($product->total_profit / $product->total_sales) * 100) 
                        : 0;
                    return $product;
                });
            }
                
            return $productData;
        } catch (\Exception $e) {
            \Log::error('Error in getTopProducts: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // إذا حدث خطأ، نعيد مصفوفة فارغة لتجنب توقف التطبيق
            return collect([]);
        }
    }

    /**
     * Export sales analysis to Excel
     */
    public function exportSalesAnalysis(Request $request)
    {
        // Create and return an Excel export based on the sales analysis report
        // This method should handle exporting the data to Excel format
        // For simplicity, we're not implementing the actual export functionality here
    }

    /**
     * Get aggregated sales data with optimized batch processing
     * 
     * @param Request $request
     * @return array Processed sales data including summary, daily sales, and top products
     */
    private function getAggregatedSalesData(Request $request)
    {
        // Parse filter parameters
        $startDate = $request->filled('start_date') 
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay() 
            : Carbon::now()->subDays(30)->startOfDay();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay() 
            : Carbon::now()->endOfDay();
            
        $categoryId = $request->input('category_id');
        $customerId = $request->input('customer_id');
        $invoiceType = $request->input('invoice_type');
        
                 // أولاً، قم بجلب قائمة الفواتير المكتملة التي تقع ضمن الفترة المحددة
         $invoiceQuery = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '=', 'completed');
            
        // تطبيق فلاتر إضافية
        if ($customerId) {
            $invoiceQuery->where('customer_id', $customerId);
        }
        
        if ($invoiceType) {
            $invoiceQuery->where('type', $invoiceType);
        }
        
        // إحصائيات ملخص المبيعات
        $summary = [
            'total_sales' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'total_invoices' => 0,
            'average_invoice' => 0,
            'total_items' => 0,
            'invoice_count' => 0,
            'profit_margin' => 0,
            'avg_invoice_value' => 0,
            'product_count' => 0,
            'total_discount' => 0,
        ];
        
        // بيانات المبيعات اليومية
        $dailySales = [];
        $productSales = [];
        
        // تهيئة بنية بيانات المبيعات اليومية للفترة المحددة
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dailySales[$dateString] = [
                'date' => $dateString,
                'total_sales' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'invoice_count' => 0,
                'total_orders' => 0, // لتوافق مع القالب
            ];
            $currentDate->addDay();
        }
        
        // بديل 1: استخدم طريقة مباشرة للحصول على بيانات المنتجات من قاعدة البيانات
        // جلب عناصر الفواتير مباشرة من جدول invoice_items مع المنتجات والفئات المرتبطة
                 $invoiceItemsQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', '=', 'completed');
            
        // تطبيق نفس الفلاتر على استعلام العناصر
        if ($customerId) {
            $invoiceItemsQuery->where('invoices.customer_id', $customerId);
        }
        
        if ($invoiceType) {
            $invoiceItemsQuery->where('invoices.type', $invoiceType);
        }
        
        if ($categoryId) {
            $invoiceItemsQuery->where('products.category_id', $categoryId);
        }
        
                 // استخدام التفاصيل النهائية للاستعلام مع معالجة البيانات الخاصة بالمنتجات
         try {
             // نطبع استعلام SQL للتصحيح
             \Log::info('SQL Query', [
                 'query' => $invoiceItemsQuery
                     ->select(
                         'invoices.id as invoice_id',
                         'invoices.created_at',
                         'invoice_items.product_id',
                         'products.name as product_name',
                         'products.barcode',
                         'categories.id as category_id',
                         'categories.name as category_name',
                         'invoice_items.quantity',
                         'invoice_items.unit_price',
                         'invoice_items.total_price',
                         'invoice_items.unit_cost',
                         'invoice_items.profit'
                     )
                     ->toSql(),
                 'bindings' => $invoiceItemsQuery->getBindings()
             ]);
                
             // الاستعلام الرئيسي
             $invoiceItems = $invoiceItemsQuery
                 ->select(
                     'invoices.id as invoice_id',
                     'invoices.created_at',
                     'invoice_items.product_id',
                     'products.name as product_name',
                     'products.barcode',
                     'categories.id as category_id',
                     'categories.name as category_name',
                     'invoice_items.quantity',
                     'invoice_items.unit_price',
                     'invoice_items.total_price',
                     'invoice_items.unit_cost',
                     'invoice_items.profit'
                 )
                 ->get();
                 
             // إذا لم يتم العثور على عناصر، نحاول استخدام طريقة بديلة
             if ($invoiceItems->isEmpty()) {
                 \Log::info('Empty invoice items. Trying alternative method.');
                 
                 // طريقة بديلة باستخدام اتصال مباشر بالجداول
                 $itemsData = DB::table('invoice_items')
                     ->join('products', 'invoice_items.product_id', '=', 'products.id')
                     ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                     ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                     ->where('invoices.status', '=', 'completed')
                     ->whereBetween('invoices.created_at', [$startDate, $endDate])
                     ->select(
                         'invoices.id as invoice_id',
                         'invoices.created_at',
                         'invoice_items.product_id',
                         'products.name as product_name',
                         'products.barcode',
                         'categories.id as category_id',
                         'categories.name as category_name',
                         'invoice_items.quantity',
                         'invoice_items.unit_price',
                         'invoice_items.total_price',
                         'invoice_items.unit_cost',
                         'invoice_items.profit'
                     )
                     ->get();
                     
                 $invoiceItems = $itemsData;
                 \Log::info('Alternative method found items: ' . $invoiceItems->count());
             }
         } catch (\Exception $e) {
             \Log::error('Error getting invoice items: ' . $e->getMessage());
             $invoiceItems = collect([]); // مجموعة فارغة في حالة حدوث خطأ
         }
        
                 // جمع البيانات الإجمالية للفواتير
         $invoiceSummary = $invoiceQuery->select(
             DB::raw('COUNT(DISTINCT id) as invoice_count'),
             DB::raw('SUM(total) as total_sales'),
             DB::raw('SUM(profit) as total_profit')
         )->first();
         
         // تحديث الملخص بالبيانات المجمعة
         if ($invoiceSummary) {
             $summary['total_sales'] = $invoiceSummary->total_sales ?? 0;
             $summary['total_profit'] = $invoiceSummary->total_profit ?? 0;
             $summary['total_discount'] = 0; // لا يوجد حقل discount في جدول invoices
             $summary['invoice_count'] = $invoiceSummary->invoice_count ?? 0;
             $summary['total_invoices'] = $invoiceSummary->invoice_count ?? 0;
         }
        
                 // تجميع المبيعات حسب التاريخ - استخدام استعلام منفصل لتجنب مشاكل التجميع
         $dailySummary = DB::table('invoices')
             ->select(
                 DB::raw('DATE(created_at) as date'),
                 DB::raw('COUNT(*) as total_orders'),
                 DB::raw('SUM(total) as total_sales'),
                 DB::raw('SUM(profit) as total_profit')
             )
             ->whereBetween('created_at', [$startDate, $endDate])
             ->where('status', '=', 'completed')
             ->groupBy(DB::raw('DATE(created_at)'))
             ->get();
            
        foreach ($dailySummary as $day) {
            if (isset($dailySales[$day->date])) {
                $dailySales[$day->date]['total_sales'] = $day->total_sales;
                $dailySales[$day->date]['total_profit'] = $day->total_profit;
                $dailySales[$day->date]['total_orders'] = $day->total_orders;
                $dailySales[$day->date]['invoice_count'] = $day->total_orders;
            }
        }
        
        // تجميع المبيعات حسب المنتج
        $processedInvoices = [];
        
        // تأكد من وجود بيانات قبل المعالجة
        if (is_object($invoiceItems) && count($invoiceItems) > 0) {
            \Log::info('Processing invoice items', [
                'count' => count($invoiceItems),
                'first_item' => isset($invoiceItems[0]) ? json_encode($invoiceItems[0]) : 'No items found'
            ]);
            
            foreach ($invoiceItems as $item) {
                if (!is_object($item) || !isset($item->product_id)) {
                    \Log::warning('Invalid invoice item', [
                        'item' => is_object($item) ? get_object_vars($item) : $item
                    ]);
                    continue;
                }
                
                    $productId = $item->product_id;
                $invoiceId = $item->invoice_id;
                $invoiceDate = Carbon::parse($item->created_at)->format('Y-m-d');
                
                // تسجيل المنتج إذا لم يكن موجودًا بالفعل
                    if (!isset($productSales[$productId])) {
                    // جلب اسم المنتج بشكل صريح من جدول المنتجات
                    $productDetails = DB::table('products')->where('id', $productId)->first();
                    $categoryDetails = null;
                    
                    // إذا وجدنا المنتج، نحاول الحصول على الفئة
                    if ($productDetails && $productDetails->category_id) {
                        $categoryDetails = DB::table('categories')->where('id', $productDetails->category_id)->first();
                    }
                    
                        $productSales[$productId] = [
                        'id' => $productId,
                            'product_id' => $productId,
                        'name' => $productDetails ? $productDetails->name : ($item->product_name ?? 'منتج غير معروف'),
                        'category_name' => $categoryDetails ? $categoryDetails->name : ($item->category_name ?? 'بدون فئة'),
                        'category_id' => $productDetails ? $productDetails->category_id : ($item->category_id ?? null),
                        'barcode' => $productDetails ? $productDetails->barcode : ($item->barcode ?? ''),
                        'total_quantity' => 0,
                            'total_sales' => 0,
                            'total_profit' => 0,
                        'number_of_orders' => 0,
                        'invoices' => []
                        ];
                    }
                    
                // تحديث إحصائيات المنتج (مع التأكد من وجود الحقول)
                if (isset($item->quantity)) {
                    $productSales[$productId]['total_quantity'] += floatval($item->quantity);
                }
                
                if (isset($item->total_price)) {
                    $productSales[$productId]['total_sales'] += floatval($item->total_price);
                }
                
                if (isset($item->profit)) {
                    $productSales[$productId]['total_profit'] += floatval($item->profit);
                }
                
                // إضافة الفاتورة إلى قائمة الفواتير للمنتج إذا لم تكن موجودة بالفعل
                if (!in_array($invoiceId, $productSales[$productId]['invoices'])) {
                    $productSales[$productId]['invoices'][] = $invoiceId;
                    $productSales[$productId]['number_of_orders']++;
                }
                
                // إضافة الكمية إلى إجمالي العناصر
                if (isset($item->quantity)) {
                    $summary['total_items'] += floatval($item->quantity);
                }
            }
        } else {
            \Log::warning('No invoice items to process', [
                'invoiceItems' => $invoiceItems
            ]);
            
            // محاولة بديلة للحصول على البيانات باستخدام استعلام مباشر
            $directItems = DB::table('invoice_items')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate])
                ->where('invoices.status', 'completed')
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    'categories.name as category_name',
                    'invoice_items.quantity',
                    'invoice_items.total_price',
                    'invoice_items.profit',
                    'invoice_items.invoice_id',
                    'invoices.created_at'
                )
                ->get();
                
            \Log::info('Direct query results', [
                'count' => $directItems->count()
            ]);
            
            // معالجة العناصر التي تم الحصول عليها مباشرة
            foreach ($directItems as $item) {
                $productId = $item->product_id;
                $invoiceId = $item->invoice_id;
                
                if (!isset($productSales[$productId])) {
                    $productSales[$productId] = [
                        'id' => $productId,
                        'product_id' => $productId,
                        'name' => $item->product_name ?? 'منتج غير معروف',
                        'category_name' => $item->category_name ?? 'بدون فئة',
                        'total_quantity' => 0,
                        'total_sales' => 0,
                        'total_profit' => 0,
                        'number_of_orders' => 0,
                        'invoices' => []
                    ];
                }
                
                $productSales[$productId]['total_quantity'] += floatval($item->quantity);
                $productSales[$productId]['total_sales'] += floatval($item->total_price);
                $productSales[$productId]['total_profit'] += floatval($item->profit);
                
                if (!in_array($invoiceId, $productSales[$productId]['invoices'])) {
                    $productSales[$productId]['invoices'][] = $invoiceId;
                    $productSales[$productId]['number_of_orders']++;
                }
                
                $summary['total_items'] += floatval($item->quantity);
            }
        }
        
        // حساب متوسط قيمة الفاتورة
        $summary['average_invoice'] = $summary['invoice_count'] > 0 
            ? $summary['total_sales'] / $summary['invoice_count'] 
            : 0;
        $summary['avg_invoice_value'] = $summary['average_invoice'];
        
        // حساب هامش الربح الإجمالي
        $summary['profit_margin'] = $summary['total_sales'] > 0 
            ? ($summary['total_profit'] / $summary['total_sales']) * 100 
            : 0;
            
        // عدد المنتجات الفريدة التي تم بيعها
        $summary['product_count'] = count($productSales);
        
        // ترتيب المبيعات اليومية حسب التاريخ
        $dailySales = array_values($dailySales);
        
        // حساب هامش الربح لكل منتج وإزالة مصفوفة الفواتير
        foreach ($productSales as &$product) {
            $product['profit_margin'] = $product['total_sales'] > 0 
                ? ($product['total_profit'] / $product['total_sales']) * 100 
                : 0;
                
            // حساب متوسط الكمية
            $product['average_quantity'] = $product['number_of_orders'] > 0
                ? $product['total_quantity'] / $product['number_of_orders']
                : 0;
                
            unset($product['invoices']);
        }
        
        // الحصول على أفضل المنتجات مبيعًا (مع التأكد من وجود المصفوفة وترتيبها)
        $products = collect($productSales);
        
        // تسجيل معلومات التصحيح
        \Log::info('Products before sorting', [
            'count' => $products->count(),
            'first_product' => $products->isNotEmpty() ? $products->first() : 'No products found'
        ]);
        
        // ترتيب المنتجات حسب المبيعات
        $topProducts = $products->sortByDesc('total_sales')->values()->all();
        
        // تسجيل المنتجات النهائية
        \Log::info('Final top products', [
            'count' => count($topProducts),
            'first_product' => !empty($topProducts) ? $topProducts[0] : 'No products found'
        ]);
        
        // حساب مصروفات الرواتب وصافي الأرباح
        $salaryData = SalaryExpenseService::getSalaryExpensesAndNetProfit(
            $summary['total_profit'],
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
        
        // إضافة مصروفات الرواتب وصافي الأرباح إلى الملخص
        $summary['salary_expenses'] = $salaryData['salary_expenses'];
        $summary['net_profit'] = $salaryData['net_profit'];
        
        return [
            'summary' => $summary,
            'daily_sales' => $dailySales,
            'top_products' => $topProducts
        ];
    }

    /**
     * تحليل أرباح المشتريات
     */
    public function purchasesProfitAnalytics(Request $request)
    {
        // الحصول على الفئات والموردين لقوائم التصفية
        $categories = \App\Models\Category::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        
        // قيم افتراضية للملخص لتجنب أخطاء "undefined index"
        $defaultSummary = [
            'purchase_count' => 0,
            'purchase_total' => 0,
            'sales_total' => 0,
            'total_profit' => 0,
            'total_quantity' => 0,
            'profit_margin' => 0,
            'sold_quantity' => 0
        ];
        
        // التحقق مما إذا كان يجب فقط عرض النموذج (تحميل الصفحة الأولى أو إعادة التعيين)
        $shouldShowFormOnly = !$request->filled('generate_report');
        
        // إذا كان يجب فقط عرض النموذج
        if ($shouldShowFormOnly) {
            // تمرير الملخص الافتراضي لتجنب أخطاء "undefined index"
            $summary = $defaultSummary;
            return view('reports.purchases-profit-analytics', compact('categories', 'suppliers', 'summary'));
        }

        try {
            // استخراج معلمات الفلتر
            $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));
            $supplierId = $request->filled('supplier_id') ? $request->input('supplier_id') : null;
            $categoryId = $request->filled('category_id') ? $request->input('category_id') : null;
            
            // بناء استعلام أساسي لفواتير المشتريات
            $purchaseQuery = \App\Models\Purchase::with(['supplier', 'items.product.category'])
                ->whereBetween('purchase_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
                
            // تطبيق فلتر المورد إذا تم تحديده
            if ($supplierId) {
                $purchaseQuery->where('supplier_id', $supplierId);
            }
            
            // تطبيق فلتر الفئة على المنتجات المشتراة
            if ($categoryId) {
                $purchaseQuery->whereHas('items.product', function($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                });
            }
            
            // الحصول على فواتير المشتريات
            $purchases = $purchaseQuery->get();
            
            // جمع معلومات المنتجات وتحليل الأرباح
            $productPerformance = [];
            $supplierPerformance = [];
            
            // إجماليات الملخص
            $summary = [
                'purchase_count' => $purchases->count(),
                'purchase_total' => 0,
                'sales_total' => 0,
                'total_profit' => 0,
                'total_quantity' => 0,
                'sold_quantity' => 0
            ];
            
            // تحليل كل فاتورة مشتريات
            foreach ($purchases as $purchase) {
                // إضافة إجمالي فاتورة المشتريات إلى الإجمالي
                $summary['purchase_total'] += $purchase->total_amount;
                
                // تحليل عناصر الفاتورة
                foreach ($purchase->items as $item) {
                    $productId = $item->product_id;
                    $product = $item->product;
                    
                    // تخطي المنتجات المحذوفة
                    if (!$product) continue;
                    
                    // إنشاء أو تحديث بيانات أداء المنتج
                    if (!isset($productPerformance[$productId])) {
                        $productPerformance[$productId] = [
                            'id' => $productId,
                            'name' => $product->name,
                            'category_name' => $product->category ? $product->category->name : 'بدون فئة',
                            'supplier_name' => $purchase->supplier ? $purchase->supplier->name : 'غير محدد',
                            'supplier_id' => $purchase->supplier_id,
                            'total_quantity' => 0,
                            'purchase_total' => 0,
                            'sales_total' => 0,
                            'total_profit' => 0,
                            'sold_quantity' => 0
                        ];
                    }
                    
                    // إضافة كمية وقيمة المشتريات
                    $productPerformance[$productId]['total_quantity'] += $item->quantity;
                    $productPerformance[$productId]['purchase_total'] += ($item->quantity * $item->purchase_price);
                    
                    // إضافة إلى الإجماليات
                    $summary['total_quantity'] += $item->quantity;
                }
                
                // إضافة بيانات المورد
                $supplierId = $purchase->supplier_id;
                if ($supplierId && !isset($supplierPerformance[$supplierId]) && $purchase->supplier) {
                    $supplierPerformance[$supplierId] = [
                        'id' => $supplierId,
                        'name' => $purchase->supplier->name,
                        'products_count' => 0,
                        'purchase_total' => 0,
                        'sales_total' => 0,
                        'total_profit' => 0
                    ];
                }
                
                if ($supplierId && isset($supplierPerformance[$supplierId])) {
                    $supplierPerformance[$supplierId]['purchase_total'] += $purchase->total_amount;
                }
            }
            
            // الآن نحصل على بيانات المبيعات للمنتجات المشتراة لحساب الأرباح
            $productIds = array_keys($productPerformance);
            
            if (!empty($productIds)) {
                // استعلام المبيعات للمنتجات المشتراة خلال الفترة
                $salesData = DB::table('invoice_items')
                    ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->join('products', 'invoice_items.product_id', '=', 'products.id')
                    ->whereIn('invoice_items.product_id', $productIds)
                    ->whereBetween('invoices.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ])
                    ->where('invoices.status', 'completed')
                    ->select(
                        'invoice_items.product_id',
                        DB::raw('SUM(invoice_items.quantity) as sold_quantity'),
                        DB::raw('SUM(invoice_items.total_price) as sales_total'),
                        DB::raw('SUM(invoice_items.profit) as total_profit')
                    )
                    ->groupBy('invoice_items.product_id')
                    ->get();
                
                // تحديث بيانات أداء المنتج بمعلومات المبيعات
                foreach ($salesData as $sale) {
                    $productId = $sale->product_id;
                    
                    if (isset($productPerformance[$productId])) {
                        $productPerformance[$productId]['sales_total'] = $sale->sales_total;
                        $productPerformance[$productId]['total_profit'] = $sale->total_profit;
                        $productPerformance[$productId]['sold_quantity'] = $sale->sold_quantity;
                        
                        // تحديث إجماليات الملخص
                        $summary['sales_total'] += $sale->sales_total;
                        $summary['total_profit'] += $sale->total_profit;
                        $summary['sold_quantity'] += $sale->sold_quantity;
                        
                        // تحديث بيانات أداء المورد
                        $supplierId = $productPerformance[$productId]['supplier_id'];
                        if ($supplierId && isset($supplierPerformance[$supplierId])) {
                            $supplierPerformance[$supplierId]['sales_total'] += $sale->sales_total;
                            $supplierPerformance[$supplierId]['total_profit'] += $sale->total_profit;
                        }
                    }
                }
            }
            
            // حساب عدد المنتجات لكل مورد وترتيبها
            foreach ($productPerformance as $product) {
                $supplierId = $product['supplier_id'];
                if ($supplierId && isset($supplierPerformance[$supplierId])) {
                    $supplierPerformance[$supplierId]['products_count']++;
                }
            }
            
            // حساب هامش الربح الإجمالي
            $summary['profit_margin'] = $summary['purchase_total'] > 0 
                ? ($summary['total_profit'] / $summary['purchase_total']) * 100 
                : 0;
            
            // حساب هامش الربح لكل مورد
            foreach ($supplierPerformance as &$supplier) {
                $supplier['profit_margin'] = $supplier['purchase_total'] > 0 
                    ? ($supplier['total_profit'] / $supplier['purchase_total']) * 100 
                    : 0;
            }
            
            // تحويل مصفوفات الأداء إلى كائنات مجموعة
            // نقوم بتحويل جميع المصفوفات إلى كائنات قبل إرسالها للعرض
            $topProducts = collect($productPerformance)->map(function($item) {
                return (object) $item;
            })->values();
            
            $supplierPerformance = collect($supplierPerformance)->map(function($item) {
                return (object) $item;
            })->values();
            
            // ترتيب المنتجات حسب المعيار المحدد
            $orderBy = $request->input('products_order', 'purchase_total');
            
            switch ($orderBy) {
                case 'profit':
                    $topProducts = $topProducts->sortByDesc('total_profit')->values();
                    break;
                case 'quantity':
                    $topProducts = $topProducts->sortByDesc('total_quantity')->values();
                    break;
                case 'purchase_total':
                default:
                    $topProducts = $topProducts->sortByDesc('purchase_total')->values();
                    break;
            }
            
            // ترتيب الموردين حسب إجمالي المشتريات
            $supplierPerformance = $supplierPerformance->sortByDesc('purchase_total')->values();
            
            return view('reports.purchases-profit-analytics', compact(
                'categories',
                'suppliers',
                'summary',
                'topProducts',
                'supplierPerformance'
            ));
            
        } catch (\Exception $e) {
            // تسجيل الخطأ
            \Illuminate\Support\Facades\Log::error('Error in purchases profit analysis report: ' . $e->getMessage());
            
            // إرجاع العرض مع رسالة خطأ
            $summary = $defaultSummary;
            
            return view('reports.purchases-profit-analytics', compact('categories', 'suppliers', 'summary'))
                ->with('error', 'حدث خطأ أثناء إنشاء التقرير: ' . $e->getMessage());
        }
    }

    /**
     * Generic helper to build report by payment method (direct + mixed)
     */
    private function buildPaymentMethodReport(string $method, Request $request)
    {
        // 1) Direct invoices of given type
        $directInvoicesQuery = Invoice::with('customer')
            ->with('payments')
            ->where('type', $method);

        // 2) Mixed invoices that include this payment method
        $mixedInvoicesQuery = Invoice::with('customer')
            ->with(['payments' => function($q) use ($method) { $q->where('method', $method); }])
            ->where('type', 'mixed')
            ->whereHas('payments', function($q) use ($method) {
                $q->where('method', $method);
            });

        // Combine using unionAll keeping order
        $invoicesUnion = $directInvoicesQuery->unionAll($mixedInvoicesQuery);

        // We need to order by created_at desc; union loses ordering so wrap it
        $invoices = Invoice::fromSub($invoicesUnion, 'invoices')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate total amount for the method
        $totalDirect = Invoice::where('type', $method)->sum('total');
        $totalMixed = \DB::table('invoice_payments')
            ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.type', 'mixed')
            ->where('invoice_payments.method', $method)
            ->sum('invoice_payments.amount');

        $total = $totalDirect + $totalMixed;

        return compact('invoices', 'total');
    }

    public function visaSales(Request $request)
    {
        $report = $this->buildPaymentMethodReport('visa', $request);
        return view('reports.visa-sales', [
            'invoices' => $report['invoices'],
            'totalVisaSales' => $report['total']
        ]);
    }

    /**
     * Display Transfer sales report.
     */
    public function transferSales(Request $request)
    {
        $report = $this->buildPaymentMethodReport('transfer', $request);
        return view('reports.transfer-sales', [
            'invoices' => $report['invoices'],
            'totalTransferSales' => $report['total']
        ]);
    }

    public function inventorySummary(Request $request)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $priceTypes = PriceType::where('is_active', true)->orderBy('sort_order')->get();
        $selectedPriceTypeId = $request->input('price_type_id', PriceType::where('is_default', true)->first()->id ?? null);

        $productsQuery = Product::with([
            'category',
            'units' => function ($query) use ($selectedPriceTypeId) {
                $query->with(['unit', 'prices' => function ($priceQuery) use ($selectedPriceTypeId) {
                    $priceQuery->where('price_type_id', $selectedPriceTypeId);
                }]);
            }
        ])
        ->where('stock_quantity', '>', 0)
        ->where('is_active', true);

        if ($request->filled('category_id')) {
            $productsQuery->where('category_id', $request->category_id);
        }

        $products = $productsQuery->get();

        $totalPurchaseValue = 0;
        $totalSaleValue = 0;

        foreach ($products as $product) {
            $mainUnit = $product->units->firstWhere('is_main_unit', true);

            if (!$mainUnit) {
                continue;
            }

            $cost = $mainUnit->cost ?? 0;
            $price = $mainUnit->prices->first()->value ?? 0;

            $totalPurchaseValue += $product->stock_quantity * $cost;
            $totalSaleValue += $product->stock_quantity * $price;
        }

        $expectedProfit = $totalSaleValue - $totalPurchaseValue;

        return view('reports.inventory-summary', compact(
            'products',
            'categories',
            'priceTypes',
            'totalPurchaseValue',
            'totalSaleValue',
            'expectedProfit',
            'selectedPriceTypeId'
        ));
    }

    public function productSales(Request $request)
    {
        $products = Product::all();
        $categories = Category::all();

        $productSales = \DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->select(
                'products.name as product_name',
                \DB::raw('COUNT(invoice_items.id) as sales_count'),
                \DB::raw('SUM(invoice_items.total_price) as total_sales_value'),
                \DB::raw('SUM(invoice_items.profit) as total_profit')
            )
            ->when($request->product_id, function ($query, $product_id) {
                return $query->where('invoice_items.product_id', $product_id);
            })
            ->when($request->category_id, function ($query, $category_id) {
                return $query->where('products.category_id', $category_id);
            })
            ->when($request->hour, function ($query, $hour) {
                return $query->where(\DB::raw('HOUR(invoices.created_at)'), $hour);
            })
            ->when($request->start_date, function ($query, $start_date) {
                return $query->where('invoices.created_at', '>=', $start_date);
            })
            ->when($request->end_date, function ($query, $end_date) {
                return $query->where('invoices.created_at', '<=', $end_date);
            })
            ->groupBy('products.name')
            ->get();

        return view('reports.product-sales', compact('productSales', 'products', 'categories'));
    }

    /**
     * تقرير مرتجعات المبيعات
     */
    public function salesReturns(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:customers,id',
            'return_type' => 'nullable|in:item,full_invoice,partial_invoice',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // تاريخ البداية والنهاية الافتراضي
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');

        // استعلام المرتجعات مع العلاقات
        $salesReturns = DB::table('sales_returns')
            ->leftJoin('invoices', 'sales_returns.invoice_id', '=', 'invoices.id')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('users', 'sales_returns.user_id', '=', 'users.id')
            ->leftJoin('shifts', 'sales_returns.shift_id', '=', 'shifts.id')
            ->select([
                'sales_returns.id',
                'sales_returns.return_date',
                'sales_returns.return_type',
                'sales_returns.total_returned_amount',
                'sales_returns.notes',
                'invoices.id as invoice_id',
                'invoices.total as invoice_total',
                'invoices.created_at as invoice_date',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'users.name as user_name',
                'shifts.id as shift_id'
            ])
            ->whereBetween('sales_returns.return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->where('invoices.customer_id', $customerId);
            })
            ->when($request->return_type, function ($query, $returnType) {
                return $query->where('sales_returns.return_type', $returnType);
            })
            ->when($request->user_id, function ($query, $userId) {
                return $query->where('sales_returns.user_id', $userId);
            })
            ->orderBy('sales_returns.return_date', 'desc')
            ->paginate(50);

        // إحصائيات إجمالية
        $totalReturns = DB::table('sales_returns')
            ->whereBetween('return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->whereExists(function ($subQuery) use ($customerId) {
                    $subQuery->select(DB::raw(1))
                        ->from('invoices')
                        ->whereColumn('invoices.id', 'sales_returns.invoice_id')
                        ->where('invoices.customer_id', $customerId);
                });
            })
            ->when($request->return_type, function ($query, $returnType) {
                return $query->where('return_type', $returnType);
            })
            ->when($request->user_id, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->count();

        $totalAmount = DB::table('sales_returns')
            ->whereBetween('return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->whereExists(function ($subQuery) use ($customerId) {
                    $subQuery->select(DB::raw(1))
                        ->from('invoices')
                        ->whereColumn('invoices.id', 'sales_returns.invoice_id')
                        ->where('invoices.customer_id', $customerId);
                });
            })
            ->when($request->return_type, function ($query, $returnType) {
                return $query->where('return_type', $returnType);
            })
            ->when($request->user_id, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->sum('total_returned_amount');

        // إحصائيات حسب نوع الإرجاع
        $returnTypeStats = DB::table('sales_returns')
            ->whereBetween('return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('return_type, COUNT(*) as count, SUM(total_returned_amount) as total_amount')
            ->groupBy('return_type')
            ->get();

        // إحصائيات المنتجات الأكثر إرجاعاً
        $topReturnedProducts = collect();
        try {
            // Check if return_items table exists
            if (Schema::hasTable('return_items')) {
                $topReturnedProducts = DB::table('return_items')
                    ->join('sales_returns', 'return_items.sales_return_id', '=', 'sales_returns.id')
                    ->join('products', 'return_items.product_id', '=', 'products.id')
                    ->join('product_units', 'return_items.unit_id', '=', 'product_units.id')
                    ->whereBetween('sales_returns.return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($request->customer_id, function ($query, $customerId) {
                        return $query->whereExists(function ($subQuery) use ($customerId) {
                            $subQuery->select(DB::raw(1))
                                ->from('invoices')
                                ->whereColumn('invoices.id', 'sales_returns.invoice_id')
                                ->where('invoices.customer_id', $customerId);
                        });
                    })
                    ->when($request->return_type, function ($query, $returnType) {
                        return $query->where('sales_returns.return_type', $returnType);
                    })
                    ->when($request->user_id, function ($query, $userId) {
                        return $query->where('sales_returns.user_id', $userId);
                    })
                    ->selectRaw('
                        products.name as product_name, 
                        product_units.name as unit_name,
                        SUM(return_items.quantity_returned) as total_quantity,
                        SUM(return_items.sub_total_returned) as total_amount,
                        COUNT(DISTINCT return_items.sales_return_id) as return_count
                    ')
                    ->groupBy('products.id', 'products.name', 'product_units.id', 'product_units.name')
                    ->orderByDesc('total_quantity')
                    ->limit(10)
                    ->get();
            }
        } catch (\Exception $e) {
            // If table doesn't exist or any other error, just use empty collection
            $topReturnedProducts = collect();
        }

        // إحصائيات العملاء الأكثر إرجاعاً
        $topReturningCustomers = DB::table('sales_returns')
            ->join('invoices', 'sales_returns.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->whereBetween('sales_returns.return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->where('customers.id', $customerId);
            })
            ->when($request->return_type, function ($query, $returnType) {
                return $query->where('sales_returns.return_type', $returnType);
            })
            ->when($request->user_id, function ($query, $userId) {
                return $query->where('sales_returns.user_id', $userId);
            })
            ->selectRaw('
                customers.name as customer_name,
                customers.phone as customer_phone,
                COUNT(*) as return_count,
                SUM(sales_returns.total_returned_amount) as total_amount
            ')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // البيانات للفلاتر
        $customers = \App\Models\Customer::select('id', 'name')->orderBy('name')->get();
        $users = \App\Models\User::select('id', 'name')->orderBy('name')->get();

        return view('reports.sales-returns', compact(
            'salesReturns', 
            'totalReturns', 
            'totalAmount', 
            'returnTypeStats',
            'topReturnedProducts',
            'topReturningCustomers',
            'customers', 
            'users',
            'startDate',
            'endDate'
        ));
    }

    /**
     * تصدير تقرير مرتجعات المبيعات إلى Excel
     */
    public function exportSalesReturns(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:customers,id',
            'return_type' => 'nullable|in:item,full_invoice,partial_invoice',
            'user_id' => 'nullable|exists:users,id',
            'format' => 'nullable|in:excel,pdf',
        ]);

        // تاريخ البداية والنهاية
        $startDate = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');

        $salesReturns = DB::table('sales_returns')
            ->leftJoin('invoices', 'sales_returns.invoice_id', '=', 'invoices.id')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('users', 'sales_returns.user_id', '=', 'users.id')
            ->leftJoin('shifts', 'sales_returns.shift_id', '=', 'shifts.id')
            ->select([
                'sales_returns.id',
                'sales_returns.return_date',
                'sales_returns.return_type',
                'sales_returns.total_returned_amount',
                'sales_returns.notes',
                'invoices.id as invoice_id',
                'invoices.total as invoice_total',
                'invoices.created_at as invoice_date',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'users.name as user_name',
                'shifts.id as shift_id'
            ])
            ->whereBetween('sales_returns.return_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->where('invoices.customer_id', $customerId);
            })
            ->when($request->return_type, function ($query, $returnType) {
                return $query->where('sales_returns.return_type', $returnType);
            })
            ->when($request->user_id, function ($query, $userId) {
                return $query->where('sales_returns.user_id', $userId);
            })
            ->orderBy('sales_returns.return_date', 'desc')
            ->get();

        // تحضير البيانات للتصدير
        $exportData = [];
        $exportData[] = ['تقرير مرتجعات المبيعات'];
        $exportData[] = ['من تاريخ: ' . $startDate . ' إلى تاريخ: ' . $endDate];
        $exportData[] = []; // سطر فارغ

        // رؤوس الأعمدة
        $exportData[] = [
            'رقم المرتجع',
            'تاريخ الإرجاع',
            'رقم الفاتورة',
            'تاريخ الفاتورة',
            'اسم العميل',
            'هاتف العميل',
            'نوع الإرجاع',
            'مبلغ الإرجاع',
            'المستخدم',
            'رقم الوردية',
            'ملاحظات'
        ];

        // بيانات المرتجعات
        foreach ($salesReturns as $return) {
            $exportData[] = [
                $return->id,
                \Carbon\Carbon::parse($return->return_date)->format('Y-m-d H:i'),
                $return->invoice_id ?? 'لا يوجد',
                $return->invoice_date ? \Carbon\Carbon::parse($return->invoice_date)->format('Y-m-d H:i') : 'لا يوجد',
                $return->customer_name ?? 'غير محدد',
                $return->customer_phone ?? 'غير محدد',
                match($return->return_type) {
                    'item' => 'إرجاع صنف',
                    'full_invoice' => 'إرجاع فاتورة كاملة',
                    'partial_invoice' => 'إرجاع جزئي من فاتورة',
                    default => $return->return_type
                },
                number_format($return->total_returned_amount, 2),
                $return->user_name ?? 'غير محدد',
                $return->shift_id ?? 'غير محدد',
                $return->notes ?? ''
            ];
        }

        // تحديد صيغة التصدير
        $format = $request->format ?? 'excel';
        
        if ($format === 'pdf') {
            // إنشاء ملف PDF
            $fileName = 'sales_returns_report_' . $startDate . '_to_' . $endDate . '.pdf';
            
            // إحضار البيانات للعرض في PDF
            $reportData = [
                'salesReturns' => $salesReturns,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalReturns' => $salesReturns->count(),
                'totalAmount' => $salesReturns->sum('total_returned_amount'),
            ];
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales-returns-pdf', $reportData);
            $pdf->setPaper('A4', 'landscape');
            
            return $pdf->download($fileName);
        } else {
            // إنشاء ملف Excel
            $fileName = 'sales_returns_report_' . $startDate . '_to_' . $endDate . '.xlsx';
            
            return \Maatwebsite\Excel\Facades\Excel::download(
                new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray {
                    protected $data;
                    
                    public function __construct($data) {
                        $this->data = $data;
                    }
                    
                    public function array(): array {
                        return $this->data;
                    }
                },
                $fileName
            );
        }
    }
} 