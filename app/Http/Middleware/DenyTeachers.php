<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Student spaces (Q&A, channels) are closed to teachers: always 403, including read access.
 */
class DenyTeachers
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isTeacher(), 403);

        return $next($request);
    }
}
