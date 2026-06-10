<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->estado !== 'activo') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'Tu usuario no está activo.',
                ]);
        }

        if (!$user->rol || !in_array($user->rol->nombre, $roles)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}