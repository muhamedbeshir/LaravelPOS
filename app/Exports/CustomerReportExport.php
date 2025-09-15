<?php

namespace App\Exports;

use App\Models\Customer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerReportExport
{
    protected $type;
    protected $startDate;
    protected $endDate;

    public function __construct($type = 'all', $startDate = null, $endDate = null)
    {
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function download($filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'اسم العميل',
            'رقم الهاتف',
            'نوع العميل',
            'عدد الفواتير',
            'إجمالي المبيعات',
            'إجمالي المدفوعات',
            'الرصيد المتبقي'
        ];
        
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(65 + $index) . '1', $header);
        }
        
        // Get data
        $query = Customer::query()
            ->withCount('invoices')
            ->withSum('invoices', 'total')
            ->withSum('payments', 'amount');

        if ($this->type === 'credit') {
            $query->credit()->withDueBalance();
        }

        if ($this->startDate && $this->endDate) {
            $query->whereHas('invoices', function($q) {
                $q->whereBetween('created_at', [$this->startDate, $this->endDate]);
            });
        }

        $customers = $query->get();
        
        // Fill data
        $row = 2;
        foreach ($customers as $customer) {
            $sheet->setCellValue('A' . $row, $customer->name);
            $sheet->setCellValue('B' . $row, $customer->phone);
            $sheet->setCellValue('C' . $row, $customer->payment_type);
            $sheet->setCellValue('D' . $row, $customer->invoices_count);
            $sheet->setCellValue('E' . $row, $customer->invoices_sum_total ?? 0);
            $sheet->setCellValue('F' . $row, $customer->payments_sum_amount ?? 0);
            $sheet->setCellValue('G' . $row, $customer->credit_balance);
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