<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Spatie\WebTinker\Output\DumpRenderer;
use Spatie\WebTinker\Output\OutputFormat;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index(DumpRenderer $dumpRenderer)
    {
        $richOutput = $this->richOutputEnabled();

        return view('web-tinker::web-tinker', [
            'path' => app(UrlGenerator::class)->to(config('web-tinker.path')),
            // The page asks for the format it is allowed to have, so the
            // toggle needs to be read in one place only.
            'outputFormat' => $richOutput ? OutputFormat::Html->value : OutputFormat::Text->value,
            // VarDumper's stylesheet and toggle script, emitted once for the
            // page so individual dumps carry markup only.
            'dumpAssets' => $richOutput ? $dumpRenderer->pageAssets() : '',
        ]);
    }

    public function execute(Request $request, Tinker $tinker)
    {
        $validated = $request->validate([
            'code' => 'required',
            'format' => 'nullable|string|in:text,html',
        ]);

        return $tinker->execute($validated['code'], $this->format($request));
    }

    /**
     * Text unless the caller asks for otherwise: the browser opts into HTML
     * explicitly, so scripts and tooling posting to this endpoint keep getting
     * the plain output they were written against.
     */
    protected function format(Request $request): OutputFormat
    {
        if (! $this->richOutputEnabled()) {
            return OutputFormat::Text;
        }

        $default = OutputFormat::fromRequestValue(config('web-tinker.output_format'));

        return OutputFormat::fromRequestValue($request->input('format'), $default);
    }

    protected function richOutputEnabled(): bool
    {
        return (bool) config('web-tinker.dump.enabled', true);
    }
}
