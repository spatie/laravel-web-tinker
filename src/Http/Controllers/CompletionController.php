<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Completion\CompletionResult;

class CompletionController
{
    public function __invoke(Request $request, Completer $completer): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'present|string',
            'cursor' => 'required|integer|min:0',
        ]);

        if (! config('web-tinker.completion.enabled', true)) {
            return response()->json(CompletionResult::empty()->toArray());
        }

        $result = $completer->complete($validated['code'], (int) $validated['cursor']);

        return response()->json($result->toArray());
    }
}
