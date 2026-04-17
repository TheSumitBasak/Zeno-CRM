<?php


namespace Zeno\Core\Binding\Key;

/**
 * A parameter-name-only key.
 */
class NamedKey
{
    private string $parameterName;

    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * Create.
     *
     * @param string $parameterName A constructor parameter name (w/o '$').
     */
    public static function create(string $parameterName): self
    {
        return new self($parameterName);
    }

    public function toString(): string
    {
        return '$' . $this->parameterName;
    }
}
