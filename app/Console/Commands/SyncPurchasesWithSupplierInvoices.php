<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\SupplierInvoice;
use App\Models\Supplier;

class SyncPurchasesWithSupplierInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:sync-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'مزامنة فواتير المشتريات الحالية مع فواتير الموردين';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('بدء مزامنة فواتير المشتريات مع فواتير الموردين...');

        $purchases = Purchase::whereNotNull('supplier_id')->get();
        $bar = $this->output->createProgressBar(count($purchases));
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($purchases as $purchase) {
            // التحقق مما إذا كانت فاتورة المورد موجودة بالفعل
            $supplierInvoice = SupplierInvoice::where('invoice_number', $purchase->invoice_number)
                ->where('supplier_id', $purchase->supplier_id)
                ->first();

            // تحويل قيمة status من جدول purchases إلى القيم المسموح بها في جدول supplier_invoices
            $status = $this->mapPurchaseStatusToInvoiceStatus($purchase->status, $purchase->remaining_amount);

            if ($supplierInvoice) {
                // تحديث فاتورة المورد الموجودة
                $supplierInvoice->update([
                    'amount' => $purchase->total_amount,
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $purchase->remaining_amount,
                    'status' => $status
                ]);
                $updated++;
            } else {
                // إنشاء فاتورة مورد جديدة
                SupplierInvoice::create([
                    'supplier_id' => $purchase->supplier_id,
                    'invoice_number' => $purchase->invoice_number,
                    'amount' => $purchase->total_amount,
                    'due_date' => $purchase->purchase_date ? $purchase->purchase_date->addDays(30) : now()->addDays(30),
                    'status' => $status,
                    'paid_amount' => $purchase->paid_amount,
                    'remaining_amount' => $purchase->remaining_amount,
                    'notes' => "فاتورة مشتريات رقم: {$purchase->invoice_number}"
                ]);
                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // تحديث إجماليات الموردين
        $this->info('تحديث إجماليات الموردين...');
        $suppliers = Supplier::all();
        foreach ($suppliers as $supplier) {
            // تحديث إجماليات المورد بناءً على فواتير المشتريات مباشرة
            $totalAmount = Purchase::where('supplier_id', $supplier->id)->sum('total_amount');
            $paidAmount = Purchase::where('supplier_id', $supplier->id)->sum('paid_amount');
            $remainingAmount = $totalAmount - $paidAmount;
            
            $supplier->update([
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount
            ]);
        }

        $this->info("تمت المزامنة بنجاح! تم إنشاء {$created} فاتورة جديدة وتحديث {$updated} فاتورة.");
    }

    /**
     * تحويل قيمة status من جدول purchases إلى القيم المسموح بها في جدول supplier_invoices
     */
    private function mapPurchaseStatusToInvoiceStatus($purchaseStatus, $remainingAmount)
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        } elseif ($purchaseStatus == 'partially_paid') {
            return 'partially_paid';
        } else {
            return 'pending';
        }
    }
} 