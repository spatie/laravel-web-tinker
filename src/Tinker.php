<?php

namespace Spatie\WebTinker;

use Psy\Shell;
use Psy\Configuration;
use Psy\ExecutionLoopClosure;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Laravel\Tinker\ClassAliasAutoloader;
use Symfony\Component\Console\Output\BufferedOutput;

class Tinker
{
    /** @var \Symfony\Component\Console\Output\BufferedOutput */
    protected $output;

    /** @var \Psy\Shell */
    protected $shell;

    public static function execute(string $phpCode): string
    {
        return(new static())->run($phpCode);
    }

    public function __construct()
    {
        $this->output = new BufferedOutput();

        $this->shell = $this->createShell($this->output);
    }

    public function run(string $phpCode): string
    {
        $this->shell->addInput($phpCode);

        $closure = new ExecutionLoopClosure($this->shell);

        $closure->execute();

        return $this->cleanOutput($this->output->fetch());
    }

    protected function createShell(BufferedOutput $output): Shell
    {
        $config = new Configuration([
            'updateCheck' => 'never',
        ]);

        $config->getPresenter()->addCasters([
            Collection::class => 'Laravel\Tinker\TinkerCaster::castCollection',
            Model::class => 'Laravel\Tinker\TinkerCaster::castModel',
            Application::class => 'Laravel\Tinker\TinkerCaster::castApplication',
        ]);

        $shell = new Shell($config);

        $shell->setOutput($output);

        $composerClassMap = base_path('vendor/composer/autoload_classmap.php');

        if (file_exists($composerClassMap)) {
            ClassAliasAutoloader::register($shell, $composerClassMap);
        }

        return $shell;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        return trim($output);
    }
}
