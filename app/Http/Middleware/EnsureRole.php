<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->session()->get('auth_role');

        if (! $role) {
            return redirect()->route('login');
        }

        if (! in_array($role, $roles, true)) {
            return redirect()->route($role === 'superadmin' ? 'superadmin.seleksi-admin' : 'dashboard')
                ->with('error', 'Akun Anda tidak punya akses ke fitur tersebut.');
        }

        return $next($request);
    }
}
