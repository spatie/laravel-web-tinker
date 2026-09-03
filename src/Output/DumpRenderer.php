<?php

namespace Spatie\WebTinker\Output;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Throwable;

/**
 * Renders an execution's result as VarDumper markup.
 *
 * PsySH prints a flat, truncated string; VarDumper prints a tree that can be
 * expanded, shows real types, and stays readable for a nested array or a model
 * with relations loaded.
 *
 * The dumper's `<style>`/`<script>` header is emitted once per page by
 * {@see pageAssets()} rather than repeated with every dump — see
 * `resources/views/web-tinker.blade.php`.
 */
class DumpRenderer
{
    protected VarCloner $cloner;

    protected HtmlDumper $dumper;

    public function __construct(
        protected int $maxDepth = 6,
        protected int $maxItems = 250,
        protected int $maxStringLength = 2500
    ) {
        $this->cloner = new VarCloner();

        $this->cloner->setMaxItems($this->maxItems);
        $this->cloner->setMaxString($this->maxStringLength);

        // The same casters PsySH is configured with, so a model or collection
        // dumps as its meaningful state instead of its internal plumbing.
        $this->cloner->addCasters([
            Collection::class => 'Laravel\Tinker\TinkerCaster::castCollection',
            Model::class => 'Laravel\Tinker\TinkerCaster::castModel',
            Application::class => 'Laravel\Tinker\TinkerCaster::castApplication',
        ]);

        $this->dumper = new HtmlDumper();
        $this->dumper->setDumpHeader('');
    }

    /** Output written by the snippet, followed by the value it returned. */
    public function render(string $stdout, mixed $returnValue): string
    {
        return $this->stdoutBlock($stdout).$this->dump($returnValue);
    }

    /** Output written before the snippet threw, followed by the throwable. */
    public function renderThrowable(string $stdout, Throwable $throwable): string
    {
        $heading = '<div class="wt-exception-heading">'
            .e(get_class($throwable)).': '.e($throwable->getMessage())
            .'</div>';

        return $this->stdoutBlock($stdout).$heading.$this->dump($throwable);
    }

    /**
     * The dumper's shared assets. Rendered into the page once; every dump after
     * that is markup only, which keeps responses small and lets the output
     * panel sanitize what it inserts.
     */
    public function pageAssets(): string
    {
        $dumper = new HtmlDumper();

        $getDumpHeader = new ReflectionMethod($dumper, 'getDumpHeader');
        $getDumpHeader->setAccessible(true);

        return (string) $getDumpHeader->invoke($dumper);
    }

    protected function dump(mixed $value): string
    {
        $data = $this->cloner->cloneVar($value)->withMaxDepth($this->maxDepth);

        return (string) $this->dumper->dump($data, true);
    }

    protected function stdoutBlock(string $stdout): string
    {
        if (trim($stdout) === '') {
            return '';
        }

        return '<pre class="wt-stdout">'.e($stdout).'</pre>';
    }
}
