<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isAdmin(), 403);

        if ($permission === 'super') {
            abort_unless($user->isSuperAdmin(), 403, 'Bu bo\'limga faqat Bosh Admin kira oladi.');
        } elseif ($permission !== null) {
            abort_unless($user->hasPermission($permission), 403, 'Sizda ushbu bo\'limni boshqarish ruxsati yo\'q.');
        }

        return $next($request);
    }
}
