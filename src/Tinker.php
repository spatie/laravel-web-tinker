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
        $phpCode = $this->removeComments($phpCode);

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

    public function removeComments(string $code): string
    {
        $tokens = token_get_all("<?php\n".$code.'?>');
        $cleanCode = '';
        foreach ($tokens as $token) {
            if (is_string($token)) {
                // simple 1-character token
                $cleanCode .= $token;
            } else {
                // token array
                list($id, $text) = $token;

                switch ($id) {
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                    case T_OPEN_TAG:
                    case T_CLOSE_TAG:
                        // no action on comments and php tags
                        break;
                    default:
                        // anything else -> output "as is"
                        $cleanCode .= $text;
                        break;
                }
            }
        }
        return $cleanCode;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        return trim($output);
    }
}
