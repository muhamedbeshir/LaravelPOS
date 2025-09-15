<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LowStockExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::query()
            ->where('products.is_active', true)
            ->whereRaw('stock_quantity <= alert_quantity')
            ->where('alert_quantity', '>', 0)
            ->leftJoin('purchase_items', function($join) {
                $join->on('products.id', '=', 'purchase_items.product_id')
                    ->whereRaw('purchase_items.id = (
                        SELECT id FROM purchase_items 
                        WHERE product_id = products.id 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )');
            })
            ->select(
                'products.*',
                'purchase_items.purchase_price',
                'purchase_items.selling_price'
            )
            ->orderBy('products.stock_quantity');
    }

    public function headings(): array
    {
        return [
            'المنتج',
            'المجموعة',
            'المخزون الحالي',
            'الحد الأدنى',
            'الحالة',
            'القيمة المتوقعة للطلب',
            'آخر تحديث'
        ];
    }

    public function map($product): array
    {
        $purchasePrice = $product->purchase_price ?? 0;
        $orderValue = ($product->alert_quantity - $product->stock_quantity) * $purchasePrice;
        
        return [
            $product->name,
            $product->category->name,
            number_format($product->stock_quantity, 2),
            number_format($product->alert_quantity, 2),
            $this->getStatus($product),
            number_format($orderValue, 2),
            $product->updated_at->format('Y-m-d H:i')
        ];
    }

    private function getStatus($product)
    {
        if ($product->stock_quantity <= 0) {
            return 'نفذ المخزون';
        }
        return 'منخفض';
    }
} 