<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->get('gobus_user_logged')) {
            // preserve intended url
            $intended = $request->getRequestUri();
            return redirect('/user-login?next=' . urlencode($intended));
        }
        return $next($request);
    }
}
