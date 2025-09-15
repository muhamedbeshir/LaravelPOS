<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\CustomerPayment;
use App\Models\CustomerSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerApiController extends Controller
{
    /**
     * Get all customers
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCustomers(Request $request)
    {
        try {
            $query = Customer::query();
            
            // Filter by status
            if ($request->has('status')) {
                $status = $request->status;
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            }
            
            // Filter by search term
            if ($request->has('search')) {
                $search = $request->search;
                \Log::info('API performing customer search with term: ' . $search);
                
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                    // shop_name was removed from database, so we don't search it anymore
                });
                
                try {
                    // Log the generated SQL query for debugging
                    $sql = $query->toSql();
                    $bindings = $query->getBindings();
                    \Log::info('Generated API SQL query: ' . $sql, ['bindings' => $bindings]);
                } catch (\Exception $e) {
                    \Log::error('Error logging API SQL query: ' . $e->getMessage());
                }
            }
            
            // Order by
            $orderBy = $request->order_by ?? 'created_at';
            $direction = $request->direction ?? 'desc';
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->per_page ?? 15;
            $customers = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            \Log::error('API error retrieving customers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomer($id)
    {
        try {
            $customer = Customer::with(['sales', 'payments'])->findOrFail($id);
            
            // Calculate total spent, total payments, and current balance
            $totalSpent = $customer->sales->sum('total_amount');
            $totalPaid = $customer->payments->sum('amount');
            $balance = $totalSpent - $totalPaid;
            
            $customer = $customer->toArray();
            $customer['total_spent'] = $totalSpent;
            $customer['total_paid'] = $totalPaid;
            $customer['balance'] = $balance;
            
            return response()->json([
                'status' => 'success',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Store a new customer
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'email' => 'nullable|email|max:255|unique:customers',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update customer
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCustomer(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20|unique:customers,phone,' . $id,
                'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
                'address' => 'nullable|string|max:500',
                'notes' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Delete customer
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCustomer($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Check if customer has sales or payments
            if ($customer->sales()->count() > 0 || $customer->payments()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete customer as they have associated sales or payments'
                ], 422);
            }
            
            $customer->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Toggle customer active status
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleCustomerStatus($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->is_active = !$customer->is_active;
            $customer->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Customer status updated successfully',
                'data' => [
                    'id' => $customer->id,
                    'is_active' => $customer->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer status',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get customer invoices
     * 
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerInvoices($id, Request $request)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            $query = Sale::where('customer_id', $id);
            
            // Filter by date range
            if ($request->has('from_date') && $request->has('to_date')) {
                $fromDate = Carbon::parse($request->from_date)->startOfDay();
                $toDate = Carbon::parse($request->to_date)->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Order by
            $orderBy = $request->order_by ?? 'created_at';
            $direction = $request->direction ?? 'desc';
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->per_page ?? 15;
            $invoices = $query->with(['items', 'employee'])->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'customer' => $customer->only(['id', 'name', 'phone']),
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer invoices',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get customer report
     * 
     * @param int $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerReport($id, Request $request)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Date range filter
            $fromDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->subMonths(3)->startOfDay();
            $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();
            
            // Get sales data
            $sales = Sale::where('customer_id', $id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderBy('created_at')
                ->get();
                
            // Get payments data
            $payments = CustomerPayment::where('customer_id', $id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderBy('created_at')
                ->get();
            
            // Calculate totals
            $totalSales = $sales->sum('total_amount');
            $totalPayments = $payments->sum('amount');
            $balance = $totalSales - $totalPayments;
            
            // Get top purchased products
            $topProducts = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(sale_items.quantity) as total_quantity'),
                    DB::raw('SUM(sale_items.total_price) as total_amount')
                )
                ->where('sales.customer_id', $id)
                ->whereBetween('sales.created_at', [$fromDate, $toDate])
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_amount', 'desc')
                ->limit(5)
                ->get();
            
            // Monthly sales analysis
            $monthlySales = Sale::where('customer_id', $id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('COUNT(*) as invoice_count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'customer' => $customer->only(['id', 'name', 'phone', 'email', 'address']),
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_payments' => $totalPayments,
                    'balance' => $balance,
                    'invoice_count' => $sales->count()
                ],
                'top_products' => $topProducts,
                'monthly_analysis' => $monthlySales,
                'period' => [
                    'from_date' => $fromDate->format('Y-m-d'),
                    'to_date' => $toDate->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer report',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get credit customers
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCreditCustomers(Request $request)
    {
        try {
            $query = Customer::withCount(['sales', 'payments'])
                ->withSum('sales', 'total_amount')
                ->withSum('payments', 'amount')
                ->havingRaw('COALESCE(sum(sales.total_amount), 0) > COALESCE(sum(customer_payments.amount), 0)')
                ->orderByRaw('COALESCE(sum(sales.total_amount), 0) - COALESCE(sum(customer_payments.amount), 0) DESC');
            
            // Filter by search term
            if ($request->has('search')) {
                $search = $request->search;
                \Log::info('API credit customers search with term: ' . $search);

                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                    // shop_name was removed from database, so we don't search it anymore
                });
                
                try {
                    // Log the generated SQL query for debugging
                    $sql = $query->toSql();
                    $bindings = $query->getBindings();
                    \Log::info('Generated API credit customers SQL query: ' . $sql, ['bindings' => $bindings]);
                } catch (\Exception $e) {
                    \Log::error('Error logging API credit customers SQL query: ' . $e->getMessage());
                }
            }
            
            // Pagination
            $perPage = $request->per_page ?? 15;
            $customers = $query->paginate($perPage);
            
            // Calculate balance for each customer
            $customers->getCollection()->transform(function ($customer) {
                $customer->balance = $customer->sales_sum_total_amount - $customer->payments_sum_amount;
                return $customer;
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve credit customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer dashboard summary
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary()
    {
        try {
            // Count total customers
            $totalCustomers = Customer::count();
            $activeCustomers = Customer::where('is_active', true)->count();
            
            // Get customers with overdue balances
            $creditCustomers = Customer::withCount(['sales', 'payments'])
                ->withSum('sales', 'total_amount')
                ->withSum('payments', 'amount')
                ->havingRaw('COALESCE(sum(sales.total_amount), 0) > COALESCE(sum(customer_payments.amount), 0)')
                ->count();
            
            // Get top customers by sales
            $topCustomers = Customer::withSum('sales', 'total_amount')
                ->orderByDesc('sales_sum_total_amount')
                ->limit(5)
                ->get(['id', 'name', 'phone', 'sales_sum_total_amount']);
            
            // Recent customers
            $recentCustomers = Customer::orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'phone', 'created_at']);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'counts' => [
                        'total_customers' => $totalCustomers,
                        'active_customers' => $activeCustomers,
                        'credit_customers' => $creditCustomers
                    ],
                    'top_customers' => $topCustomers,
                    'recent_customers' => $recentCustomers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer settings
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        try {
            $settings = CustomerSetting::all();
            
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update customer settings
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string|exists:customer_settings,key',
                'settings.*.value' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            foreach ($request->settings as $settingData) {
                $setting = CustomerSetting::where('key', $settingData['key'])->first();
                if ($setting) {
                    $setting->value = $settingData['value'];
                    $setting->save();
                }
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Customer settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update customer settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 