<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\User;
use App\Models\Funciones;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
   // protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
   /* public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }*/

    public function login(Request $request) {
        if(Auth::user())
            return redirect()->route('home');
        return $this->authenticate($request);
    }

    public function authenticate(Request $params)
    {
        $this->validate($params, [
            'email' => 'required', 'password' => 'required',
        ]);
        if (Auth::attempt(['email' => $params['email'], 'password' => $params['password']])) {
            //$params->session()->regenerate();
            Auth::guard();

            $user = Auth::user();
            // Authentication passed...
            return redirect()->route('home');

        }
        return $this->sendFailedLoginResponse($params);
    }

    public function getViewLogin() {
        if(Auth::user())
        {

            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function logout(Request $request) {
        Auth::logout();
        //$request->session()->invalidate();
        //$request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => Lang::get('auth.failed'),
            ]);
    }
}
