<?php

namespace Spatie\WebTinker;

use Psy\Exception\BreakException;
use Psy\Exception\ThrowUpException;
use Psy\ExecutionClosure as PsyExecutionClosure;
use Psy\Shell;

class ExecutionLoopClosure extends PsyExecutionClosure
{
    public function __construct(Shell $__psysh__)
    {
        if (!defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'r') ?: fopen('/dev/null', 'r'));
        }
        if (!defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'w') ?: fopen('/dev/null', 'w'));
        }
        if (!defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'w') ?: fopen('/dev/null', 'w'));
        }

        $this->setClosure($__psysh__, function () use ($__psysh__) {
            try {
                // Restore execution scope variables
                \extract($__psysh__->getScopeVariables(false));

                // Vital: Fetch the input from the shell buffer (populated by addInput)
                // Without this, flushCode() returns empty and we evaluate NOOP.
                $__psysh__->getInput();

                try {
                    // Buffer stdout; we'll need it later
                    \ob_start([$__psysh__, 'writeStdout'], 1);

                    // Convert all errors to exceptions
                    \set_error_handler([$__psysh__, 'handleError']);

                    // Evaluate the current code buffer
                    $_ = eval($__psysh__->onExecute($__psysh__->flushCode() ?: PsyExecutionClosure::NOOP_INPUT));

                } catch (\Throwable $_e) {
                    // Clean up on our way out.
                    if (\ob_get_level() > 0) {
                        \ob_end_clean();
                    }
                    throw $_e;
                } finally {
                    \restore_error_handler();
                }

                // Flush stdout (write to shell output, plus save to magic variable)
                \ob_end_flush();

                // Save execution scope variables for next time
                $__psysh__->setScopeVariables(\get_defined_vars());

                // Write return value
                $__psysh__->writeReturnValue($_);

            } catch (BreakException $_e) {
                $__psysh__->writeException($_e);
            } catch (ThrowUpException $_e) {
                $__psysh__->writeException($_e);
                throw $_e;
            } catch (\Throwable $_e) {
                $__psysh__->writeException($_e);
            }
        });
    }
}
