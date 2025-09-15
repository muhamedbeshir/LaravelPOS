<?php

namespace App\Http\Controllers;

use App\Exports\SupplierStatementExport;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF; // Assuming laravel-dompdf is installed

class SupplierController extends Controller
{
    protected static array $middlewares = [
        'auth',
        'permission:view-suppliers' => ['only' => ['index', 'show']],
        'permission:create-suppliers' => ['only' => ['create', 'store']],
        'permission:edit-suppliers' => ['only' => ['edit', 'update']],
        'permission:delete-suppliers' => ['only' => ['destroy']],
    ];

    public function index()
    {
        $suppliers = Supplier::with(['invoices', 'payments'])
            ->orderBy('name')
            ->get();
            
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $supplier = Supplier::create($request->all());

            DB::commit();
            return redirect()->route('suppliers.index')
                ->with('success', 'تم إضافة المورد بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة المورد: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Request $request, Supplier $supplier)
    {
        if ($request->ajax()) {
            $supplier->load(['invoices', 'payments']);
            
            // تحضير بيانات الفواتير المستحقة مع إضافة النص العربي والفئة CSS
            $dueInvoices = $supplier->invoices()
                ->whereIn('status', ['pending', 'partially_paid'])
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function($invoice) {
                    $invoice->status_text = $invoice->getStatusText();
                    $invoice->status_class = $invoice->getStatusClass();
                    return $invoice;
                });
            
            return response()->json([
                'supplier' => $supplier,
                'dueInvoices' => $dueInvoices,
            ]);
        }

        [$statement, $startDate, $endDate] = $this->getStatementData($request, $supplier);
        return view('suppliers.show', compact('supplier', 'statement', 'startDate', 'endDate'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $supplier->update($request->all());

            DB::commit();
            return redirect()->route('suppliers.index')
                ->with('success', 'تم تحديث بيانات المورد بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات المورد: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            DB::beginTransaction();

            $supplier->delete();

            DB::commit();
            return redirect()->route('suppliers.index')
                ->with('success', 'تم حذف المورد بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المورد: ' . $e->getMessage());
        }
    }

    public function storeAsApi(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'notes' => 'nullable|string',
        ];

        $messages = [
            'name.required' => 'اسم المورد مطلوب.',
            'name.unique' => 'اسم المورد مسجل بالفعل.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $supplier = Supplier::create($validator->validated());

        return response()->json(['success' => true, 'supplier' => $supplier]);
    }

    public function addPayment(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bank_transfer,check',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment = $supplier->payments()->create($request->all());
            $supplier->updateAmounts();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدفعة بنجاح',
                'payment' => $payment,
                'supplier' => $supplier->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدفعة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // تعيين اتجاه الورقة من اليمين لليسار
            $sheet->setRightToLeft(true);

            // تنسيق العناوين
            $sheet->getStyle('A1:H1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ]);

            // إضافة العناوين
            $headers = [
                'اسم المورد',
                'اسم الشركة',
                'رقم الهاتف',
                'إجمالي المستحقات',
                'المبلغ المدفوع',
                'المبلغ المتبقي',
                'الحالة',
                'ملاحظات'
            ];
            $sheet->fromArray([$headers], NULL, 'A1');

            // جلب البيانات
            $suppliers = Supplier::all();
            $row = 2;
            foreach ($suppliers as $supplier) {
                $data = [
                    $supplier->name,
                    $supplier->company_name ?? '-',
                    $supplier->phone,
                    number_format($supplier->total_amount, 2),
                    number_format($supplier->paid_amount, 2),
                    number_format($supplier->remaining_amount, 2),
                    $supplier->getStatusText(),
                    $supplier->notes ?? '-'
                ];
                
                $sheet->fromArray([$data], NULL, 'A' . $row);
                
                // تنسيق الصف
                $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $row++;
            }

            // تعديل عرض الأعمدة
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // إنشاء ملف Excel
            $writer = new Xlsx($spreadsheet);
            $fileName = 'suppliers_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تصدير البيانات: ' . $e->getMessage());
        }
    }

    public function getNotifications()
    {
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
                'supplier' => $supplier->name,
                'total_due' => $totalDue,
                'invoices_count' => $invoices->count(),
                'nearest_due_date' => $invoices->min('due_date')
            ];
        }

        return response()->json($notifications);
    }

    public function exportStatementPDF(Request $request, Supplier $supplier)
    {
        [$statement, $startDate, $endDate] = $this->getStatementData($request, $supplier);
        $pdf = PDF::loadView('suppliers.statement-pdf', compact('supplier', 'statement', 'startDate', 'endDate'));
        return $pdf->stream('statement-' . $supplier->id . '.pdf');
    }

    public function exportStatementExcel(Request $request, Supplier $supplier)
    {
        [$statement, $startDate, $endDate] = $this->getStatementData($request, $supplier);
        return Excel::download(new SupplierStatementExport($supplier, $statement, $startDate, $endDate), 'statement-' . $supplier->id . '.xlsx');
    }

    private function getStatementData(Request $request, Supplier $supplier)
    {
        $invoices = $supplier->invoices()->get();
        $payments = $supplier->payments()->get();

        $transactions = collect([]);
        foreach ($invoices as $invoice) {
            $transactions->push([
                'sort_date' => $invoice->created_at,
                'transaction_date' => $invoice->created_at->format('Y-m-d'),
                'description' => 'فاتورة رقم: ' . $invoice->invoice_number,
                'debit' => $invoice->amount,
                'credit' => 0,
            ]);
        }
        foreach ($payments as $payment) {
            $transactions->push([
                'sort_date' => $payment->created_at,
                'transaction_date' => $payment->created_at->format('Y-m-d'),
                'description' => 'دفعة ' . $payment->getPaymentMethodText(),
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }

        $sortedTransactions = $transactions->sortBy('sort_date');

        $runningBalance = 0;
        $statement = $sortedTransactions->map(function($item) use (&$runningBalance) {
            $runningBalance += $item['debit'] - $item['credit'];
            $item['balance'] = $runningBalance;
            return $item;
        })->values();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate) {
            $statement = $statement->where('sort_date', '>=', $startDate);
        }
        if ($endDate) {
            $statement = $statement->where('sort_date', '<=', $endDate . ' 23:59:59');
        }

        return [$statement, $startDate, $endDate];
    }
} 