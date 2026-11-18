<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceDialogResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->boolean('_dialog') && $response instanceof RedirectResponse) {
            $target = $response->getTargetUrl();
            if (str_starts_with($target, $request->getSchemeAndHttpHost().'/') || (str_starts_with($target, '/') && ! str_starts_with($target, '//'))) {
                $response->setTargetUrl((string) Uri::of($target)->withQuery(['_dialog' => 1]));
            }
        }
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");

        return $response;
    }
}
