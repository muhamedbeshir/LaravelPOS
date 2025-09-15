<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inventory settings
        $this->createSetting('allow_negative_inventory', false, 'inventory', 'boolean');
        $this->createSetting('subtract_inventory_on_zero', false, 'inventory', 'boolean');
        
        // General settings (placeholders for future)
        $this->createSetting('company_name', 'Laravel POS', 'general', 'text');
        $this->createSetting('company_email', 'info@example.com', 'general', 'email');
        $this->createSetting('company_phone', '123-456-7890', 'general', 'text');
        
        // Sales settings (placeholders for future)
        $this->createSetting('default_tax_rate', 0, 'sales', 'number');
        
        // Printing settings (placeholders for future)
        $this->createSetting('receipt_header', 'Thank you for your purchase!', 'printing', 'textarea');
        $this->createSetting('receipt_footer', 'Visit us again soon!', 'printing', 'textarea');
        
        // Notification settings (placeholders for future)
        $this->createSetting('low_stock_notification', true, 'notifications', 'boolean');
    }
    
    private function createSetting($key, $value, $group, $type, $isPublic = true, $options = null)
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'is_public' => $isPublic,
                'options' => $options
            ]
        );
    }
} 