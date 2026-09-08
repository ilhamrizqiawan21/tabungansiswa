<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class AdminRole
{
    public function handle(Request $request, Closure $next) {
        abort_unless($request->user('admin')?->isAdmin(), 403);
        return $next($request);
    }
}
