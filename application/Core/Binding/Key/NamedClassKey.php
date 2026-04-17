<?php


namespace Zeno\Core\Binding\Key;

/**
 * A key for a class-type-hinted constructor parameter with a parameter name.
 *
 * @template-covariant T of object
 */
class NamedClassKey
{
    private string $className;
    private string $parameterName;

    /**
     * @param class-string<T> $className
     */
    private function __construct(string $className, string $parameterName)
    {
        $this->className = $className;
        $this->parameterName = $parameterName;
    }

    /**
     * Create.
     *
     * @template TC of object
     * @param class-string<TC> $className An interface.
     * @param string $parameterName A constructor parameter name (w/o '$').
     * @return self<TC>
     */
    public static function create(string $className, string $parameterName): self
    {
        return new self($className, $parameterName);
    }

    public function toString(): string
    {
        return $this->className . ' $' . $this->parameterName;
    }
}
