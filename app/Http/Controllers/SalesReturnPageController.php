<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class SalesReturnPageController extends Controller
{
    /**
     * Display the sales returns index page.
     */
    public function index(): View
    {
        // Ensure the user has permission to view this page.
        // The route middleware already checks this, but an explicit check here is good practice.
        if (Gate::denies('manage-sales-returns')) {
            abort(403, 'Unauthorized action.');
        }
        \Log::info('Sales returns index page accessed');
        return view('sales_returns.index');
    }
} 