<?php


namespace App\Http\Middleware;


use App\Models\Worker;
use Closure;
use Illuminate\Http\Request;

class WorkerAuthMiddleware
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

        if ($worker->status==1) {
            return $next($request);
        }

        abort(403, "员工账号已禁用");
    }
}
