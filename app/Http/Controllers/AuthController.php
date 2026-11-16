<?php

namespace App\Http\Controllers;

use App\Http\Request\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginView()
    {
        return view('auth.login');
    }

    /**
     * AJAX login: returns JSON with the URL to redirect to.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password') + ['active' => 1];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'success' => false,
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'success'  => true,
            'redirect' => route('dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
