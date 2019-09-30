<?php

namespace Spatie\WebTinker\Shell;

use Psy\Shell as PsyShell;

class Shell extends PsyShell
{
    protected function readline()
    {
        if (!empty($this->inputBuffer)) {
            $line = \array_shift($this->inputBuffer);
            if (!$line instanceof SilentInput) {
                $this->output->writeln(\sprintf('<aside>%s %s</aside>', static::REPLAY, OutputFormatter::escape($line)));
            }

            return $line;
        }

        return false;
    }
}
