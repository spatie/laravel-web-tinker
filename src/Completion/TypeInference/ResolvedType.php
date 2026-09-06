<?php

namespace Spatie\WebTinker\Completion\TypeInference;

/**
 * What an expression evaluates to.
 *
 * `element` carries what a collection holds, which a class name alone cannot
 * express. An annotation like `Individual[]|Collection` describes one value
 * twice, and both halves are needed: the collection answers `->map(`, the
 * element answers `->first()->`.
 */
class ResolvedType
{
    /** @param string[] $classes */
    public function __construct(
        public readonly array $classes = [],
        public readonly ?string $element = null
    ) {
    }

    public static function none(): self
    {
        return new self();
    }

    public static function of(?string $class, ?string $element = null): self
    {
        return new self($class === null ? [] : [$class], $element);
    }

    public function isEmpty(): bool
    {
        return $this->classes === [];
    }

    public function first(): ?string
    {
        return $this->classes[0] ?? null;
    }
}
