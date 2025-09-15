<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ProductSeeder;

class GenerateProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate 2000 products with 3 units each and 3 price types per unit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to generate products...');
        
        // Run the ProductSeeder
        $seeder = new ProductSeeder();
        $seeder->setContainer($this->laravel);
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Products generated successfully!');
        
        return Command::SUCCESS;
    }
} 