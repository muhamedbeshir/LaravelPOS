<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display main loyalty dashboard
     */
    public function dashboard(): View
    {
        $statistics = $this->loyaltyService->getLoyaltyStatistics();
        $settings = LoyaltySetting::getSettings();
        
        // Recent transactions
        $recentTransactions = LoyaltyTransaction::with(['customer', 'user'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Top customers by points
        $topCustomers = Customer::where('id', '!=', 1)
            ->where('total_loyalty_points', '>', 0)
            ->orderBy('total_loyalty_points', 'desc')
            ->limit(10)
            ->get();
        
        // Monthly statistics for chart
        $monthlyStats = DB::table('loyalty_transactions')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(CASE WHEN type = "earned" THEN points ELSE 0 END) as points_earned'),
                DB::raw('SUM(CASE WHEN type = "redeemed" THEN points ELSE 0 END) as points_redeemed'),
                DB::raw('COUNT(CASE WHEN type = "earned" THEN 1 END) as transactions_earned'),
                DB::raw('COUNT(CASE WHEN type = "redeemed" THEN 1 END) as transactions_redeemed')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        return view('loyalty.dashboard', compact(
            'statistics',
            'settings', 
            'recentTransactions',
            'topCustomers',
            'monthlyStats'
        ));
    }

    /**
     * Display loyalty settings page
     */
    public function settings(): View
    {
        $settings = LoyaltySetting::getSettings();
        $statistics = $this->loyaltyService->getLoyaltyStatistics();
        
        return view('loyalty.settings', compact('settings', 'statistics'));
    }

    /**
     * Update loyalty settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'earning_method' => 'required|in:per_invoice,per_amount,per_product',
            'points_per_invoice' => 'required|integer|min:1',
            'points_per_amount' => 'required|numeric|min:0.01',
            'points_per_product' => 'required|integer|min:1',
            'points_to_currency_rate' => 'required|integer|min:1',
            'max_redemption_per_transaction' => 'nullable|integer|min:1',
            'min_points_for_redemption' => 'required|integer|min:1',
            'allow_full_discount' => 'boolean',
            'is_active' => 'boolean'
        ]);

        try {
            $this->loyaltyService->updateLoyaltySettings($validated);
            
            return redirect()->route('loyalty.settings')
                ->with('success', 'تم تحديث إعدادات نقاط الولاء بنجاح');
                
        } catch (\Exception $e) {
            Log::error('Error updating loyalty settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage());
        }
    }

    /**
     * Show customer loyalty dashboard
     */
    public function customerDashboard(Customer $customer): View
    {
        $loyaltySummary = $this->loyaltyService->getCustomerLoyaltySummary($customer);
        $loyaltyHistory = $this->loyaltyService->getCustomerLoyaltyHistory($customer, 20);
        $maxDiscountInfo = $this->loyaltyService->getMaximumDiscountAvailable($customer);
        
        return view('loyalty.customer-dashboard', compact(
            'customer',
            'loyaltySummary', 
            'loyaltyHistory',
            'maxDiscountInfo'
        ));
    }

    /**
     * Redeem points to balance
     */
    public function redeemToBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'points' => 'required|integer|min:1'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            
            $amount = $this->loyaltyService->redeemPointsToBalance(
                $customer,
                (int) $validated['points']
            );

            return response()->json([
                'success' => true,
                'message' => "تم تحويل {$validated['points']} نقطة إلى رصيد بقيمة {$amount} جنيه",
                'amount_credited' => $amount,
                'new_points_balance' => $customer->fresh()->total_loyalty_points
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Apply points discount to invoice (API for sales system)
     */
    public function applyInvoiceDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'points' => 'required|integer|min:1',
            'invoice_number' => 'required|string'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            
            $discountAmount = $this->loyaltyService->applyPointsDiscountToInvoice(
                $customer,
                (int) $validated['points'],
                $validated['invoice_number']
            );

            return response()->json([
                'success' => true,
                'message' => "تم تطبيق خصم بقيمة {$discountAmount} جنيه",
                'discount_amount' => $discountAmount,
                'points_used' => $validated['points'],
                'new_points_balance' => $customer->fresh()->total_loyalty_points
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Manually adjust customer points
     */
    public function adjustPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'points' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            
            $this->loyaltyService->adjustCustomerPoints(
                $customer,
                (int) $validated['points'],
                auth()->id(),
                $validated['reason']
            );

            $action = $validated['points'] > 0 ? 'إضافة' : 'خصم';
            $points = abs($validated['points']);

            return response()->json([
                'success' => true,
                'message' => "تم {$action} {$points} نقطة بنجاح",
                'new_points_balance' => $customer->fresh()->total_loyalty_points
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reset customer points
     */
    public function resetPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            
            $this->loyaltyService->resetCustomerPoints(
                $customer,
                auth()->id(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين نقاط العميل بنجاح',
                'new_points_balance' => 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get customer loyalty summary (API)
     */
    public function getCustomerSummary(Customer $customer): JsonResponse
    {
        try {
            Log::info('Getting customer loyalty summary', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);
            
            $summary = $this->loyaltyService->getCustomerLoyaltySummary($customer);
            
            Log::info('Customer loyalty summary retrieved successfully', [
                'customer_id' => $customer->id,
                'summary' => $summary
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer loyalty summary', [
                'customer_id' => $customer->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل بيانات النقاط: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer loyalty history (API)
     */
    public function getCustomerHistory(Customer $customer, Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 50);
            $history = $this->loyaltyService->getCustomerLoyaltyHistory($customer, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل سجل النقاط'
            ], 500);
        }
    }

    /**
     * Calculate points for amount (API for sales system)
     */
    public function calculatePointsForAmount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'product_count' => 'required|integer|min:0'
        ]);

        try {
            $settings = LoyaltySetting::getSettings();
            
            if (!$settings->is_active) {
                return response()->json([
                    'success' => true,
                    'points' => 0,
                    'message' => 'نظام نقاط الولاء غير مفعل'
                ]);
            }

            $points = $settings->calculatePoints(
                $validated['amount'],
                (int) $validated['product_count']
            );

            return response()->json([
                'success' => true,
                'points' => $points,
                'earning_method' => $settings->earning_method,
                'earning_method_label' => $settings->earning_method_label
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب النقاط'
            ], 500);
        }
    }

    /**
     * Calculate discount for points (API for sales system)
     */
    public function calculateDiscountForPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'points' => 'required|integer|min:1'
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            $settings = LoyaltySetting::getSettings();
            
            if (!$settings->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'نظام نقاط الولاء غير مفعل'
                ], 422);
            }

            if (!$customer->canRedeemPoints((int) $validated['points'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استبدال هذا العدد من النقاط'
                ], 422);
            }

            $discountAmount = $customer->calculateDiscountFromPoints((int) $validated['points']);

            return response()->json([
                'success' => true,
                'discount_amount' => $discountAmount,
                'points_required' => (int) $validated['points'],
                'customer_points_balance' => $customer->total_loyalty_points
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب الخصم'
            ], 500);
        }
    }

    /**
     * Get maximum discount available for customer
     */
    public function getMaximumDiscount(Customer $customer): JsonResponse
    {
        try {
            $discountInfo = $this->loyaltyService->getMaximumDiscountAvailable($customer);
            
            return response()->json([
                'success' => true,
                'data' => $discountInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب الحد الأقصى للخصم'
            ], 500);
        }
    }

    /**
     * Get loyalty statistics for admin dashboard
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->loyaltyService->getLoyaltyStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الإحصائيات'
            ], 500);
        }
    }

    /**
     * Show all customers loyalty points list
     */
    public function customersIndex(Request $request): View
    {
        $query = Customer::where('id', '!=', 1) // Exclude cash customer
            ->with(['loyaltyTransactions' => function($q) {
                $q->latest()->limit(3);
            }]);

        // Filter by points
        if ($request->has('points_filter')) {
            switch ($request->points_filter) {
                case 'with_points':
                    $query->where('total_loyalty_points', '>', 0);
                    break;
                case 'no_points':
                    $query->where('total_loyalty_points', '=', 0);
                    break;
                case 'redeemable':
                    $settings = LoyaltySetting::getSettings();
                    $query->where('total_loyalty_points', '>=', $settings->min_points_for_redemption);
                    break;
            }
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('total_loyalty_points', 'desc')
            ->paginate(20);

        $statistics = $this->loyaltyService->getLoyaltyStatistics();

        return view('loyalty.customers-index', compact('customers', 'statistics'));
    }

    /**
     * Show loyalty transactions list
     */
    public function transactionsIndex(Request $request): View
    {
        $query = LoyaltyTransaction::with(['customer', 'user'])
            ->latest();

        // Filter by type
        if ($request->has('type_filter') && $request->type_filter) {
            $query->where('type', $request->type_filter);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(30);

        return view('loyalty.transactions-index', compact('transactions'));
    }

    /**
     * Get loyalty settings (API for AJAX calls)
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = LoyaltySetting::getSettings();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_active' => $settings->is_active,
                    'earning_method' => $settings->earning_method,
                    'earning_method_label' => $settings->earning_method_label,
                    'points_per_currency' => $settings->points_per_currency,
                    'points_per_product' => $settings->points_per_product,
                    'points_per_invoice' => $settings->points_per_invoice,
                    'points_to_currency_rate' => $settings->points_to_currency_rate,
                    'max_discount_percentage' => $settings->max_discount_percentage,
                    'min_points_for_redemption' => $settings->min_points_for_redemption,
                    'max_points_per_transaction' => $settings->max_points_per_transaction
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الإعدادات'
            ], 500);
        }
    }
}
