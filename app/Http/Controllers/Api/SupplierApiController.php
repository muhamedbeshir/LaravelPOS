<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SupplierApiController extends Controller
{
    /**
     * Get all suppliers
     */
    public function getAllSuppliers(Request $request)
    {
        try {
            $query = Supplier::with(['invoices', 'payments']);
            
            // Filter by keyword search
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Filter by status (has remaining balance)
            if ($request->has('has_balance') && $request->boolean('has_balance')) {
                $query->where('remaining_amount', '>', 0);
            }
            
            // Order by
            $orderBy = $request->input('order_by', 'name');
            $orderDirection = $request->input('order_direction', 'asc');
            if (in_array($orderBy, ['name', 'company_name', 'created_at', 'remaining_amount'])) {
                $query->orderBy($orderBy, $orderDirection);
            }
            
            // Pagination
            $perPage = $request->input('per_page', 15);
            $suppliers = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'suppliers' => $suppliers
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching suppliers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching suppliers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific supplier
     */
    public function getSupplier($id)
    {
        try {
            $supplier = Supplier::with(['invoices', 'payments'])->findOrFail($id);
            
            // Add due and upcoming invoices
            $dueInvoices = $supplier->getDueInvoices();
            $upcomingInvoices = $supplier->getUpcomingInvoices();
            
            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'due_invoices' => $dueInvoices,
                'upcoming_invoices' => $upcomingInvoices
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching supplier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found or error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Create a new supplier
     */
    public function storeSupplier(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'phone' => 'required|string|max:20',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $supplier = Supplier::create($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully',
                'supplier' => $supplier
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating supplier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a supplier
     */
    public function updateSupplier(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'phone' => 'required|string|max:20',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $supplier = Supplier::findOrFail($id);
            $supplier->update($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully',
                'supplier' => $supplier->fresh()
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating supplier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a supplier
     */
    public function deleteSupplier($id)
    {
        try {
            DB::beginTransaction();
            
            $supplier = Supplier::findOrFail($id);
            
            // Check if supplier has invoices or payments
            if ($supplier->invoices()->count() > 0 || $supplier->payments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with invoices or payments'
                ], 422);
            }
            
            $supplier->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting supplier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add payment for a supplier
     */
    public function addPayment(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|in:cash,bank_transfer,check',
                'reference_number' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            DB::beginTransaction();
            
            $supplier = Supplier::findOrFail($id);
            
            // Create payment
            $payment = $supplier->payments()->create($request->all());
            
            // Update supplier amounts
            $supplier->updateAmounts();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment' => $payment,
                'supplier' => $supplier->fresh()
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding supplier payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding supplier payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get payments for a supplier
     */
    public function getPayments($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $payments = $supplier->payments()->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'supplier' => $supplier->only(['id', 'name', 'company_name']),
                'payments' => $payments
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching supplier payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching supplier payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get supplier invoices
     */
    public function getInvoices($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $invoices = $supplier->invoices()->with(['items.product'])->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'supplier' => $supplier->only(['id', 'name', 'company_name']),
                'invoices' => $invoices
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching supplier invoices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching supplier invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get notification data for due invoices
     */
    public function getNotifications()
    {
        try {
            $dueInvoices = SupplierInvoice::with('supplier')
                ->whereIn('status', ['pending', 'partially_paid'])
                ->where('due_date', '<=', now()->addDays(7))
                ->get()
                ->groupBy('supplier_id');
    
            $notifications = [];
            foreach ($dueInvoices as $supplierId => $invoices) {
                $supplier = $invoices->first()->supplier;
                $totalDue = $invoices->sum('remaining_amount');
                
                $notifications[] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplier->name,
                    'total_due' => $totalDue,
                    'invoices_count' => $invoices->count(),
                    'nearest_due_date' => $invoices->min('due_date')
                ];
            }
    
            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching supplier notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching supplier notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 