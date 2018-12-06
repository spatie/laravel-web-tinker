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
        return Tinker::execute($request->code);
    }
}