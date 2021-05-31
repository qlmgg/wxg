<?php

namespace App\Http\Middleware;

use App\Models\Worker;
use Closure;
use Illuminate\Http\Request;

class AdminWorkerRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /**
         * @var Worker $worker
         */
        $worker = $request->user();

        if (in_array($worker->type, [1, 2], true)) {
            return $next($request);
        }

        abort(403, "只能是后台管理和区域经理能访问");
    }
}
