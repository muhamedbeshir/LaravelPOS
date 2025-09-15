<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder ensures there's always a cash customer with ID=1
     * to be used for cash invoices in the sales module.
     */
    public function run(): void
    {
        // First check if ID=1 exists (even if soft-deleted)
        $exists = DB::table('customers')->where('id', 1)->exists();
        
        if ($exists) {
            // Update the existing record
            $this->command->info('Cash customer exists. Updating...');
            
            // Force ID 1 to be available by removing soft-delete if needed
            DB::statement('UPDATE customers SET deleted_at = NULL WHERE id = 1');
            
            Customer::where('id', 1)->update([
                'name' => 'عميل نقدي',
                'phone' => '0000000000',
                'address' => 'غير محدد',
                'notes' => 'عميل افتراضي للمبيعات النقدية',
                'type' => 'retail',
                'payment_type' => 'cash',
                'credit_balance' => 0,
                'credit_limit' => 0,
                'due_days' => 0,
                'is_active' => true,
                'updated_at' => now()
            ]);
            
            $this->command->info('Cash customer (ID: 1) updated successfully.');
        } else {
            // Create a new record with forced ID=1
            $this->command->info('Creating new cash customer with ID=1...');
            
            // Temporarily disable auto-increment for customers table
            DB::statement('ALTER TABLE customers AUTO_INCREMENT = 1');
            
            // Create the cash customer
            Customer::create([
                'id' => 1,
                'name' => 'عميل نقدي',
                'phone' => '0000000000',
                'address' => 'غير محدد',
                'notes' => 'عميل افتراضي للمبيعات النقدية',
                'type' => 'retail',
                'payment_type' => 'cash',
                'credit_balance' => 0,
                'credit_limit' => 0,
                'due_days' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->command->info('Cash customer (ID: 1) created successfully.');
        }
    }
}
