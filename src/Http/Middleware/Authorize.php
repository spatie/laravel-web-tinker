<?php

namespace Spatie\WebTinker\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return Gate::check('viewWebTinker')
            ? $next($request)
            : abort(403);
    }
}
