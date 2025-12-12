<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class BypassCsrfForAdmin extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Bypass CSRF for admin routes
        if ($request->is('admin-login') || 
            $request->is('admin/*') || 
            $request->is('admin/check-session')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin-login',
        'admin/schedules/*',
        'admin/check-session',
    ];
}
