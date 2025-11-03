<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $USERNAME = env('API_USERNAME', 'admin'); // default username
        $PASSWORD = env('API_PASSWORD', 'secret'); // default password

        $hasSuppliedCredentials = $request->getUser() && $request->getPassword();
        $credentialsAreValid = $request->getUser() === $USERNAME && $request->getPassword() === $PASSWORD;


        if (! $hasSuppliedCredentials || ! $credentialsAreValid) {
            return response('Unauthorized', 401)
                ->header('WWW-Authenticate', 'Basic realm="Local API"');
        }

        return $next($request);
    }
}
