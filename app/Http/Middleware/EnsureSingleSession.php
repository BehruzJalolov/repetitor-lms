<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * O'quvchi akkaunti bir vaqtda faqat bitta qurilmada ishlaydi.
 * Boshqa qurilmadan kirilsa, eski sessiya avtomatik tugatiladi.
 */
class EnsureSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            // Agar foydalanuvchi "Meni eslab qol" (remember_token) orqali qayta kirgan bo'lsa,
            // yangi sessiya ID sini saqlab qo'yamiz (boshqa qurilmadan kirilganda remember_token yangilanadi).
            if (Auth::viaRemember()) {
                $user->forceFill(['current_session_id' => $request->session()->getId()])->save();
            } elseif ($user->current_session_id && $user->current_session_id !== $request->session()->getId()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Akkauntingizga boshqa qurilmadan kirildi. Iltimos, qayta kiring.');
            }
        }

        return $next($request);
    }
}
