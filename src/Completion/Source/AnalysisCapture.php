<?php

namespace Spatie\WebTinker\Completion\Source;

use Psy\Completion\AnalysisResult;
use Psy\Completion\Source\SourceInterface;

/**
 * Keeps hold of the analysis the engine performed for the current request.
 *
 * The engine answers with names alone; a browser also needs to know where the
 * replacement starts and what kind of thing each name is, and both of those
 * live in the analysis it does not return.
 *
 * Registering a source that records it and contributes nothing is cheaper and
 * steadier than parsing the buffer a second time, which would be a second
 * opinion on where the current token begins — the sort of duplication that
 * quietly drifts out of agreement.
 *
 * Applies to every kind so that it runs whatever the caret is on, and is
 * registered last so the types it records are the ones the member sources
 * actually saw.
 */
class AnalysisCapture implements SourceInterface
{
    protected ?AnalysisResult $analysis = null;

    public function appliesToKind(int $kinds): bool
    {
        return true;
    }

    /** @return string[] always empty — this source only watches */
    public function getCompletions(AnalysisResult $analysis): array
    {
        $this->analysis = $analysis;

        return [];
    }

    public function last(): ?AnalysisResult
    {
        return $this->analysis;
    }
}
