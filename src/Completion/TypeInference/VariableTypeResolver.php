<?php

namespace Spatie\WebTinker\Completion\TypeInference;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\WebTinker\Completion\ClassIndex;

/**
 * Works out what class an expression evaluates to, by reading the snippet —
 * never by running it.
 *
 * A web tinker request builds a fresh shell with an empty scope, so there is no
 * live `$user` to reflect on the way the CLI shell can. Executing the lines
 * above the caret to produce one is not an option: completion would then insert
 * rows every time someone typed a character after `User::create(...)`.
 *
 * So the code itself is the evidence. Recognised shapes:
 *
 *   $user = new User;                     // constructor
 *   $user = User::first();                // static factory
 *   $user = app(User::class);             // container
 *   $user = $repository->findUser();      // native and @return types
 *   /** @var User $user *\/                // explicit annotation, always wins
 *
 * and any chain of members hanging off one of those, including chains that
 * start from a static call directly: `Company::getById(1)->individuals->first()`.
 */
class VariableTypeResolver
{
    /**
     * Eloquent's static entry points are `__callStatic` forwards to the query
     * builder — not real methods, so there is nothing to reflect and no
     * docblock to read. These are the forwards that hand back an instance of
     * the model they were called on.
     *
     * Nothing else is guessed at: a static method with neither a return type
     * nor an `@return` annotation resolves to null, and completion stays quiet.
     */
    protected const ELOQUENT_STATIC_FORWARDS = [
        'create',
        'find',
        'findOrFail',
        'findOrNew',
        'first',
        'firstOrCreate',
        'firstOrFail',
        'firstOrNew',
        'forceCreate',
        'make',
        'sole',
        'updateOrCreate',
    ];

    /**
     * Collection methods that hand back one element.
     *
     * Laravel types these as generics (`@return TValue`), which reflection
     * cannot follow — but the annotation that produced the collection said
     * what it holds, so the element type is carried along the chain instead.
     */
    protected const COLLECTION_ELEMENT_METHODS = [
        'find', 'first', 'firstOrFail', 'firstWhere', 'get', 'last', 'pop',
        'shift', 'sole',
    ];

    /** Variables currently being resolved, so `$a = $a->b()` cannot recurse forever. */
    protected array $resolving = [];

    public function __construct(
        protected ClassIndex $classIndex,
        protected DocBlockReader $docBlocks
    ) {
    }

    /** The class held by `$name` at the end of `$code`, or null. */
    public function resolve(string $code, string $name): ?string
    {
        if (isset($this->resolving[$name])) {
            return null;
        }

        if ($annotated = $this->fromAnnotation($code, $name)) {
            return $annotated;
        }

        if (! preg_match_all('/\$'.preg_quote($name, '/').'\s*=\s*([^;\n]+)/', $code, $matches)) {
            return null;
        }

        $this->resolving[$name] = true;

        try {
            // Later assignments shadow earlier ones.
            return $this->resolveExpression(trim(end($matches[1])), $code);
        } finally {
            unset($this->resolving[$name]);
        }
    }

    /** The class an expression evaluates to, following its member chain. */
    public function resolveExpression(string $expression, string $code = ''): ?string
    {
        $pattern = '/^('.Expression::HEAD.')((?:'.Expression::LINK.')*)$/';

        if (! preg_match($pattern, trim($expression), $matches)) {
            return null;
        }

        $type = $this->resolveHead(trim($matches[1]), $code);

        return $matches[2] === '' ? $type : $this->followChain($type, $matches[2]);
    }

    /** Public members are only meaningful once the class actually exists. */
    public function reflect(?string $class): ?ReflectionClass
    {
        if ($class === null || ! class_exists($class)) {
            return null;
        }

        return new ReflectionClass($class);
    }

    /** Names a model documents with `@property`, for the members matcher. */
    public function documentedProperties(ReflectionClass $class): array
    {
        return array_keys($this->docBlocks->properties($class));
    }

    /** Names a model documents with `@method`, for the members matcher. */
    public function documentedMethods(ReflectionClass $class): array
    {
        return $this->docBlocks->methods($class);
    }

    protected function resolveHead(string $head, string $code): ?string
    {
        if (preg_match('/^\$(\w+)$/', $head, $matches)) {
            return $this->resolve($code, $matches[1]);
        }

        if (preg_match('/^new\s+\\\\?([A-Za-z_][\w\\\\]*)/', $head, $matches)) {
            return $this->qualify($matches[1]);
        }

        if (preg_match('/^(?:app|resolve)\s*\(\s*\\\\?([A-Za-z_][\w\\\\]*)::class/i', $head, $matches)) {
            return $this->qualify($matches[1]);
        }

        if (preg_match('/^\\\\?([A-Za-z_][\w\\\\]*)::(\w+)\s*\(/', $head, $matches)) {
            return $this->fromStaticCall($this->qualify($matches[1]), $matches[2]);
        }

        return null;
    }

    /**
     * Apply each `->member` step in turn, method calls and properties alike,
     * carrying the element type of a collection along so that taking one item
     * out of it lands on the right class.
     */
    protected function followChain(?string $type, string $chain): ?string
    {
        preg_match_all('/->\s*(\w+)\s*(\()?/', $chain, $links, PREG_SET_ORDER);

        $element = null;

        foreach ($links as $link) {
            if ($type === null) {
                return null;
            }

            $isCall = ($link[2] ?? '') === '(';

            if ($isCall && $element !== null && $this->takesOneElement($type, $link[1])) {
                $type = $element;
                $element = null;

                continue;
            }

            [$type, $element] = $isCall
                ? $this->methodType($type, $link[1])
                : $this->propertyType($type, $link[1]);
        }

        return $type;
    }

    protected function takesOneElement(string $class, string $method): bool
    {
        return in_array($method, self::COLLECTION_ELEMENT_METHODS, true)
            && is_a($class, \Traversable::class, true);
    }

    protected function fromAnnotation(string $code, string $name): ?string
    {
        $pattern = '/@var\s+\\\\?([A-Za-z_][\w\\\\]*)(?:\[\])?\s+\$'.preg_quote($name, '/').'\b/';

        if (! preg_match_all($pattern, $code, $matches)) {
            return null;
        }

        return $this->qualify(end($matches[1]));
    }

    protected function fromStaticCall(?string $class, string $method): ?string
    {
        if ($class === null || ! class_exists($class)) {
            return null;
        }

        if ($declared = $this->methodReturnType($class, $method)) {
            return $declared;
        }

        if (is_subclass_of($class, Model::class) && in_array($method, self::ELOQUENT_STATIC_FORWARDS, true)) {
            return $class;
        }

        return null;
    }

    /**
     * A property's type: declared natively, or documented with `@property`.
     *
     * @return array{0: string|null, 1: string|null} [class, element]
     */
    protected function propertyType(string $class, string $name): array
    {
        if (! class_exists($class)) {
            return [null, null];
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->hasProperty($name)) {
            $native = $this->namedType($class, (new ReflectionProperty($class, $name))->getType());

            if ($native !== null) {
                return [$native, null];
            }
        }

        return $this->docBlocks->propertyType($reflection, $name);
    }

    /** @return array{0: string|null, 1: string|null} [class, element] */
    protected function methodType(string $class, string $method): array
    {
        if (! method_exists($class, $method)) {
            return [null, null];
        }

        $reflection = new ReflectionMethod($class, $method);

        $native = $this->namedType($class, $reflection->getReturnType());

        return $native !== null ? [$native, null] : $this->docBlocks->returnType($reflection);
    }

    protected function methodReturnType(string $class, string $method): ?string
    {
        return $this->methodType($class, $method)[0];
    }

    /**
     * Reduce a native type to a class name.
     *
     * Unions are searched for their first class member, so
     * `DateTimeImmutable|false` resolves rather than being discarded.
     */
    protected function namedType(string $class, mixed $type): ?string
    {
        $candidates = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($candidates as $candidate) {
            if (! $candidate instanceof ReflectionNamedType || $candidate->isBuiltin()) {
                continue;
            }

            $name = $candidate->getName();

            if (in_array($name, ['static', 'self', '$this'], true)) {
                return $class;
            }

            if (class_exists($name) || interface_exists($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Turn whatever was written into a class that exists — a short name is
     * looked up in the class index, the way the class-name matcher does.
     */
    protected function qualify(string $class): ?string
    {
        $class = ltrim($class, '\\');

        if (class_exists($class) || interface_exists($class)) {
            return $class;
        }

        foreach ($this->classIndex->all() as $candidate) {
            if (strcasecmp($candidate, $class) === 0) {
                return $candidate;
            }

            if (strcasecmp((string) substr((string) strrchr($candidate, '\\'), 1), $class) === 0) {
                return $candidate;
            }
        }

        return null;
    }
}
