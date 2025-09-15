<?php

namespace App\Exports;

use App\Models\StockMovement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockMovementsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return StockMovement::query()
            ->with(['product', 'unit', 'employee'])
            ->whereBetween('created_at', [$this->startDate->startOfDay(), $this->endDate->endOfDay()])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'المنتج',
            'الكمية',
            'الوحدة',
            'نوع الحركة',
            'المرجع',
            'الموظف',
            'التاريخ',
            'ملاحظات'
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->product->name,
            number_format($movement->quantity, 2),
            $movement->unit->name,
            $movement->movement_type == 'in' ? 'وارد' : 'منصرف',
            $this->getReference($movement),
            $movement->employee->name,
            $movement->created_at->format('Y-m-d H:i'),
            $movement->notes
        ];
    }

    private function getReference($movement)
    {
        if ($movement->reference_type == 'purchase') {
            return 'فاتورة شراء #' . optional($movement->reference)->invoice_number;
        }
        return $movement->reference_type;
    }
} 