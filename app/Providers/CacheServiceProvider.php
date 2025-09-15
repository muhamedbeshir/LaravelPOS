<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\StockMovement;
use App\Models\Category;
use App\Services\CacheService;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // When a product is saved or deleted, clear product-related caches
        Product::saved(function () {
            CacheService::clearCache([
                CacheService::TAG_PRODUCTS,
                CacheService::TAG_INVENTORY,
                CacheService::TAG_DASHBOARD
            ]);
        });

        Product::deleted(function () {
            CacheService::clearCache([
                CacheService::TAG_PRODUCTS,
                CacheService::TAG_INVENTORY,
                CacheService::TAG_DASHBOARD
            ]);
        });

        // When a customer is saved or deleted, clear customer-related caches
        Customer::saved(function () {
            CacheService::clearCache([
                CacheService::TAG_CUSTOMERS,
                CacheService::TAG_DASHBOARD
            ]);
        });

        Customer::deleted(function () {
            CacheService::clearCache([
                CacheService::TAG_CUSTOMERS,
                CacheService::TAG_DASHBOARD
            ]);
        });

        // When an invoice is saved or deleted, clear sales and dashboard caches
        Invoice::saved(function () {
            CacheService::clearCache([
                CacheService::TAG_SALES,
                CacheService::TAG_DASHBOARD,
                CacheService::TAG_REPORTS
            ]);
        });

        Invoice::deleted(function () {
            CacheService::clearCache([
                CacheService::TAG_SALES,
                CacheService::TAG_DASHBOARD,
                CacheService::TAG_REPORTS
            ]);
        });

        // When a stock movement is recorded, clear inventory and dashboard caches
        StockMovement::saved(function () {
            CacheService::clearCache([
                CacheService::TAG_INVENTORY,
                CacheService::TAG_PRODUCTS,
                CacheService::TAG_DASHBOARD
            ]);
        });

        // When a category is updated, clear product and category-related caches
        Category::saved(function () {
            CacheService::clearCache([
                CacheService::TAG_PRODUCTS,
                CacheService::TAG_INVENTORY
            ]);
        });

        Category::deleted(function () {
            CacheService::clearCache([
                CacheService::TAG_PRODUCTS,
                CacheService::TAG_INVENTORY
            ]);
        });
    }
} 