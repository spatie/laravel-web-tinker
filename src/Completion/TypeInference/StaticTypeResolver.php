<?php

namespace Spatie\WebTinker\Completion\TypeInference;

use Illuminate\Database\Eloquent\Model;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Parser;
use PhpParser\NodeFinder;
use Psy\ParserFactory;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\WebTinker\Completion\ClassIndex;
use Traversable;

/**
 * Works out what an expression evaluates to by reading the code, never by
 * running it.
 *
 * PsySH's own {@see \Psy\Completion\TypeResolver} answers this from the shell's
 * runtime context — it looks the variable up and reflects on the object it
 * finds. That is the better answer when there is one, but a web tinker request
 * builds a fresh shell with an empty scope, so there never is: nothing has been
 * evaluated yet, and evaluating the lines above the caret to find out would
 * mean `User::create(...)` inserting a row on every keystroke.
 *
 * So the assignment itself is the evidence:
 *
 *     $user = new User;                 // constructor
 *     $user = User::first();            // Eloquent's static forwards
 *     $user = app(User::class);         // the container
 *     $user = $repository->findUser();  // native and @return types
 *     /** @var User $user *\/            // an annotation, when all else fails
 *
 * Only the AST is consulted, so a nested call in the arguments or a chain of
 * any length costs nothing extra. Anything unrecognised resolves to no type at
 * all, and the caller offers nothing rather than something wrong.
 */
class StaticTypeResolver
{
    /**
     * Eloquent's static entry points are `__callStatic` forwards to the query
     * builder — not real methods, so there is nothing to reflect on and no
     * docblock to read. These are the forwards that hand back an instance of
     * the model they were called on.
     */
    protected const ELOQUENT_STATIC_FORWARDS = [
        'create', 'find', 'findOrFail', 'findOrNew', 'first', 'firstOrCreate',
        'firstOrFail', 'firstOrNew', 'forceCreate', 'make', 'sole',
        'updateOrCreate',
    ];

    /** Collection methods that hand back one element. */
    protected const ELEMENT_METHODS = [
        'find', 'first', 'firstOrFail', 'firstWhere', 'get', 'last', 'pop',
        'shift', 'sole',
    ];

    /** Container helpers whose argument names the class they return. */
    protected const CONTAINER_HELPERS = ['app', 'resolve'];

    protected Parser $parser;

    /** Variables being resolved, so `$a = $a->b()` cannot recurse forever. */
    protected array $resolving = [];

    public function __construct(
        protected ClassIndex $classIndex,
        protected DocBlockReader $docBlocks
    ) {
        $this->parser = (new ParserFactory())->createParser();
    }

    /**
     * The type of an expression node, resolved against the surrounding code.
     *
     * @param string $buffer everything the editor holds, needed to find the
     *                       assignment a variable came from
     */
    public function resolveNode(Node $node, string $buffer): ResolvedType
    {
        if ($node instanceof Expr\Variable && is_string($node->name)) {
            return $this->resolveVariable($node->name, $buffer);
        }

        if ($node instanceof Expr\New_) {
            return ResolvedType::of($this->qualifyName($node->class));
        }

        if ($node instanceof Expr\StaticCall) {
            return $this->resolveStaticCall($node);
        }

        if ($node instanceof Expr\FuncCall) {
            return $this->resolveContainerCall($node);
        }

        if ($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) {
            return $this->resolveMethodCall($node, $buffer);
        }

        if ($node instanceof Expr\PropertyFetch || $node instanceof Expr\NullsafePropertyFetch) {
            return $this->resolvePropertyFetch($node, $buffer);
        }

        if ($node instanceof Expr\Assign) {
            return $this->resolveNode($node->expr, $buffer);
        }

        if ($node instanceof Expr\Ternary) {
            return $this->resolveNode($node->if ?? $node->cond, $buffer);
        }

        return ResolvedType::none();
    }

    /**
     * The type of an expression given only as text.
     *
     * The engine hands us a node whenever the buffer parsed. It cannot when the
     * caret sits directly after an arrow (`$user->`), which is a syntax error
     * until something follows it — so that case arrives as text and is parsed
     * here on its own.
     */
    public function resolveText(string $expression, string $buffer): ResolvedType
    {
        $statements = $this->parse($expression.';');

        $expr = ($statements[0] ?? null) instanceof Node\Stmt\Expression
            ? $statements[0]->expr
            : null;

        return $expr === null ? ResolvedType::none() : $this->resolveNode($expr, $buffer);
    }

    /** The last thing assigned to `$name` before the caret. */
    protected function resolveVariable(string $name, string $buffer): ResolvedType
    {
        if (isset($this->resolving[$name])) {
            return ResolvedType::none();
        }

        if ($annotated = $this->docBlocks->annotatedVariableType($buffer, $name)) {
            return ResolvedType::of($this->qualifyString($annotated));
        }

        $assignments = (new NodeFinder())->find(
            $this->parse($buffer),
            fn (Node $node) => $node instanceof Expr\Assign
                && $node->var instanceof Expr\Variable
                && $node->var->name === $name
        );

        if ($assignments === []) {
            return ResolvedType::none();
        }

        $this->resolving[$name] = true;

        try {
            // Later assignments shadow earlier ones.
            return $this->resolveNode(end($assignments)->expr, $buffer);
        } finally {
            unset($this->resolving[$name]);
        }
    }

    protected function resolveStaticCall(Expr\StaticCall $node): ResolvedType
    {
        $class = $this->qualifyName($node->class);
        $method = $node->name instanceof Identifier ? $node->name->name : null;

        if ($class === null || $method === null) {
            return ResolvedType::none();
        }

        $declared = $this->methodType($class, $method);

        if (! $declared->isEmpty()) {
            return $declared;
        }

        return is_subclass_of($class, Model::class) && in_array($method, self::ELOQUENT_STATIC_FORWARDS, true)
            ? ResolvedType::of($class)
            : ResolvedType::none();
    }

    protected function resolveContainerCall(Expr\FuncCall $node): ResolvedType
    {
        if (! $node->name instanceof Name) {
            return ResolvedType::none();
        }

        if (! in_array(strtolower($node->name->toString()), self::CONTAINER_HELPERS, true)) {
            return ResolvedType::none();
        }

        $argument = $node->args[0]->value ?? null;

        // `app(User::class)` — the only form that names its own return type.
        if ($argument instanceof Expr\ClassConstFetch && $argument->name instanceof Identifier
            && strtolower($argument->name->name) === 'class') {
            return ResolvedType::of($this->qualifyName($argument->class));
        }

        return ResolvedType::none();
    }

    protected function resolveMethodCall(Expr $node, string $buffer): ResolvedType
    {
        $receiver = $this->resolveNode($node->var, $buffer);
        $method = $node->name instanceof Identifier ? $node->name->name : null;

        if ($method === null || $receiver->isEmpty()) {
            return ResolvedType::none();
        }

        // Taking one item out of a collection whose element type the annotation
        // that produced it already told us. Laravel types these as generics,
        // which reflection cannot follow.
        if ($receiver->element !== null && $this->takesOneElement($receiver->first(), $method)) {
            return ResolvedType::of($receiver->element);
        }

        return $this->methodType($receiver->first(), $method);
    }

    protected function resolvePropertyFetch(Expr $node, string $buffer): ResolvedType
    {
        $receiver = $this->resolveNode($node->var, $buffer);
        $property = $node->name instanceof Identifier ? $node->name->name : null;

        if ($property === null || $receiver->isEmpty()) {
            return ResolvedType::none();
        }

        return $this->propertyType($receiver->first(), $property);
    }

    protected function takesOneElement(?string $class, string $method): bool
    {
        return $class !== null
            && in_array($method, self::ELEMENT_METHODS, true)
            && is_a($class, Traversable::class, true);
    }

    /** A property's type: declared natively, or documented with `@property`. */
    public function propertyType(?string $class, string $name): ResolvedType
    {
        if ($class === null || ! class_exists($class)) {
            return ResolvedType::none();
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->hasProperty($name)) {
            $native = $this->namedType($class, (new ReflectionProperty($class, $name))->getType());

            if ($native !== null) {
                return ResolvedType::of($native);
            }
        }

        [$type, $element] = $this->docBlocks->propertyType($reflection, $name);

        return ResolvedType::of($type, $element);
    }

    public function methodType(?string $class, string $method): ResolvedType
    {
        if ($class === null || ! method_exists($class, $method)) {
            return ResolvedType::none();
        }

        $reflection = new ReflectionMethod($class, $method);

        if ($native = $this->namedType($class, $reflection->getReturnType())) {
            return ResolvedType::of($native);
        }

        [$type, $element] = $this->docBlocks->returnType($reflection);

        return ResolvedType::of($type, $element);
    }

    /**
     * Reduce a native type to a class name. Unions are searched for their first
     * class member, so `DateTimeImmutable|false` resolves rather than being
     * discarded.
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

    protected function qualifyName(mixed $name): ?string
    {
        return $name instanceof Name ? $this->qualifyString($name->toString()) : null;
    }

    /**
     * Turn whatever was written into a class that exists — a short name is
     * looked up in the class index, so `new Sale` resolves without anyone
     * having to remember the namespace.
     */
    protected function qualifyString(string $class): ?string
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

    /**
     * Parse a partial snippet, keeping whatever statements are complete.
     *
     * The line under the caret is a syntax error by definition — it is being
     * typed — so errors are collected rather than thrown and the statements
     * above it, which hold the assignments, still come back.
     *
     * @return Node[]
     */
    protected function parse(string $code): array
    {
        return $this->parser->parse('<?php '.$code, new ErrorHandler\Collecting()) ?? [];
    }
}
