<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTransaction;
use App\Models\DeliveryStatus;
use App\Models\Invoice;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryTransactionController extends Controller
{
    /**
     * عرض قائمة معاملات الدليفري
     */
    public function index(Request $request)
    {
        $query = DeliveryTransaction::with(['invoice', 'customer', 'employee', 'status']);
        
        // تطبيق الفلاتر
        if ($request->filled('status')) {
            $status = DeliveryStatus::where('code', $request->status)->first();
            if ($status) {
                $query->where('status_id', $status->id);
            }
        }
        
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // ترتيب النتائج
        $query->orderBy('created_at', 'desc');
        
        $transactions = $query->paginate(15);
        
        $statuses = DeliveryStatus::active()->ordered()->get();
        $employees = Employee::active()->orderBy('name')->get();
        
        return view('delivery.index', compact('transactions', 'statuses', 'employees'));
    }
    
    /**
     * عرض تفاصيل معاملة دليفري
     */
    public function show(DeliveryTransaction $transaction)
    {
        try {
            $transaction->load(['invoice.items.product', 'invoice.items.unit', 'customer', 'employee', 'status']);
            
            // إذا كان الطلب بواسطة AJAX، أرجع JSON
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'transaction' => $transaction
                ]);
            }
            
            // وإلا أرجع صفحة HTML
            return view('delivery.show', compact('transaction'));
        } catch (\Exception $e) {
            Log::error("Error fetching delivery transaction {$transaction->id}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء جلب بيانات التوصيل'
                ], 500);
            }
            
            return redirect()->route('delivery-transactions.index')->with('error', 'حدث خطأ أثناء عرض تفاصيل المعاملة');
        }
    }
    
    /**
     * تحديث حالة معاملة دليفري
     */
    public function updateStatus(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'status' => 'required|string|exists:delivery_statuses,code'
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction->updateStatus($request->status, auth()->id());
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم تحديث حالة الطلبية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * إضافة دفعة لمعاملة دليفري
     */
    public function addPayment(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction->addPayment($request->amount, auth()->id());
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم إضافة الدفعة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * إلغاء معاملة دليفري (إرجاع)
     */
    public function returnDelivery(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'notes' => 'nullable|string|max:255'
        ]);
        
        try {
            DB::beginTransaction();
            
            if ($request->filled('notes')) {
                $transaction->notes = $request->notes;
                $transaction->save();
            }
            
            $transaction->updateStatus('returned', auth()->id());
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم إرجاع الطلبية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * الحصول على معاملة الدليفري بواسطة معرف الفاتورة
     */
    public function getByInvoice(Invoice $invoice)
    {
        $transaction = DeliveryTransaction::with(['invoice', 'customer', 'employee', 'status'])
            ->where('invoice_id', $invoice->id)
            ->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'معاملة الدليفري غير موجودة'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'transaction' => $transaction
        ]);
    }
    
    /**
     * تحديث وقت التوصيل يدويًا
     */
    public function updateDeliveryTime(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'delivery_time' => 'required|date'
        ]);
        
        try {
            DB::beginTransaction();
            
            // تحديث وقت التوصيل في معاملة الدليفري
            $transaction->delivery_date = $request->delivery_time;
            $transaction->save();
            
            // تحديث وقت التوصيل في الفاتورة
            $transaction->invoice->delivery_time = $request->delivery_time;
            $transaction->invoice->save();
            
            // إذا لم تكن الحالة "تم التوصيل" بالفعل، قم بتحديثها
            if ($transaction->status->code !== 'delivered_pending_payment') {
                $transaction->updateStatus('delivered_pending_payment', auth()->id());
            }
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم تحديث وقت التوصيل بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * تحديث معلومات الشحن لمعاملة دليفري
     */
    public function update(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'shipping_company_id' => 'nullable|exists:shipping_companies,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tracking_number' => 'nullable|string|max:255',
            'shipped_at' => 'nullable|date',
            'estimated_delivery_date' => 'nullable|date'
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction->shipping_company_id = $request->shipping_company_id;
            $transaction->shipping_cost = $request->shipping_cost;
            $transaction->tracking_number = $request->tracking_number;
            $transaction->shipped_at = $request->shipped_at;
            $transaction->estimated_delivery_date = $request->estimated_delivery_date;
            $transaction->save();
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم تحديث معلومات الشحن بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * تحديث حالة الشحن لمعاملة دليفري
     */
    public function updateShippingStatus(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'shipping_status' => 'required|string|exists:shipping_statuses,code'
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction->updateShippingStatus($request->shipping_status, auth()->id());
            
            DB::commit();
            
            return redirect()->back()->with('success', 'تم تحديث حالة الشحن بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all incomplete delivery transactions and display them in the index view.
     */
    public function getIncompleteOrders()
    {
        $query = DeliveryTransaction::with(['invoice', 'customer', 'employee', 'status'])
            ->whereHas('status', function($q) {
                $q->whereNotIn('code', ['paid', 'returned', 'cancelled']);
            })
            ->orderBy('created_at', 'desc');

        $transactions = $query->paginate(15);
        
        $statuses = DeliveryStatus::active()->ordered()->get();
        $employees = Employee::active()->orderBy('name')->get();
        $pageTitle = 'طلبات الدليفري الجارية';

        return view('delivery.index', compact('transactions', 'statuses', 'employees', 'pageTitle'));
    }

    /**
     * Get all delivery transactions for the current active shift and display them in the index view.
     */
    public function getCurrentShiftTransactions(Request $request)
    {
        $currentShift = Shift::getCurrentOpenShift();

        if (!$currentShift) {
            return redirect()->route('delivery-transactions.index')->with('warning', 'لا توجد وردية مفتوحة حاليًا.');
        }

        $query = DeliveryTransaction::with(['invoice', 'customer', 'employee', 'status'])
            ->where('created_at', '>=', $currentShift->start_time)
            ->orderBy('created_at', 'desc');
        
        // If the shift is closed, only show transactions up to the end time.
        if ($currentShift->end_time) {
            $query->where('created_at', '<=', $currentShift->end_time);
        }

        $transactions = $query->paginate(15);

        $statuses = DeliveryStatus::active()->ordered()->get();
        $employees = Employee::active()->orderBy('name')->get();
        $pageTitle = 'طلبات الدليفري للوردية الحالية';
        
        return view('delivery.index', compact('transactions', 'statuses', 'employees', 'pageTitle'));
    }

    /**
     * Update the status of the specified delivery transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeliveryTransaction  $transaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeliveryStatus(Request $request, DeliveryTransaction $transaction)
    {
        $request->validate([
            'status' => 'required|string|exists:delivery_statuses,code',
            'amount' => 'nullable|numeric|min:0',
            'notes'  => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $statusCode = $request->input('status');
            
            // Call the model's method to update status and related timestamps
            $transaction->updateStatus($statusCode, auth()->id());

            // Handle specific logic for paid or returned statuses if necessary
            if ($statusCode === 'paid') {
                $amount = $request->input('amount');
                if ($amount !== null) {
                    // Assuming you might have a separate payment recording logic or want to update collected_amount directly
                    // For now, the model's updateStatus already handles setting is_paid and payment_date
                    // If partial payments are possible and need to be recorded differently, this is where you'd do it.
                    // Let's assume for now that $transaction->updateStatus('paid') correctly sets collected_amount to total amount.
                    // If the provided amount is different, you might log it or throw an error if it doesn't match.
                    if (isset($data['amount'])) { // If amount is provided in request (as per JS)
                        // Validate if $data['amount'] matches $transaction->amount for full payment
                        // Or handle partial payment logic if supported
                        // For simplicity, we assume the model's updateStatus handles the full payment logic correctly.
                    }
                }
            } elseif ($statusCode === 'returned') {
                if ($request->filled('notes')) {
                    $transaction->notes = ($transaction->notes ? $transaction->notes . "\n" : "") . "Return Notes: " . $request->input('notes');
                }
                 // The model's updateStatus already handles setting is_returned and return_date, and stock return.
            }
            
            $transaction->save(); // Save any additional changes like notes

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم تحديث حالة التوصيل بنجاح.', 'transaction' => $transaction->fresh(['invoice', 'customer', 'employee', 'status'])]);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error("Validation error updating delivery status for transaction {$transaction->id}: " . $e->getMessage(), $e->errors());
            return response()->json(['success' => false, 'message' => 'خطأ في البيانات المدخلة.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating delivery status for transaction {$transaction->id}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الحالة.'], 500);
        }
    }
} 