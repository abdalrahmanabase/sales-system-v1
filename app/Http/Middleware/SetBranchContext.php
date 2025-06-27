<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SetBranchContext
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->branch_id) {
            session(['branch_id' => Auth::user()->branch_id]);
        }
        return $next($request);
    }
} 