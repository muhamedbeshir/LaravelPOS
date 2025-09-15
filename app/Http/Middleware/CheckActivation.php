<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

final class CheckActivation
{
    public function handle(Request $request, Closure $next)
    {
        $activationFile = base_path('docs/activation.txt');

        // Allow access to activation routes only
        if ($request->is('activate') || $request->is('activate/submit')) {
            return $next($request);
        }

        // استثناء طلبات AJAX
        if ($request->ajax()) {
            return $next($request);
        }

        // If activation file does not exist, redirect to activation page
        if (!File::exists($activationFile)) {
            return redirect()->route('activation.form');
        }

        return $next($request);
    }
} 