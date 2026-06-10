<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $recordar = $request->boolean('remember');

        if (Auth::attempt($credenciales, $recordar)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors([
                'username' => 'Las credenciales no son correctas.',
            ])
            ->onlyInput('username');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user->rol) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'El usuario no tiene un rol asignado.',
                ]);
        }

        return match ($user->rol->nombre) {
            'Administrador' => redirect()->route('dashboard.admin'),
            'Coordinador' => redirect()->route('dashboard.coordinador'),
            'Docente' => redirect()->route('dashboard.docente'),
            'Postulante' => redirect()->route('dashboard.postulante'),
            default => abort(403, 'Rol no autorizado.'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}