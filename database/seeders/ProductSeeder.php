<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Unit;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductUnitPrice;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 15 categories
        $this->command->info('Creating 15 categories...');
        $categories = [];
        $categoryNames = [
            'الإلكترونيات', 'الأجهزة المنزلية', 'الملابس', 'الأحذية', 'الأثاث',
            'مستلزمات المطبخ', 'منتجات التجميل', 'العطور', 'الأدوات المكتبية', 'الألعاب',
            'الأدوات الرياضية', 'الكتب', 'المواد الغذائية', 'المشروبات', 'منتجات التنظيف'
        ];
        
        foreach ($categoryNames as $name) {
            $category = Category::create([
                'name' => $name,
                'color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                'is_active' => true
            ]);
            $categories[] = $category->id;
        }
        
        // Create base units if they don't exist
        $this->command->info('Creating units...');
        $units = [];
        $unitNames = [
            'قطعة' => 'piece',
            'علبة' => 'box',
            'كرتون' => 'carton',
            'كيلوجرام' => 'kg',
            'جرام' => 'g',
            'لتر' => 'l',
            'مللتر' => 'ml',
            'متر' => 'm',
            'سنتيمتر' => 'cm',
            'زجاجة' => 'bottle',
            'عبوة' => 'pack',
            'كيس' => 'bag',
            'طقم' => 'set',
            'حزمة' => 'bundle',
            'دستة' => 'dozen'
        ];
        
        foreach ($unitNames as $name => $code) {
            $unit = Unit::firstOrCreate(
                ['name' => $name],
                [
                    'code' => $code,
                    'is_base_unit' => true,
                    'parent_unit_id' => null,
                    'conversion_factor' => 1,
                    'is_active' => true
                ]
            );
            $units[] = $unit->id;
        }
        
        // Create price types if they don't exist
        $this->command->info('Creating price types...');
        $priceTypeData = [
            ['name' => 'سعر التجزئة', 'code' => 'retail', 'is_default' => true],
            ['name' => 'سعر الجملة', 'code' => 'wholesale', 'is_default' => false],
            ['name' => 'سعر خاص', 'code' => 'special', 'is_default' => false]
        ];
        
        $priceTypes = [];
        foreach ($priceTypeData as $data) {
            $priceType = PriceType::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'is_default' => $data['is_default'],
                    'is_active' => true,
                    'sort_order' => count($priceTypes) + 1
                ]
            );
            $priceTypes[] = $priceType->id;
        }
        
        // Generate 2000 products with 3 units each and 3 price types per unit
        $this->command->info('Generating 2000 products with 3 units each and 3 price types per unit...');
        $this->command->getOutput()->progressStart(2000);
        
        $productPrefixes = [
            'ممتاز', 'سوبر', 'كلاسيك', 'برو', 'بريميوم', 'ديلوكس', 'اكسترا',
            'الترا', 'ميجا', 'رويال', 'سوبريم', 'بلس', 'ماكس', 'كومفورت', 'لايت'
        ];
        
        $productSuffixes = [
            'الأصلي', 'الجديد', 'المطور', 'الاقتصادي', 'الفاخر', 'المميز',
            'الكلاسيكي', 'العائلي', 'الصغير', 'الكبير', 'المتوسط', 'الخاص'
        ];
        
        $productTypes = [
            'تلفزيون', 'ثلاجة', 'غسالة', 'مكيف', 'قميص', 'بنطلون', 'حذاء', 'كرسي',
            'طاولة', 'سرير', 'مقلاة', 'قدر', 'كريم', 'عطر', 'قلم', 'دفتر', 'لعبة',
            'كرة', 'كتاب', 'أرز', 'سكر', 'زيت', 'مشروب', 'عصير', 'منظف'
        ];
        
        for ($i = 1; $i <= 2000; $i++) {
            // Generate product name
            $prefix = $productPrefixes[array_rand($productPrefixes)];
            $type = $productTypes[array_rand($productTypes)];
            $suffix = $productSuffixes[array_rand($productSuffixes)];
            $productName = $prefix . ' ' . $type . ' ' . $suffix;
            
            // Generate barcode
            $barcode = mt_rand(1000000000, 9999999999);
            
            // Create product
            $product = Product::create([
                'name' => $productName,
                'category_id' => $categories[array_rand($categories)],
                'barcode' => $barcode,
                'alert_quantity' => mt_rand(5, 20),
                'has_serial' => mt_rand(0, 1),
                'serial_number' => mt_rand(0, 1) ? 'SN' . mt_rand(10000, 99999) : null,
                'is_active' => true
            ]);
            
            // Add 3 units to the product
            $selectedUnits = array_rand($units, 3);
            foreach ($selectedUnits as $index => $unitIndex) {
                $unitId = $units[$unitIndex];
                
                // Create product unit
                $productUnit = ProductUnit::create([
                    'product_id' => $product->id,
                    'unit_id' => $unitId,
                    'barcode' => $barcode . chr(65 + $index),
                    'is_main_unit' => $index === 0 ? true : false,
                    'stock_quantity' => mt_rand(10, 100),
                    'is_active' => true
                ]);
                
                // Base cost for the unit
                $baseCost = mt_rand(10, 100);
                
                // Add 3 price types to the unit
                foreach ($priceTypes as $priceTypeId) {
                    // Calculate price with profit margin based on price type
                    $margin = $priceTypeId === $priceTypes[0] ? 1.3 : ($priceTypeId === $priceTypes[1] ? 1.2 : 1.1);
                    $price = round($baseCost * $margin, 2);
                    
                    ProductUnitPrice::create([
                        'product_unit_id' => $productUnit->id,
                        'price_type_id' => $priceTypeId,
                        'value' => $price
                    ]);
                }
            }
            
            $this->command->getOutput()->progressAdvance();
        }
        
        $this->command->getOutput()->progressFinish();
        $this->command->info('Products seeding completed successfully!');
    }
} 