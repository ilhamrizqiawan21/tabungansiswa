<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function create(): Response { return Inertia::render('Auth/Login'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:50'], 'password' => ['required', 'string']]);
        $key = 'login:' . strtolower($data['username']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['username' => 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.']);
        if (!Auth::guard('admin')->attempt(['username' => $data['username'], 'password' => $data['password']])) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages(['username' => 'Username atau password tidak valid.']);
        }
        RateLimiter::clear($key); $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
