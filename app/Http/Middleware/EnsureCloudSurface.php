<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCloudSurface
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminHost = trim((string) config('app.admin_host', ''));

        if ($adminHost === '' || strcasecmp($request->getHost(), $adminHost) !== 0) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->is_platform_admin) {
            return redirect()->route('admin.dashboard');
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');

        if ($appUrl !== '') {
            return redirect()->away($appUrl.$request->getRequestUri());
        }

        abort(404);
    }
}
