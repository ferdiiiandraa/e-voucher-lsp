<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function beranda(): View
    {
        return view('beranda');
    }

    public function kelola(): View
    {
        return view('kelola');
    }
}
