<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use Illuminate\Support\Facades\Artisan;

class ClearAppCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-cache {--tag= : Specific cache tag to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application caches including tagged caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tag = $this->option('tag');

        if ($tag) {
            $availableTags = [
                'dashboard' => CacheService::TAG_DASHBOARD,
                'inventory' => CacheService::TAG_INVENTORY,
                'products' => CacheService::TAG_PRODUCTS,
                'customers' => CacheService::TAG_CUSTOMERS,
                'sales' => CacheService::TAG_SALES,
                'reports' => CacheService::TAG_REPORTS,
            ];

            if (isset($availableTags[$tag])) {
                CacheService::clearCache($availableTags[$tag]);
                $this->info("Cache for tag '{$tag}' cleared successfully.");
            } else {
                $this->error("Invalid tag: {$tag}");
                $this->line("Available tags: " . implode(', ', array_keys($availableTags)));
            }
        } else {
            // Clear all application caches
            CacheService::clearAllCache();
            
            // Also run Laravel's built-in cache clear commands
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            $this->info('All application caches cleared successfully.');
        }

        return Command::SUCCESS;
    }
} 