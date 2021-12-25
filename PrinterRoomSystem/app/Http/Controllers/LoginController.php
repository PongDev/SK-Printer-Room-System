<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserModel;

class LoginController extends Controller
{
    public function index()
    {
      if (!UserModel::isLogin())
      {
        if (UserModel::isLastLoginFailed())
        {
          UserModel::clearLastLoginFailed();
          return view('login',['loginError'=>'Login Failed']);
        }
        return view('login');
      }
      else return redirect('/');
    }

    public function login()
    {
      UserModel::login(request()->post('usr'),request()->post('pwd'));
      return redirect('/');
    }
    public function logout()
    {
      UserModel::logout();
      return redirect('/');
    }
}
