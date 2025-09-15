<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        return view('supplier-payments.create', compact('suppliers'));
    }

    public function getSupplierInvoices($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $invoices = $supplier->invoices()
                             ->where('status', '!=', 'paid')
                             ->orderBy('due_date', 'asc')
                             ->get();

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'allocations' => 'nullable|array',
            'allocations.*' => 'numeric|min:0',
        ]);

        $totalAllocated = collect($request->allocations)->sum();

        if ($totalAllocated > $request->amount) {
            return back()->withInput()->withErrors(['amount' => 'إجمالي المبلغ المخصص لا يمكن أن يتجاوز مبلغ الدفعة.']);
        }

        DB::transaction(function () use ($request) {
            $supplier = Supplier::findOrFail($request->supplier_id);

            // 1. Create the payment
            $payment = $supplier->payments()->create([
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'created_at' => $request->payment_date
            ]);

            // 2. Allocate payment to invoices
            if ($request->allocations) {
                foreach ($request->allocations as $invoiceId => $allocatedAmount) {
                    if ($allocatedAmount > 0) {
                        $invoice = SupplierInvoice::findOrFail($invoiceId);
                        
                        // Ensure we don't over-allocate
                        $amountToAllocate = min($allocatedAmount, $invoice->remaining_amount);
                        
                        if ($amountToAllocate > 0) {
                            // Create allocation record
                            $payment->allocatedInvoices()->attach($invoiceId, ['amount' => $amountToAllocate]);

                            // Update invoice
                            $invoice->addPayment($amountToAllocate);
                            
                            // تحديث فاتورة المشتريات المقابلة
                            $purchase = \App\Models\Purchase::where('supplier_id', $supplier->id)
                                ->where('invoice_number', $invoice->invoice_number)
                                ->first();
                                
                            if ($purchase) {
                                $purchase->paid_amount += $amountToAllocate;
                                $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;
                                
                                // تحديث حالة الفاتورة
                                if ($purchase->remaining_amount <= 0) {
                                    $purchase->status = 'paid';
                                } elseif ($purchase->paid_amount > 0) {
                                    $purchase->status = 'partially_paid';
                                } else {
                                    $purchase->status = 'pending';
                                }
                                
                                $purchase->save();
                            }
                        }
                    }
                }
            }

            // 3. Update supplier's total amounts
            $supplier->updateAmounts();
        });

        return redirect()->route('suppliers.show', $request->supplier_id)->with('success', 'تم تسجيل الدفعة بنجاح.');
    }
}
