<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    private int $timeoutMinutes = 120;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');

            if ($lastActivity && (time() - $lastActivity) > ($this->timeoutMinutes * 60)) {
                Auth::logout();
                session()->flush();
                return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
            }

            session(['last_activity_time' => time()]);
        }

        return $next($request);
    }
}
