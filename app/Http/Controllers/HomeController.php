<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\HomeDashboardService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the role-aware application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(HomeDashboardService $dashboard)
    {
        $user = Auth::user();

        if ($user->isFacilitador()) {
            return view('home', ['dashboard' => $dashboard->forFacilitator($user)]);
        }

        if ($user->isParticipante()) {
            return view('home', ['dashboard' => $dashboard->forParticipant($user)]);
        }

        return view('home', ['dashboard' => $dashboard->overview()]);
    }
}
