<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerPaymentApiController extends Controller
{
    /**
     * Get all customer payments
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllPayments(Request $request)
    {
        try {
            $query = CustomerPayment::with('customer');
            
            // Filter by customer ID
            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
            
            // Filter by date range
            if ($request->has('from_date') && $request->has('to_date')) {
                $fromDate = Carbon::parse($request->from_date)->startOfDay();
                $toDate = Carbon::parse($request->to_date)->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }
            
            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }
            
            // Order by
            $orderBy = $request->order_by ?? 'created_at';
            $direction = $request->direction ?? 'desc';
            $query->orderBy($orderBy, $direction);
            
            // Pagination
            $perPage = $request->per_page ?? 15;
            $payments = $query->paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customer payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get customer payment by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayment($id)
    {
        try {
            $payment = CustomerPayment::with('customer')->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer payment not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Store a new customer payment
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|not_in:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,credit_card,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if customer has outstanding balance
            $customer = Customer::findOrFail($request->customer_id);
            $totalSales = $customer->sales->sum('total_amount');
            $totalPaid = $customer->payments->sum('amount');
            $balance = $totalSales - $totalPaid;
            
            if ($balance <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer has no outstanding balance'
                ], 422);
            }
            
            // If payment amount is greater than balance, adjust it to the balance
            $paymentAmount = min($request->amount, $balance);
            
            $payment = new CustomerPayment();
            $payment->customer_id = $request->customer_id;
            $payment->amount = $paymentAmount;
            $payment->payment_method = $request->payment_method;
            $payment->reference_number = $request->reference_number;
            $payment->notes = $request->notes;
            $payment->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Customer payment added successfully',
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add customer payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete customer payment
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePayment($id)
    {
        try {
            $payment = CustomerPayment::findOrFail($id);
            
            // Check if payment was created more than 24 hours ago
            $createdAt = Carbon::parse($payment->created_at);
            $now = Carbon::now();
            
            if ($now->diffInHours($createdAt) > 24) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete payment as it was created more than 24 hours ago'
                ], 422);
            }
            
            $payment->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Customer payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete customer payment',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
    
    /**
     * Get payment summary by period
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentSummary(Request $request)
    {
        try {
            // Date range filter
            $fromDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : Carbon::now()->startOfMonth();
            $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : Carbon::now()->endOfDay();
            
            // Get payments in the date range
            $payments = CustomerPayment::whereBetween('created_at', [$fromDate, $toDate]);
            
            // Filter by customer ID if provided
            if ($request->has('customer_id')) {
                $payments->where('customer_id', $request->customer_id);
            }
            
            // Get total amount by payment method
            $paymentsByMethod = $payments->select('payment_method', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();
            
            // Get daily payment totals
            $dailyPayments = CustomerPayment::whereBetween('created_at', [$fromDate, $toDate])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Get top paying customers
            $topCustomers = CustomerPayment::whereBetween('created_at', [$fromDate, $toDate])
                ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
                ->with('customer:id,name,phone')
                ->groupBy('customer_id')
                ->orderByDesc('total_paid')
                ->limit(5)
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'period' => [
                        'from_date' => $fromDate->format('Y-m-d'),
                        'to_date' => $toDate->format('Y-m-d')
                    ],
                    'total_amount' => $payments->sum('amount'),
                    'payment_count' => $payments->count(),
                    'by_payment_method' => $paymentsByMethod,
                    'daily_payments' => $dailyPayments,
                    'top_customers' => $topCustomers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 