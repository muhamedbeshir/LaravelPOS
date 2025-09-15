<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Exports\CustomerInvoicesExport;
use App\Exports\CustomerReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;

class CustomerController extends Controller
{
    protected $settings;

    protected static array $middlewares = [
        'auth',
        'permission:view-customers' => ['only' => ['index', 'show']],
        'permission:create-customers' => ['only' => ['create', 'store']],
        'permission:edit-customers' => ['only' => ['edit', 'update']],
        'permission:delete-customers' => ['only' => ['destroy']],
    ];

    public function __construct()
    {
        try {
            $this->settings = CustomerSetting::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Log the request parameters for debugging
            \Log::info('Customer filter parameters:', $request->all());
            
            $query = Customer::query();
            
            // Exclude the cash customer (ID=1)
            $query->where('id', '!=', 1);

            // Filter by payment type
            if ($request->has('payment_type') && $request->payment_type !== '') {
                $query->where('payment_type', $request->payment_type);
            }

            // Filter by balance
            if ($request->has('balance_filter') && $request->balance_filter !== '') {
                \Log::info('Applying balance filter: ' . $request->balance_filter);
                
                switch ($request->balance_filter) {
                    case 'negative':
                        $query->where('credit_balance', '<', 0);
                        break;
                    case 'positive':
                        $query->where('credit_balance', '>', 0);
                        break;
                    case 'zero':
                        $query->where('credit_balance', '=', 0);
                        break;
                }
            }

            // Search by name or phone
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                \Log::info('Performing customer search with term: ' . $search);
                
                $query->where(function($q) use ($search) {
                    // Only search in name and phone fields
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
                
                try {
                    // Log the generated SQL query for debugging
                    $sql = $query->toSql();
                    $bindings = $query->getBindings();
                    \Log::info('Generated SQL query: ' . $sql, ['bindings' => $bindings]);
                } catch (\Exception $e) {
                    \Log::error('Error logging SQL query: ' . $e->getMessage());
                }
            }

            $customers = $query->paginate(15);

            if ($request->wantsJson() || $request->has('wantsJson')) {
                try {
                    // For debugging
                    \Log::info('Sending JSON response with customers count: ' . $customers->count());
                    
                    return response()->json([
                        'success' => true,
                        'customers' => $customers,
                        'pagination' => [
                            'total' => $customers->total(),
                            'per_page' => $customers->perPage(),
                            'current_page' => $customers->currentPage(),
                            'last_page' => $customers->lastPage(),
                        ]
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error creating JSON response: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Error processing customer data',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Get cached customer statistics
            $stats = CacheService::getCustomerStats();

            // Get all active price types for customer forms
            $priceTypes = \App\Models\PriceType::active()
                ->orderBy('sort_order')
                ->get();

            return view('customers.index', compact('customers', 'stats', 'priceTypes'));

        } catch (\Exception $e) {
            \Log::error('Error in customers index: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحميل البيانات'
                ], 500);
            }

            return back()->with('error', 'حدث خطأ أثناء تحميل البيانات');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Customer creation request received', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'credit_limit' => 'nullable|numeric|min:0',
                'has_unlimited_credit' => 'nullable|boolean',
                'default_price_type_id' => 'nullable|exists:price_types,id'
            ]);

            // Initialize credit_balance to 0
            $validated['credit_balance'] = 0;
            // Set default payment_type as credit to allow all types
            $validated['payment_type'] = 'credit';
            
            // Set is_unlimited_credit flag if specified via has_unlimited_credit
            if (isset($validated['has_unlimited_credit']) && $validated['has_unlimited_credit']) {
                $validated['is_unlimited_credit'] = true;
                // Set a reasonable default credit limit
                $validated['credit_limit'] = 0;
                unset($validated['has_unlimited_credit']);
            } else {
                $validated['is_unlimited_credit'] = false;
                // Ensure credit_limit is set (default to 0 if not provided)
                if (!isset($validated['credit_limit'])) {
                    $validated['credit_limit'] = 0;
                }
                unset($validated['has_unlimited_credit']);
            }

            $customer = Customer::create($validated);
            \Log::info('Customer created successfully', ['id' => $customer->id, 'name' => $customer->name]);

            // Load the default price type relationship for the response
            $customer->load('defaultPriceType');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة العميل بنجاح',
                    'customer' => $customer
                ]);
            }

            return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح');
        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إضافة العميل',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'حدث خطأ أثناء إضافة العميل');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        try {
            // تحميل المدفوعات والفواتير مع العميل
            $customer->load(['payments', 'invoices' => function($query) {
                $query->latest()->take(10);
            }, 'defaultPriceType']);

            // حساب الإحصائيات
            $stats = [
                'total_invoices' => $customer->invoices()->count(),
                'total_amount' => $customer->invoices()->sum('total_amount'),
                'total_paid' => $customer->payments()->sum('amount'),
                'remaining_balance' => $customer->credit_balance
            ];

            return view('customers.show', compact('customer', 'stats'));
        } catch (\Exception $e) {
            return redirect()->route('customers.index')
                ->with('error', 'حدث خطأ أثناء عرض بيانات العميل');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        try {
            // Get all active price types for the dropdown
            $priceTypes = \App\Models\PriceType::active()
                ->orderBy('sort_order')
                ->get();
                
            return view('customers.edit', compact('customer', 'priceTypes'));
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات العميل');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:1000',
                'credit_limit' => 'nullable|numeric|min:0',
                'has_unlimited_credit' => 'nullable|boolean',
                'is_active' => 'boolean',
                'default_price_type_id' => 'nullable|exists:price_types,id'
            ]);

            // Set is_unlimited_credit flag if specified via has_unlimited_credit
            if (isset($validated['has_unlimited_credit']) && $validated['has_unlimited_credit']) {
                $validated['is_unlimited_credit'] = true;
                // Set a reasonable default credit limit
                $validated['credit_limit'] = 0;
                unset($validated['has_unlimited_credit']);
            } else {
                $validated['is_unlimited_credit'] = false;
                // Ensure credit limit is a valid number
                if (!isset($validated['credit_limit'])) {
                    $validated['credit_limit'] = $customer->credit_limit;
                }
                unset($validated['has_unlimited_credit']);
            }

            $customer->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث العميل بنجاح'
                ]);
            }

            return redirect()->route('customers.index')->with('success', 'تم تحديث العميل بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث العميل',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'حدث خطأ أثناء تحديث العميل');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Customer $customer)
    {
        try {
            \Log::info("Attempting to delete customer ID: {$customer->id}, name: {$customer->name}");
            
            if ($customer->credit_balance > 0) {
                \Log::warning("Cannot delete customer ID: {$customer->id} - has credit balance: {$customer->credit_balance}");
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف العميل لوجود مديونية عليه'
                    ], 422);
                }
                return back()->with('error', 'لا يمكن حذف العميل لوجود مديونية عليه');
            }

            // Check if customer has invoices
            $invoicesCount = $customer->invoices()->count();
            if ($invoicesCount > 0) {
                \Log::info("Customer ID: {$customer->id} has {$invoicesCount} invoices. Setting customer_id to NULL in these invoices.");
                
                // Instead of preventing deletion, update invoices to set customer_id to NULL
                DB::table('invoices')
                    ->where('customer_id', $customer->id)
                    ->update([
                        'customer_id' => null,
                        'updated_at' => now()
                    ]);
                
                \Log::info("Updated {$invoicesCount} invoices to remove customer reference.");
            }

            // Delete any customer payments
            $paymentsCount = $customer->payments()->count();
            if ($paymentsCount > 0) {
                \Log::info("Deleting {$paymentsCount} payments for customer ID: {$customer->id}");
                $customer->payments()->delete();
            }

            // Now we can safely delete the customer
            $customer->forceDelete();
            \Log::info("Successfully deleted customer ID: {$customer->id}");

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف العميل بنجاح'
                ]);
            }
            return back()->with('success', 'تم حذف العميل بنجاح');
        } catch (\Exception $e) {
            \Log::error("Error deleting customer ID: {$customer->id} - " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف العميل',
                    'error' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'حدث خطأ أثناء حذف العميل');
        }
    }

    public function getCustomerInvoices(Customer $customer, Request $request)
    {
        try {
            \Log::info("Getting invoices for customer ID: {$customer->id}, name: {$customer->name}");
            
            $query = $customer->invoices()->with('items');
            
            \Log::info("Initial query created with relationship 'items'");

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
                \Log::info("Applied date filter: {$request->start_date} to {$request->end_date}");
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
                \Log::info("Applied payment status filter: {$request->payment_status}");
            }

            $invoices = $query->latest()->paginate(10);
            
            \Log::info("Found {$invoices->total()} invoices for customer ID: {$customer->id}");
            
            if ($invoices->isEmpty()) {
                \Log::warning("No invoices found for customer ID: {$customer->id}");
            } else {
                // Log some details about the first invoice
                $firstInvoice = $invoices->first();
                \Log::info("First invoice details - ID: {$firstInvoice->id}, Invoice Number: {$firstInvoice->invoice_number}, Total: {$firstInvoice->total}, Status: {$firstInvoice->payment_status}");
            }

            return response()->json([
                'invoices' => $invoices,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error("Error retrieving customer invoices: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الفواتير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCreditCustomers()
    {
        $customers = Customer::credit()
            ->where('id', '!=', 1)
            ->withDueBalance()
            ->orderByDesc('credit_balance')
            ->get();

        return response()->json([
            'customers' => $customers
        ]);
    }

    public function getCustomerReport(Customer $customer)
    {
        $report = [
            'total_invoices' => $customer->invoices()->count(),
            'total_amount' => $customer->invoices()->sum('total_amount'),
            'total_paid' => $customer->payments()->sum('amount'),
            'remaining_balance' => $customer->credit_balance,
            'payment_history' => $customer->payments()
                ->select('amount', 'payment_method', 'created_at')
                ->latest()
                ->get(),
            'recent_invoices' => $customer->invoices()
                ->select('id', 'total_amount', 'payment_status', 'created_at')
                ->latest()
                ->take(5)
                ->get()
        ];

        return response()->json($report);
    }

    public function exportInvoices(Customer $customer, Request $request)
    {
        $format = $request->get('format', 'excel');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $paymentStatus = $request->get('payment_status');

        if ($format === 'excel') {
            return Excel::download(
                new CustomerInvoicesExport($customer, $startDate, $endDate, $paymentStatus),
                "invoices_{$customer->name}.xlsx"
            );
        }

        if ($format === 'pdf') {
            $invoices = $customer->invoices()
                ->when($startDate && $endDate, function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->when($paymentStatus, function($query) use ($paymentStatus) {
                    $query->where('payment_status', $paymentStatus);
                })
                ->with('items')
                ->get();

            $pdf = PDF::loadView('exports.invoices', [
                'customer' => $customer,
                'invoices' => $invoices
            ]);

            return $pdf->download("invoices_{$customer->name}.pdf");
        }

        return response()->json(['message' => 'Invalid export format'], 400);
    }

    public function exportCustomerReport(Request $request)
    {
        $format = $request->get('format', 'excel');
        $type = $request->get('type', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($format === 'excel') {
            $exporter = new CustomerReportExport($type, $startDate, $endDate);
            return $exporter->download('customer_report.xlsx');
        }

        if ($format === 'pdf') {
            $query = Customer::query()
                ->withCount('invoices')
                ->withSum('invoices', 'total_amount')
                ->withSum('payments', 'amount');

            if ($type === 'credit') {
                $query->credit()->withDueBalance();
            }

            if ($startDate && $endDate) {
                $query->whereHas('invoices', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                });
            }

            $customers = $query->get();

            $pdf = PDF::loadView('exports.customer_report', [
                'customers' => $customers,
                'type' => $type,
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);

            return $pdf->download('customer_report.pdf');
        }

        return response()->json(['message' => 'Invalid export format'], 400);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_due_days' => 'required|integer|min:1',
            'enable_whatsapp_notifications' => 'required|boolean',
            'send_invoice_notifications' => 'required|boolean',
            'send_due_date_reminders' => 'required|boolean',
            'reminder_days_before' => 'required|integer|min:1'
        ]);

        $settings = CustomerSetting::first();
        if (!$settings) {
            $settings = CustomerSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $settings
        ]);
    }

    public function getSettings()
    {
        $settings = CustomerSetting::first();
        if (!$settings) {
            $settings = CustomerSetting::create([
                'default_due_days' => 3,
                'enable_whatsapp_notifications' => true,
                'send_invoice_notifications' => true,
                'send_due_date_reminders' => true,
                'reminder_days_before' => 1
            ]);
        }

        return response()->json(['settings' => $settings]);
    }

    public function getDashboardSummary()
    {
        try {
            $quick_stats = [
                [
                    'title' => 'إجمالي العملاء',
                    'value' => Customer::count()
                ],
                [
                    'title' => 'العملاء الآجلين',
                    'value' => Customer::credit()->count()
                ],
                [
                    'title' => 'إجمالي المديونيات',
                    'value' => Customer::sum('credit_balance')
                ],
                [
                    'title' => 'مدفوعات اليوم',
                    'value' => DB::table('customer_payments')
                        ->whereDate('created_at', today())
                        ->sum('amount')
                ]
            ];

            return response()->json([
                'success' => true,
                'quick_stats' => $quick_stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storePayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'amount' => 'required|numeric|not_in:0',
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:cash,bank_transfer,check',
                'notes' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $customer = Customer::findOrFail($validatedData['customer_id']);
            
            // إنشاء سجل الدفع
            $payment = new CustomerPayment([
                'customer_id' => $customer->id,
                'amount' => $validatedData['amount'],
                'payment_date' => $validatedData['payment_date'],
                'payment_method' => $validatedData['payment_method'],
                'notes' => $validatedData['notes']
            ]);
            $payment->save();

            // تحديث رصيد العميل
            $customer->credit_balance -= $validatedData['amount'];
            $customer->save();

            DB::commit();

            // Clear customer-related caches after a payment is processed
            CacheService::clearCache([CacheService::TAG_CUSTOMERS, CacheService::TAG_DASHBOARD]);
            
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدفعة بنجاح',
                'new_balance' => $customer->credit_balance
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الدفعة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCustomerInfo(Customer $customer)
    {
        try {
            $customer->load(['invoices', 'payments']);
            
            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address ?: '-',
                'notes' => $customer->notes,
                'credit_balance' => $customer->credit_balance,
                'credit_limit' => $customer->credit_limit,
                'is_unlimited_credit' => $customer->is_unlimited_credit,
                'created_at' => $customer->created_at,
                'invoices_count' => $customer->invoices->count(),
                'total_sales' => $customer->invoices->sum('total_amount'),
                'total_payments' => $customer->payments->sum('amount'),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل بيانات العميل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quick statistics for dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQuickStats()
    {
        try {
            // Get cached customer statistics and transform them for the quick stats format
            $customerStats = CacheService::getCustomerStats();
            
            $quick_stats = [
                [
                    'title' => 'إجمالي العملاء',
                    'value' => $customerStats['total_customers']
                ],
                [
                    'title' => 'العملاء الآجلين',
                    'value' => $customerStats['customers_with_balance']
                ],
                [
                    'title' => 'إجمالي المديونيات',
                    'value' => $customerStats['total_balance']
                ],
                [
                    'title' => 'مدفوعات اليوم',
                    'value' => $customerStats['today_payments']
                ]
            ];

            return response()->json([
                'success' => true,
                'quick_stats' => $quick_stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
