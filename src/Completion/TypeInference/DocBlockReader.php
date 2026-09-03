<?php

namespace Spatie\WebTinker\Completion\TypeInference;

use ReflectionClass;
use ReflectionMethod;

/**
 * Reads types out of docblocks, and resolves the short class names they use.
 *
 * This is what makes Eloquent models completable. A model's columns and
 * relations exist only at runtime — `$company->individuals` resolves through
 * `__get`, and no amount of reflection will find it. What does describe them
 * is the `@property` block above the class:
 *
 *     @property Individual[]|Collection $individuals
 *     @property string                  $site_description
 *
 * A short name in a docblock means whatever the `use` statements at the top of
 * that file say it means, so those are parsed too — otherwise `Collection`
 * above would be a coin flip between a dozen classes called Collection.
 */
class DocBlockReader
{
    /**
     * A docblock type: a name, optionally followed by generic arguments.
     *
     * The generic part is matched separately because it may contain spaces —
     * `Collection<int, Widget>` is one type, not two.
     */
    protected const TYPE = '[^\s$*<]+(?:<[^>]*>)?';

    /** Types that name no class worth reflecting on. */
    protected const BUILT_IN = [
        'array', 'bool', 'callable', 'false', 'float', 'int', 'iterable',
        'mixed', 'null', 'object', 'resource', 'string', 'true', 'void',
    ];

    /** @var array<string, array<string, array{type: string, declaredIn: string}>> */
    protected array $properties = [];

    /** @var array<string, string[]> */
    protected array $methods = [];

    /** @var array<string, array<string, string>> file => lowercased alias => FQCN */
    protected array $imports = [];

    /**
     * Documented properties of a class and everything it inherits from.
     *
     * @return array<string, array{type: string, declaredIn: string}>
     */
    public function properties(ReflectionClass $class): array
    {
        if (isset($this->properties[$class->getName()])) {
            return $this->properties[$class->getName()];
        }

        $properties = [];

        // Walk from the root down, so a subclass's annotation overrides the
        // one it inherits.
        foreach (array_reverse($this->lineage($class)) as $ancestor) {
            $docComment = $ancestor->getDocComment();

            if ($docComment === false) {
                continue;
            }

            preg_match_all(
                '/@property(?:-read|-write)?\s+('.self::TYPE.')\s+\$(\w+)/',
                $docComment,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $properties[$match[2]] = ['type' => $match[1], 'declaredIn' => $ancestor->getName()];
            }
        }

        return $this->properties[$class->getName()] = $properties;
    }

    /**
     * Documented `@method` names — how models advertise their local scopes and
     * forwarded builder calls.
     *
     * @return string[]
     */
    public function methods(ReflectionClass $class): array
    {
        if (isset($this->methods[$class->getName()])) {
            return $this->methods[$class->getName()];
        }

        $methods = [];

        foreach ($this->lineage($class) as $ancestor) {
            $docComment = $ancestor->getDocComment();

            if ($docComment === false) {
                continue;
            }

            preg_match_all('/@method\s+(?:static\s+)?(?:[^\s]+\s+)?(\w+)\s*\(/', $docComment, $matches);

            $methods = array_merge($methods, $matches[1]);
        }

        return $this->methods[$class->getName()] = array_values(array_unique($methods));
    }

    /**
     * The documented type of a property, and its element type when it is a
     * collection.
     *
     * @return array{0: string|null, 1: string|null} [class, element]
     */
    public function propertyType(ReflectionClass $class, string $name): array
    {
        $property = $this->properties($class)[$name] ?? null;

        if ($property === null) {
            return [null, null];
        }

        return $this->typeAndElement($property['type'], new ReflectionClass($property['declaredIn']));
    }

    /**
     * The documented `@return` type of a method, and its element type.
     *
     * @return array{0: string|null, 1: string|null} [class, element]
     */
    public function returnType(ReflectionMethod $method): array
    {
        $docComment = $method->getDocComment();

        if ($docComment === false || ! preg_match('/@return\s+('.self::TYPE.')/', $docComment, $matches)) {
            return [null, null];
        }

        return $this->typeAndElement($matches[1], $method->getDeclaringClass());
    }

    /**
     * Resolve a docblock type against the file it was written in.
     *
     * `static`, `self` and `$this` fold back to the context class. A union
     * prefers its plain members over its array ones, so
     * `Individual[]|Collection` resolves to the collection — which is what the
     * expression actually holds.
     */
    public function resolve(string $rawType, ReflectionClass $context): ?string
    {
        foreach ($this->candidates($rawType) as $candidate) {
            if (in_array($candidate, ['static', 'self', '$this'], true)) {
                return $context->getName();
            }

            if ($resolved = $this->qualify($candidate, $context)) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * The class-ish names in a raw type, plain ones first.
     *
     * @return string[]
     */
    public function candidates(string $rawType): array
    {
        $partitioned = $this->partition($rawType);

        return array_values(array_filter(array_merge($partitioned['plain'], $partitioned['elements'])));
    }

    /**
     * Split a raw type into the value's own class and, when it is a
     * collection, what it holds.
     *
     * `Individual[]|Collection` describes one value two ways: the class is the
     * collection, the element is the individual. Knowing both is what lets
     * `$company->individuals->first()` resolve, since Laravel types
     * `Collection::first()` as a generic that reflection cannot follow.
     *
     * @return array{0: string|null, 1: string|null} [class, element]
     */
    public function typeAndElement(string $rawType, ReflectionClass $context): array
    {
        $partitioned = $this->partition($rawType);

        $resolve = function (array $names) use ($context): ?string {
            foreach ($names as $name) {
                if (in_array($name, ['static', 'self', '$this'], true)) {
                    return $context->getName();
                }

                if ($resolved = $this->qualify($name, $context)) {
                    return $resolved;
                }
            }

            return null;
        };

        $element = $resolve($partitioned['elements']);
        $class = $resolve($partitioned['plain']) ?? $element;

        return [$class, $element];
    }

    /**
     * @return array{plain: string[], elements: string[]}
     */
    protected function partition(string $rawType): array
    {
        $plain = [];
        $elements = [];

        foreach (explode('|', $rawType) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '' || in_array(strtolower($candidate), self::BUILT_IN, true)) {
                continue;
            }

            if (str_ends_with($candidate, '[]')) {
                $elements[] = substr($candidate, 0, -2);

                continue;
            }

            // `Collection<int, Individual>`: the class is the collection, the
            // last generic argument is what it holds.
            if (preg_match('/^([\w\\\\]+)<(.+)>$/', $candidate, $generic)) {
                $arguments = array_map('trim', explode(',', $generic[2]));

                $plain[] = $generic[1];
                $elements[] = (string) end($arguments);

                continue;
            }

            $plain[] = $candidate;
        }

        return ['plain' => $plain, 'elements' => $elements];
    }

    protected function qualify(string $candidate, ReflectionClass $context): ?string
    {
        if (str_starts_with($candidate, '\\')) {
            $candidate = ltrim($candidate, '\\');

            return $this->exists($candidate) ? $candidate : null;
        }

        $imports = $this->importsOf($context);
        $alias = strtolower(strtok($candidate, '\\') ?: $candidate);

        if (isset($imports[$alias])) {
            $remainder = substr($candidate, strlen($alias));
            $imported = $imports[$alias].$remainder;

            if ($this->exists($imported)) {
                return $imported;
            }
        }

        $namespace = $context->getNamespaceName();

        if ($namespace !== '' && $this->exists($namespace.'\\'.$candidate)) {
            return $namespace.'\\'.$candidate;
        }

        return $this->exists($candidate) ? $candidate : null;
    }

    protected function exists(string $class): bool
    {
        return class_exists($class) || interface_exists($class) || enum_exists($class);
    }

    /**
     * `use` statements at the top of the file a class was declared in.
     *
     * Only the part above the type declaration is read, so `use SomeTrait;`
     * inside the class body is not mistaken for an import.
     *
     * @return array<string, string> lowercased alias => fully qualified name
     */
    protected function importsOf(ReflectionClass $class): array
    {
        $file = $class->getFileName();

        if ($file === false) {
            return [];
        }

        if (isset($this->imports[$file])) {
            return $this->imports[$file];
        }

        $contents = @file_get_contents($file);

        if ($contents === false) {
            return $this->imports[$file] = [];
        }

        $head = preg_split('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s/mi', $contents)[0] ?? '';

        preg_match_all('/^\s*use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/mi', $head, $matches, PREG_SET_ORDER);

        $imports = [];

        foreach ($matches as $match) {
            $alias = $match[2] ?? substr((string) strrchr('\\'.$match[1], '\\'), 1);

            $imports[strtolower($alias)] = $match[1];
        }

        return $this->imports[$file] = $imports;
    }

    /** @return ReflectionClass[] the class, then its ancestors */
    protected function lineage(ReflectionClass $class): array
    {
        $lineage = [$class];

        while ($class = $class->getParentClass()) {
            $lineage[] = $class;
        }

        return $lineage;
    }
}
