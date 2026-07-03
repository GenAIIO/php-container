<?php

namespace GenAI\Container\Bean;

/**
 * One constructor parameter of a Definition.
 *
 * A param is exactly one of three shapes; use the named constructors so the
 * intent is clear and impossible states stay unrepresentable:
 *
 *   Param::dependency('repo', 'App\\UserRepository')  // resolved by type id
 *   Param::value('pageSize', 25)                      // literal baked in
 *   Param::parameter('dsn')                           // a container parameter
 *
 * value() carries a hasValue flag so a literal null is honoured rather than
 * mistaken for "no value".
 *
 * Compatible with PHP 5.3.29.
 */
class Param
{
    /** @var string */
    private $name;

    /** @var string|null Class/interface id to resolve via the container. */
    private $type;

    /** @var mixed Baked literal; meaningful only when $hasValue is true. */
    private $value;

    /** @var bool */
    private $hasValue;

    /**
     * Private — build a Param through one of the named constructors below.
     *
     * @param string      $name
     * @param string|null $type
     * @param mixed       $value
     * @param bool        $hasValue
     */
    private function __construct($name, $type = null, $value = null, $hasValue = false)
    {
        $this->name     = $name;
        $this->type     = $type;
        $this->value    = $value;
        $this->hasValue = (bool) $hasValue;
    }

    /**
     * A dependency wired by type: the container resolves get($type).
     *
     * @param string $name
     * @param string $type Fully-qualified class/interface name (the bean id).
     * @return Param
     */
    public static function dependency($name, $type)
    {
        return new self($name, $type, null, false);
    }

    /**
     * A literal value baked in at build time (the 5.3-safe stand-in for a
     * #[Value(...)] attribute). null is a valid value.
     *
     * @param string $name
     * @param mixed  $value
     * @return Param
     */
    public static function value($name, $value)
    {
        return new self($name, null, $value, true);
    }

    /**
     * A scalar left unbaked: looked up as the container parameter
     * "Class::name" at runtime.
     *
     * @param string $name
     * @return Param
     */
    public static function parameter($name)
    {
        return new self($name, null, null, false);
    }

    /**
     * Adapt a Param from a reflected constructor parameter:
     *   - a class/interface type  -> dependency (wired by type)
     *   - a scalar with a default -> value (the default, baked in)
     *   - any other scalar        -> parameter (supplied at runtime)
     *
     * Build-time only: uses PHP 7+/8 reflection.
     *
     * @param \ReflectionParameter $parameter
     * @return Param
     */
    public static function fromReflection(\ReflectionParameter $parameter)
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return self::dependency($name, $type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return self::value($name, $parameter->getDefaultValue());
        }

        return self::parameter($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasValue()
    {
        return $this->hasValue;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
