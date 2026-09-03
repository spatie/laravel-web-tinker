<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index()
    {
        $path = app(UrlGenerator::class)->to(config('web-tinker.path'));

        return view('web-tinker::web-tinker', [
            'path' => $path,
            'completionPath' => rtrim($path, '/').'/completions',
            'completionEnabled' => (bool) config('web-tinker.completion.enabled', true),
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
