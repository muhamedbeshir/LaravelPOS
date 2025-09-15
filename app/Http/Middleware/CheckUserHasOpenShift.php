<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

class CheckUserHasOpenShift
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $currentOpenShift = Shift::getCurrentOpenShift();

        if (!$currentOpenShift) {
            return redirect()->route('shifts.create')
                ->with('warning', 'يجب فتح وردية جديدة أولاً للوصول إلى نقطة البيع.');
        }

        return $next($request);
    }
}
