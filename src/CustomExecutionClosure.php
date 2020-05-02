<?php

namespace Spatie\WebTinker;

use Psy\Exception\BreakException;
use Psy\Exception\ErrorException;
use Psy\Exception\ThrowUpException;
use Psy\Exception\TypeErrorException;
use Psy\ExecutionClosure;
use Psy\Shell;

/**
 * The custom execustion closure
 *
 * This class is base on the Psy\ExecutionLoopClosure
 * Extracted the do while loop and removed the loop
 * To avoid infinite looping on execution
 */
class CustomExecutionClosure extends ExecutionClosure
{
    /**
     * @param Shell $__psysh__
     */
    public function __construct(Shell $__psysh__)
    {
        $this->setClosure($__psysh__, function () use ($__psysh__) {
            // Restore execution scope variables
            \extract($__psysh__->getScopeVariables(false));

            try {
                $__psysh__->getInput();

                try {
                    // Pull in any new execution scope variables
                    if ($__psysh__->getLastExecSuccess()) {
                        \extract($__psysh__->getScopeVariablesDiff(\get_defined_vars()));
                    }

                    // Buffer stdout; we'll need it later
                    \ob_start([$__psysh__, 'writeStdout'], 1);

                    // Convert all errors to exceptions
                    \set_error_handler([$__psysh__, 'handleError']);

                    // Evaluate the current code buffer
                    $_ = eval($__psysh__->onExecute($__psysh__->flushCode() ?: ExecutionClosure::NOOP_INPUT));
                } catch (\Throwable $_e) {
                    // Clean up on our way out.
                    if (\ob_get_level() > 0) {
                        \ob_end_clean();
                    }

                    throw $_e;
                } catch (\Exception $_e) {
                    // Clean up on our way out.
                    if (\ob_get_level() > 0) {
                        \ob_end_clean();
                    }

                    throw $_e;
                } finally {
                    // Won't be needing this anymore
                    \restore_error_handler();
                }

                // Flush stdout (write to shell output, plus save to magic variable)
                \ob_end_flush();

                // Save execution scope variables for next time
                $__psysh__->setScopeVariables(\get_defined_vars());

                $__psysh__->writeReturnValue($_);
            } catch (BreakException $_e) {
                $__psysh__->writeException($_e);

                return;
            } catch (ThrowUpException $_e) {
                $__psysh__->writeException($_e);

                throw $_e;
            } catch (\TypeError $_e) {
                $__psysh__->writeException(TypeErrorException::fromTypeError($_e));
            } catch (\Error $_e) {
                $__psysh__->writeException(ErrorException::fromError($_e));
            } catch (\Exception $_e) {
                $__psysh__->writeException($_e);
            }
        });
    }
}