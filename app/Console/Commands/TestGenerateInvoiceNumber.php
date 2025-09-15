<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\Invoice;

class TestGenerateInvoiceNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invoice-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test invoice number generation for debugging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // الحصول على الوردية الحالية المفتوحة
        $currentShift = Shift::getCurrentOpenShift();
        
        // إذا لم يتم العثور على وردية من خلال الدالة، نبحث عن أي وردية مفتوحة
        if (!$currentShift) {
            $currentShift = Shift::where('is_closed', false)->latest()->first();
            if ($currentShift) {
                $this->info("تم العثور على وردية مفتوحة باستخدام البحث المباشر.");
            }
        }
        
        if (!$currentShift) {
            $this->error('لا توجد وردية مفتوحة حاليًا');
            
            // عرض عدد جميع الورديات
            $shiftsCount = Shift::count();
            $this->info("إجمالي عدد الورديات في النظام: " . $shiftsCount);
            
            // عرض آخر وردية في النظام للتشخيص
            $lastShift = Shift::latest()->first();
            if ($lastShift) {
                $this->info("معلومات آخر وردية:");
                $this->info("ID: " . $lastShift->id);
                $this->info("الاسم: " . $lastShift->name);
                $this->info("حالة الإغلاق: " . ($lastShift->is_closed ? 'مغلقة' : 'مفتوحة'));
                $this->info("تاريخ البدء: " . $lastShift->start_time);
                $this->info("تاريخ الإنشاء: " . $lastShift->created_at);
            }
            
            return 1;
        }
        
        $this->info("معلومات الوردية الحالية:");
        $this->info("ID: " . $currentShift->id);
        $this->info("الاسم: " . $currentShift->name);
        
        // الحصول على آخر فاتورة في الوردية الحالية
        $lastInvoice = Invoice::where('shift_id', $currentShift->id)
            ->orderBy('id', 'desc')
            ->first();
        
        // عدد الفواتير في الوردية الحالية
        $invoiceCount = Invoice::where('shift_id', $currentShift->id)->count();
        $this->info("عدد الفواتير في الوردية الحالية: " . $invoiceCount);
        
        $prefix = date('Ymd');
        $nextNumber = 1;
        
        if ($lastInvoice) {
            $this->info("معلومات آخر فاتورة:");
            $this->info("ID: " . $lastInvoice->id);
            $this->info("رقم الفاتورة: " . $lastInvoice->invoice_number);
            
            // استخراج الرقم التسلسلي من رقم الفاتورة
            $lastNumber = (int) substr($lastInvoice->invoice_number, 8);
            $nextNumber = $lastNumber + 1;
            $this->info("الرقم التسلسلي المستخرج: " . $lastNumber);
        } else {
            $this->info("لا توجد فواتير في الوردية الحالية");
        }
        
        $nextInvoiceNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $this->info("رقم الفاتورة التالي: " . $nextInvoiceNumber);
        
        return 0;
    }
}
