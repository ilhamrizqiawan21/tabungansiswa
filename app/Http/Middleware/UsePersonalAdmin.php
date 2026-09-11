<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UsePersonalAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Admin::query()->where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            $admin = Admin::create([
                'username' => 'personal-'.Str::uuid(),
                'password' => Hash::make(Str::random(40)),
                'nama' => 'Pengelola',
                'role' => 'admin',
            ]);
        }

        Auth::guard('admin')->setUser($admin);

        return $next($request);
    }
}
