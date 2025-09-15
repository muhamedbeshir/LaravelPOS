<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomerPayment::with('customer');

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $payments = $query->latest()->paginate(10);

        return response()->json([
            'payments' => $payments
        ]);
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
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|not_in:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'notes' => 'nullable|string',
            'override_balance_check' => 'sometimes|boolean'
        ]);

        // Add payment_date field with today's date
        $validated['payment_date'] = now()->format('Y-m-d');

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($validated['customer_id']);
            
            // Check if request has override flag and convert it to boolean
            $overrideBalanceCheck = false;
            if ($request->has('override_balance_check')) {
                $overrideBalanceCheck = filter_var($request->override_balance_check, FILTER_VALIDATE_BOOLEAN);
                
                // Debug
                \Log::info('Payment override check', [
                    'raw_value' => $request->override_balance_check,
                    'processed_value' => $overrideBalanceCheck,
                    'customer_id' => $customer->id,
                    'amount' => $validated['amount'],
                    'credit_balance' => $customer->credit_balance
                ]);
            }
            
            // Only check balance limit if override is not true
            if (!$overrideBalanceCheck && $validated['amount'] > $customer->credit_balance) {
                return response()->json([
                    'message' => 'Payment amount cannot exceed the outstanding balance'
                ], 422);
            }

            $payment = $customer->addPayment(
                $validated['amount'],
                $validated['payment_method'],
                $validated['notes'] ?? null,
                $overrideBalanceCheck
            );

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            \Log::error('Payment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'customer_id' => $request->customer_id ?? null,
                'amount' => $request->amount ?? null,
                'payment_method' => $request->payment_method ?? null
            ]);
            
            return response()->json([
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerPayment $payment)
    {
        $payment->load('customer');
        
        return response()->json([
            'payment' => $payment
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerPayment $payment)
    {
        try {
            DB::beginTransaction();

            // Restore the customer's balance
            $customer = $payment->customer;
            $customer->increment('credit_balance', $payment->amount);

            $payment->delete();

            DB::commit();

            return response()->json([
                'message' => 'Payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete payment'
            ], 500);
        }
    }
}
