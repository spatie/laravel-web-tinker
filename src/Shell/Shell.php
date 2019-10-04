<?php

namespace Spatie\WebTinker\Shell;

use Psy\Configuration;
use Psy\Input\SilentInput;
use Psy\Shell as PsyShell;

class Shell extends PsyShell
{
    /**
     * @var array  overwrite parent::$inputBuffer which is private
     */
    protected $inputBuffer;

    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);

        $this->inputBuffer   = [];
    }

    public function addInput($input, $silent = false)
    {
        foreach ((array) $input as $line) {
            $this->inputBuffer[] = $silent ? new SilentInput($line) : $line;
        }
    }
    protected function readline()
    {

        if (!empty($this->inputBuffer)) {
            return \array_shift($this->inputBuffer);
        }

        return false;
    }

}
