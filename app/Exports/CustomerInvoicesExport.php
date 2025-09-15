<?php

namespace App\Exports;

use App\Models\Customer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerInvoicesExport
{
    protected $customer;
    protected $startDate;
    protected $endDate;
    protected $paymentStatus;

    public function __construct(Customer $customer, $startDate = null, $endDate = null, $paymentStatus = null)
    {
        $this->customer = $customer;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->paymentStatus = $paymentStatus;
    }

    public function download($filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'رقم الفاتورة',
            'التاريخ',
            'إجمالي المبلغ',
            'حالة الدفع',
            'عدد المنتجات',
            'ملاحظات'
        ];
        
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }
        
        // Get data
        $query = $this->customer->invoices()->with('items');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        if ($this->paymentStatus) {
            $query->where('payment_status', $this->paymentStatus);
        }
        
        $invoices = $query->get();
        
        // Fill data
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->setCellValueByColumnAndRow(1, $row, $invoice->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $invoice->created_at->format('Y-m-d'));
            $sheet->setCellValueByColumnAndRow(3, $row, $invoice->total_amount);
            $sheet->setCellValueByColumnAndRow(4, $row, $invoice->payment_status);
            $sheet->setCellValueByColumnAndRow(5, $row, $invoice->items->count());
            $sheet->setCellValueByColumnAndRow(6, $row, $invoice->notes ?? '');
            $row++;
        }
        
        // Create file
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Save file to output
        $writer->save('php://output');
        exit;
    }
} 