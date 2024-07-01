<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Inertia\Inertia;

class HomeController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return Inertia::render('Home');
    }
}
