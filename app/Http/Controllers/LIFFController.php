<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LIFFController extends Controller
{
    public function index(Request $request)
    {
        return view("LIFF/index");
    }
}
