<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateSupplierBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier:update-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all supplier balances taking into account purchase returns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update supplier balances...');
        
        $suppliers = \App\Models\Supplier::all();
        $count = 0;
        
        foreach ($suppliers as $supplier) {
            $this->info("Updating supplier: {$supplier->name}");
            $supplier->updateAmounts();
            $count++;
        }
        
        $this->info("Completed! Updated {$count} supplier balances.");
        
        return 0;
    }
}
