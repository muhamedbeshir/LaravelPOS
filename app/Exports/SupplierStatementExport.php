<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierStatementExport implements FromCollection, WithHeadings, WithStyles
{
    protected $supplier;
    protected $statement;
    protected $startDate;
    protected $endDate;

    public function __construct($supplier, $statement, $startDate, $endDate)
    {
        $this->supplier = $supplier;
        $this->statement = $statement;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return $this->statement->map(function ($item) {
            return [
                'transaction_date' => $item['transaction_date'],
                'description' => $item['description'],
                'debit' => $item['debit'] > 0 ? $item['debit'] : '',
                'credit' => $item['credit'] > 0 ? $item['credit'] : '',
                'balance' => $item['balance'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'التاريخ',
            'البيان',
            'مدين (فاتورة)',
            'دائن (دفعة)',
            'الرصيد',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
        ]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
} 