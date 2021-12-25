<?php

namespace App\Http\Middleware;

use Closure;
use App\UserModel;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!UserModel::isLogin())
        {
          return redirect('/login');
        }
        return $next($request);
    }
}
