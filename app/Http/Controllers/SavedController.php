<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SavedController extends Controller
{
    public function index(): View
    {
        return view('saved');
    }
}
