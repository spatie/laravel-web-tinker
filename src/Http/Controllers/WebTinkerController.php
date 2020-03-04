<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index()
    {
        return view('web-tinker::web-tinker', [
            'path' => app(UrlGenerator::class)->to(config('web-tinker.path')),
        ]);
    }

    public function execute(Request $request, Tinker $tinker)
    {
        $validated = $request->validate([
            'code' => 'required',
        ]);

        return $tinker->execute($validated['code']);
    }
}
