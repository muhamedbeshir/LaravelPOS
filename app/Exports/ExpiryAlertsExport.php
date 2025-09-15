<?php

namespace App\Exports;

use App\Models\Product;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExpiryAlertsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()
            ->where('products.is_active', true)
            ->join('purchase_items', function($join) {
                $join->on('products.id', '=', 'purchase_items.product_id')
                    ->whereNotNull('purchase_items.expiry_date')
                    ->whereRaw('purchase_items.id = (
                        SELECT id FROM purchase_items 
                        WHERE product_id = products.id 
                        AND expiry_date IS NOT NULL
                        ORDER BY expiry_date ASC 
                        LIMIT 1
                    )');
            })
            ->where(function($query) {
                $query->where('purchase_items.expiry_date', '<=', Carbon::now()->addDays(30))
                      ->where('purchase_items.expiry_date', '>', Carbon::now());
            })
            ->orWhere('purchase_items.expiry_date', '<', Carbon::now())
            ->select('products.*', 
                    'purchase_items.expiry_date',
                    'purchase_items.production_date',
                    'purchase_items.purchase_price')
            ->orderBy('purchase_items.expiry_date');
    }

    public function headings(): array
    {
        return [
            'المنتج',
            'المجموعة',
            'الكمية',
            'تاريخ الإنتاج',
            'تاريخ الصلاحية',
            'الأيام المتبقية',
            'الحالة',
            'القيمة',
            'آخر تحديث'
        ];
    }

    public function map($product): array
    {
        $daysLeft = $product->expiry_date ? Carbon::parse($product->expiry_date)->diffInDays(Carbon::now(), false) : null;
        $purchasePrice = $product->purchase_price ?? 0;
        $value = $product->stock_quantity * $purchasePrice;

        return [
            $product->name,
            $product->category->name,
            number_format($product->stock_quantity, 2),
            $product->production_date ? Carbon::parse($product->production_date)->format('Y-m-d') : '-',
            $product->expiry_date ? Carbon::parse($product->expiry_date)->format('Y-m-d') : '-',
            $daysLeft !== null ? $daysLeft : '-',
            $this->getStatus($product),
            number_format($value, 2),
            $product->updated_at->format('Y-m-d H:i')
        ];
    }

    private function getStatus($product)
    {
        if (!$product->expiry_date) {
            return 'غير محدد';
        }

        $expiryDate = Carbon::parse($product->expiry_date);
        if ($expiryDate->isPast()) {
            return 'منتهي الصلاحية';
        }

        $daysLeft = Carbon::now()->diffInDays($expiryDate);
        if ($daysLeft <= 7) {
            return 'ينتهي خلال أسبوع';
        }
        if ($daysLeft <= 30) {
            return 'ينتهي خلال شهر';
        }

        return 'سليم';
    }
} 