<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSoftDeletedCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:cleanup {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently remove soft-deleted customers from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $softDeletedCount = Customer::onlyTrashed()->count();
        
        if ($softDeletedCount === 0) {
            $this->info('No soft-deleted customers found.');
            return 0;
        }
        
        $this->info("Found {$softDeletedCount} soft-deleted customers.");
        
        if (!$this->option('force') && !$this->confirm('Do you wish to permanently delete these records?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        $this->info('Cleaning up soft-deleted customers...');
        
        // Handle related invoices first
        $this->info('Updating related invoices...');
        $customers = Customer::onlyTrashed()->get();
        
        foreach ($customers as $customer) {
            $invoicesCount = DB::table('invoices')
                ->where('customer_id', $customer->id)
                ->count();
                
            if ($invoicesCount > 0) {
                $this->info("Customer ID {$customer->id} has {$invoicesCount} invoices. Setting customer_id to NULL.");
                
                DB::table('invoices')
                    ->where('customer_id', $customer->id)
                    ->update([
                        'customer_id' => null,
                        'updated_at' => now()
                    ]);
            }
        }
        
        // Now permanently delete the customers
        $this->info('Permanently deleting customers...');
        $deleted = Customer::onlyTrashed()->forceDelete();
        
        $this->info("Successfully deleted {$deleted} customers.");
        
        return 0;
    }
}
