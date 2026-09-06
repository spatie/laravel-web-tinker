<?php

namespace Spatie\WebTinker\Completion;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The list of class names the editor can complete.
 *
 * PsySH's own ClassNamesMatcher only sees `get_declared_classes()`, which in a
 * web request is whatever the framework happened to autoload — a few hundred
 * classes, almost none of them yours. This builds the real list instead:
 * Composer's classmap for vendor code, a scan of the application's own PSR-4
 * roots for classes that are autoloaded lazily, and the registered facade
 * aliases on top.
 *
 * The scan is the expensive part, so the result is cached; the cache key
 * carries the classmap's mtime, which changes on every `composer dump-autoload`.
 */
class ClassIndex
{
    /** Upper bound on files visited per PSR-4 root, so a stray huge tree can't stall a keystroke. */
    protected const MAX_FILES_PER_ROOT = 15000;

    protected static ?array $memoized = null;

    public function __construct(
        protected string $basePath,
        protected int $cacheTtl
    ) {
    }

    /** @return string[] Fully qualified class names, without a leading backslash. */
    public function all(): array
    {
        if (static::$memoized !== null) {
            return static::$memoized;
        }

        if ($this->cacheTtl <= 0) {
            return static::$memoized = $this->build();
        }

        return static::$memoized = Cache::remember(
            $this->cacheKey(),
            $this->cacheTtl,
            fn () => $this->build()
        );
    }

    /** Drop both cache layers. Exposed for tests and for `web-tinker:clear-completions`. */
    public function flush(): void
    {
        static::$memoized = null;

        Cache::forget($this->cacheKey());
    }

    protected function cacheKey(): string
    {
        $classMap = $this->basePath.'/vendor/composer/autoload_classmap.php';

        $fingerprint = file_exists($classMap) ? (string) filemtime($classMap) : 'none';

        return 'web-tinker.class-index.'.md5($this->basePath.':'.$fingerprint);
    }

    protected function build(): array
    {
        $classes = array_merge(
            $this->fromClassMap(),
            $this->fromApplicationSource(),
            $this->fromAliases(),
            get_declared_classes(),
            get_declared_interfaces()
        );

        $classes = array_map(fn (string $class) => ltrim($class, '\\'), $classes);

        $classes = array_values(array_unique($classes));

        sort($classes);

        return $classes;
    }

    protected function fromClassMap(): array
    {
        $path = $this->basePath.'/vendor/composer/autoload_classmap.php';

        if (! file_exists($path)) {
            return [];
        }

        $map = require $path;

        return is_array($map) ? array_keys($map) : [];
    }

    protected function fromAliases(): array
    {
        if (! class_exists(AliasLoader::class)) {
            return [];
        }

        return array_keys(AliasLoader::getInstance()->getAliases());
    }

    /**
     * Classes under the application's own PSR-4 roots.
     *
     * Only roots outside `vendor/` are scanned: vendor code is already covered
     * by the classmap, and walking it would cost far more than it returns.
     */
    protected function fromApplicationSource(): array
    {
        $psr4 = $this->basePath.'/vendor/composer/autoload_psr4.php';

        if (! file_exists($psr4)) {
            return [];
        }

        $map = require $psr4;

        if (! is_array($map)) {
            return [];
        }

        $classes = [];

        foreach ($map as $namespace => $directories) {
            foreach ((array) $directories as $directory) {
                if ($this->isVendorPath($directory) || ! is_dir($directory)) {
                    continue;
                }

                $classes = array_merge($classes, $this->scanDirectory($directory));
            }
        }

        return $classes;
    }

    protected function isVendorPath(string $path): bool
    {
        return str_contains(str_replace('\\', '/', $path), '/vendor/');
    }

    /**
     * Read the namespace and type name declared in each PHP file under a root.
     *
     * Deriving the class name from the file path would be cheaper, but wrong
     * for any codebase holding global-namespace classes in a namespaced tree —
     * so the declaration itself is what gets read, from the head of the file.
     *
     * @return string[]
     */
    protected function scanDirectory(string $directory): array
    {
        $classes = [];
        $visited = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (++$visited > static::MAX_FILES_PER_ROOT) {
                break;
            }

            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if ($class = $this->classDeclaredIn($file->getPathname())) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    protected function classDeclaredIn(string $path): ?string
    {
        $head = @file_get_contents($path, false, null, 0, 4096);

        if ($head === false) {
            return null;
        }

        if (! preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/mi', $head, $type)) {
            return null;
        }

        $namespace = preg_match('/^\s*namespace\s+([^;{\s]+)/mi', $head, $ns) ? $ns[1].'\\' : '';

        return $namespace.$type[1];
    }
}
