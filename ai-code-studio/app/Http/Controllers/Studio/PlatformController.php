<?php

namespace App\Http\Controllers\Studio;

use App\Models\AiProvider;
use App\Support\Settings;

/** Developer-mode versions of AI providers & white-label (admins only). */
class PlatformController extends StudioController
{
    public function providers()
    {
        return view('studio.providers', ['providers' => AiProvider::orderBy('priority')->get(), 'routing' => Settings::get('routing')]);
    }

    public function brand()
    {
        return view('studio.brand', ['settings' => Settings::all()]);
    }
}
