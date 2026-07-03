<?php

namespace GenAI\Container\Bean;

use GenAI\Container\Exception\ContainerException;

/**
 * A single bean definition: the class to build and its constructor params (in
 * order). Every bean is a singleton at runtime, so there is no per-bean scope.
 *
 * This replaces the old nested-array shape. Build them with explicit params:
 *
 *   $def = Definition::create('App\\UserController', array(
 *       Param::dependency('repo', 'App\\UserRepository'),
 *       Param::value('pageSize', 25),
 *   ));
 *
 * or autowire from the constructor with of() (build time, PHP 7+ reflection):
 *
 *   $def = Definition::of('App\\UserController');
 *
 * The class name doubles as the container id.
 *
 * This file parses on PHP 5.3, but of() uses reflection and is meant to run at
 * compile time on PHP 8 (the rest of the class is PHP 5.3.29-safe).
 */
class Definition
{
    /** @var string */
    private $class;

    /** @var Param[] */
    private $params;

    /** @var string|null Factory bean id, when this bean is built by a method. */
    private $factoryService;

    /** @var string|null Factory method name on the factory bean. */
    private $factoryMethod;

    /**
     * Private — build a Definition through create(), of() or factory().
     *
     * @param string|null $class
     * @param Param[]     $params
     * @param string|null $factoryService
     * @param string|null $factoryMethod
     * @throws ContainerException If neither a class nor a factory is given.
     */
    private function __construct($class, $params = array(), $factoryService = null, $factoryMethod = null)
    {
        if (empty($class) && ($factoryService === null || $factoryMethod === null)) {
            throw new ContainerException('A Definition needs a class, or a factory service + method.');
        }

        $this->class          = $class;
        $this->factoryService = $factoryService;
        $this->factoryMethod  = $factoryMethod;
        $this->params         = array();

        foreach ($params as $param) {
            $this->add($param);
        }
    }

    /**
     * Build a definition with an explicit list of params.
     *
     * @param string  $class  Fully-qualified class name; also the bean id.
     * @param Param[] $params Constructor params, in declaration order.
     * @return Definition
     * @throws ContainerException If $class is empty.
     */
    public static function create($class, $params = array())
    {
        return new self($class, $params);
    }

    /**
     * Build a definition produced by calling a method on another bean (the
     * "factory service") — e.g. a #[Bean] method on a configuration class.
     * Compiles to: $c->get($factoryService)->$factoryMethod(...$params).
     *
     * @param string  $factoryService Bean id whose method produces this bean.
     * @param string  $factoryMethod  Method to call on it.
     * @param Param[] $params         The method's params, in order.
     * @return Definition
     */
    public static function factory($factoryService, $factoryMethod, $params = array())
    {
        return new self(null, $params, $factoryService, $factoryMethod);
    }

    /**
     * Autowire a definition from the class constructor.
     *
     * Reflects the constructor and turns each parameter into a Param:
     *   - a class/interface type  -> Param::dependency (wired by type)
     *   - a scalar with a default -> Param::value (the default, baked in)
     *   - any other scalar        -> Param::parameter (supplied at runtime)
     *
     * Build-time only: it uses PHP 7+/8 reflection and expects the class to be
     * autoloadable. You can still ->add() afterwards to append extra params, but
     * note of() has already filled in the constructor's.
     *
     * @param string $class
     * @return Definition
     * @throws ContainerException If the class does not exist.
     */
    public static function of($class)
    {
        if (!class_exists($class)) {
            throw new ContainerException(sprintf(
                'Cannot autowire "%s": class does not exist.',
                $class
            ));
        }

        $params      = array();
        $reflection  = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $params[] = Param::fromReflection($parameter);
            }
        }

        return new self($class, $params);
    }

    /**
     * Append a constructor param.
     *
     * @param Param $param
     * @return Definition $this, for chaining.
     */
    public function add(Param $param)
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return Param[]
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return bool Whether this bean is produced by a factory method.
     */
    public function isFactory()
    {
        return $this->factoryService !== null;
    }

    /**
     * @return string|null The factory bean id (null unless isFactory()).
     */
    public function getFactoryService()
    {
        return $this->factoryService;
    }

    /**
     * @return string|null The factory method name (null unless isFactory()).
     */
    public function getFactoryMethod()
    {
        return $this->factoryMethod;
    }
}

