<?php

namespace Spatie\WebTinker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Laravel\Tinker\ClassAliasAutoloader;
use Psy\Configuration;
use Psy\Shell;
use Spatie\WebTinker\Output\DumpRenderer;
use Spatie\WebTinker\Output\OutputFormat;
use Spatie\WebTinker\OutputModifiers\OutputModifier;
use Symfony\Component\Console\Output\BufferedOutput;

class Tinker
{
    /** @var \Symfony\Component\Console\Output\BufferedOutput */
    protected $output;

    /** @var \Psy\Shell */
    protected $shell;

    /** @var \Spatie\WebTinker\OutputModifiers\OutputModifier */
    protected $outputModifier;

    /** @var \Spatie\WebTinker\Output\DumpRenderer */
    protected $dumpRenderer;

    public function __construct(OutputModifier $outputModifier, ?DumpRenderer $dumpRenderer = null)
    {
        $this->output = new BufferedOutput;

        $this->shell = $this->createShell($this->output);

        $this->outputModifier = $outputModifier;

        $this->dumpRenderer = $dumpRenderer ?? new DumpRenderer;
    }

    /**
     * Run a snippet and return its rendered output.
     *
     * The format stays an argument rather than a setting because the two
     * callers want different things from the same shell: the browser wants a
     * VarDumper tree, while anything driving this endpoint over HTTP wants the
     * plain text it has always received.
     */
    public function execute(string $phpCode, OutputFormat $format = OutputFormat::Text): string
    {
        $phpCode = $this->removeComments($phpCode);

        $output = $format === OutputFormat::Html
            ? $this->executeToHtml($phpCode)
            : $this->executeToText($phpCode);

        return $this->outputModifier->modify($output);
    }

    protected function executeToText(string $phpCode): string
    {
        try {
            $returnValue = $this->shell->execute($phpCode, true);
            $this->shell->writeReturnValue($returnValue);
        } catch (\Throwable $throwable) {
            $this->shell->writeException($throwable);
        }

        return $this->cleanOutput($this->output->fetch());
    }

    protected function executeToHtml(string $phpCode): string
    {
        try {
            $returnValue = $this->shell->execute($phpCode, true);
        } catch (\Throwable $throwable) {
            return $this->dumpRenderer->renderThrowable($this->flushStdout(), $throwable);
        }

        return $this->dumpRenderer->render($this->flushStdout(), $returnValue);
    }

    /** Whatever the snippet echoed, drained from the shell's buffer. */
    protected function flushStdout(): string
    {
        return $this->cleanOutput($this->output->fetch());
    }

    protected function createShell(BufferedOutput $output): Shell
    {
        $config = new Configuration([
            'updateCheck' => 'never',
            'configFile' => config('web-tinker.config_file') !== null ? base_path().'/'.config('web-tinker.config_file') : null,
        ]);

        $config->setInteractiveMode(Configuration::INTERACTIVE_MODE_DISABLED);

        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');

        $config->getPresenter()->addCasters([
            Collection::class => 'Laravel\Tinker\TinkerCaster::castCollection',
            Model::class => 'Laravel\Tinker\TinkerCaster::castModel',
            Application::class => 'Laravel\Tinker\TinkerCaster::castApplication',
        ]);

        $shell = new Shell($config);

        $shell->setOutput($output);

        $composerClassMap = base_path('vendor/composer/autoload_classmap.php');

        if (file_exists($composerClassMap)) {
            ClassAliasAutoloader::register($shell, $composerClassMap, config('tinker.alias', []), config('tinker.dont_alias', []));
        }

        return $shell;
    }

    public function removeComments(string $code): string
    {
        $tokens = collect(token_get_all("<?php\n".$code.'?>'));

        return $tokens->reduce(function ($carry, $token) {
            if (is_string($token)) {
                return $carry.$token;
            }

            $text = $this->ignoreCommentsAndPhpTags($token);

            return $carry.$text;
        }, '');
    }

    protected function ignoreCommentsAndPhpTags(array $token)
    {
        [$id, $text] = $token;

        if ($id === T_COMMENT) {
            return '';
        }
        if ($id === T_DOC_COMMENT) {
            return '';
        }
        if ($id === T_OPEN_TAG) {
            return '';
        }
        if ($id === T_CLOSE_TAG) {
            return '';
        }

        return $text;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        $output = preg_replace('/(?s)(<whisper.*?<\/whisper>)|INFO  Ctrl\+D\./ms', '$2', $output);

        return trim($output);
    }
}
