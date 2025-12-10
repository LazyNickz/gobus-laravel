<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminIsAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->get('gobus_admin_logged')) {
            return redirect('/admin-login');
        }
        return $next($request);
    }
}
