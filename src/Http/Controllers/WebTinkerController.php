<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index()
    {
        return view('web-tinker::web-tinker');
    }

    public function execute(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required',
        ]);

        return Tinker::execute($validated['code']);
    }
}
