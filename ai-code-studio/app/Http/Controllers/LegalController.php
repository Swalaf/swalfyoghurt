<?php

namespace App\Http\Controllers;

use App\Support\Legal;

class LegalController extends Controller
{
    public function terms()
    {
        return view('legal', ['title' => __('Terms of Service'), 'html' => Legal::render('terms')]);
    }

    public function privacy()
    {
        return view('legal', ['title' => __('Privacy Policy'), 'html' => Legal::render('privacy')]);
    }
}
