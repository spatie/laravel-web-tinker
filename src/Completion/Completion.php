<?php

namespace Spatie\WebTinker\Completion;

/**
 * A single suggestion, plus the metadata the editor renders beside it.
 */
class Completion
{
    public function __construct(
        /** Text inserted into the editor, replacing everything from `from`. */
        public readonly string $value,
        /** Short kind label shown next to the suggestion: class, env, keyword, … */
        public readonly string $meta,
        /** What the list displays; defaults to the inserted value. */
        public readonly ?string $label = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label ?? $this->value,
            'meta' => $this->meta,
        ];
    }
}
