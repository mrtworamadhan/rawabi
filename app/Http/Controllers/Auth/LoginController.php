<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.custom-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();


            if ($user->hasRole('super_admin')) {
                return redirect('/admin');
            }
            
            if ($user->hasRole('owner') || $user->hasRole('bod')) {
                return redirect()->route('dashboard.executive');
            }

            if ($user->hasRole('finance')) {
                return redirect()->route('finance.pos');
            }

            if ($user->hasRole('marketing')) {
                return redirect()->route('marketing.salesApp');
            }

            if ($user->hasRole('operasional') || $user->hasRole('staff_ops')) {
                return redirect()->route('operations.dashboard');
            }

            if ($user->hasRole('media') || $user->hasRole('editor')) {
                return redirect()->route('media.studio');
            }

            return redirect('/'); 
        }

        return back()->withErrors([
            'email' => 'Email atau password salah bro.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}