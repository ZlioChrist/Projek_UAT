<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ExploreController extends Controller
{
    public function index(): View
    {
        return view('explore');
    }
}
