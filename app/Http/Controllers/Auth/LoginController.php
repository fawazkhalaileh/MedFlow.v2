<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect($this->roleHome(Auth::user()));
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect($this->roleHome(Auth::user()));
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function roleHome($user): string
    {
        $type = $user->employee_type ?? $user->role ?? 'admin';

        return match($type) {
            'branch_manager' => route('operations'),
            'secretary'      => route('front-desk'),
            'technician'     => route('my-queue'),
            'doctor', 'nurse'=> route('review-queue'),
            'finance'        => route('finance'),
            default          => route('dashboard'),
        };
    }
}
