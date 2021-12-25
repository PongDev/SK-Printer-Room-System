<?php

namespace App\Http\Middleware;

use Closure;
use App\UserModel;

class RequireAdmin
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
        $userData=UserModel::userData();

        if (!isset($userData)||UserModel::rankidtostr($userData->Rank)!=="Admin")
        {
          return redirect('/');
        }
        return $next($request);
    }
}
