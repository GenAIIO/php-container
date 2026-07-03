<?php

namespace GenAI\Container;

use GenAI\Container\Bean\Definition;
use GenAI\Container\Exception\ContainerException;
use GenAI\Container\Util\Dumper;

/**
 * The build-time half of the container: where beans are declared.
 *
 * You combine all your bean Definitions here (typically from the attribute
 * scanner), then hand the register to the Dumper, which compiles the
 * definitions into a reflection-free factory file. The runtime Container loads
 * that file — it never sees this class.
 *
 *   $register = new ContainerRegister();
 *   $register->set('App\\UserController', Definition::create('App\\UserController', array(
 *       Param::dependency('repo', 'App\\UserRepository'),
 *       Param::value('pageSize', 25),
 *   )));
 *   $register->setParameter('App\\Pdo::dsn', 'mysql:host=localhost;dbname=app');
 *   $register->dumpToFile(__DIR__ . '/cache/container.php');
 *
 * Both beans and parameters are compiled into the file, so the runtime Container
 * needs no declaration calls — it only loadCompiled() + get().
 *
 * Compatible with PHP 5.3.29.
 */
class ContainerRegister
{
    /**
     * Declared bean definitions, keyed by id.
     *
     * @var Definition[]
     */
    private $definitions = array();

    /**
     * Declared scalar parameters, keyed by id. Compiled into the dump as
     * constant-returning factories, so the runtime resolves them via get().
     *
     * @var array
     */
    private $parameters = array();

    /**
     * Declare a bean definition under an id (normally its class name).
     *
     * @param string     $id
     * @param Definition $definition
     * @return ContainerRegister $this, for chaining.
     */
    public function set($id, Definition $definition)
    {
        if (isset($this->definitions[$id])) {
            throw new ContainerException(sprintf(
                'Bean "%s" is already defined. Bean ids must be unique — two'
                . ' #[Service]/#[Repository]/#[Configuration]/#[Bean] declarations resolve to'
                . ' the same id (often an app bean colliding with a bundle default). Rename'
                . ' one, give the #[Bean] an explicit id, or drop the duplicate.',
                $id
            ));
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    /**
     * Declare a scalar parameter value for an id (e.g. 'App\\Pdo::dsn'). It is
     * baked into the compiled file and resolved at runtime via get($id) — the
     * runtime container never sets parameters itself.
     *
     * @param string $id
     * @param mixed  $value
     * @return ContainerRegister $this, for chaining.
     */
    public function setParameter($id, $value)
    {
        $this->parameters[$id] = $value;

        return $this;
    }

    /**
     * Whether a definition is registered for the id.
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * The Definition registered for the id, or null if none.
     *
     * @param string $id
     * @return Definition|null
     */
    public function get($id)
    {
        return $this->has($id) ? $this->definitions[$id] : null;
    }

    /**
     * Drop a definition.
     *
     * @param string $id
     * @return ContainerRegister $this, for chaining.
     */
    public function remove($id)
    {
        unset($this->definitions[$id]);

        return $this;
    }

    /**
     * Every declared parameter, keyed by id.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Compile every declared definition and parameter to PHP source (via the
     * Dumper helper).
     *
     * @return string PHP source, starting with "<?php", returning a Closure.
     */
    public function dump()
    {
        return Dumper::dump($this->definitions, $this->parameters);
    }

    /**
     * Compile and write the source to a file. The directory must exist.
     *
     * @param string $path
     * @return int Bytes written.
     * @throws ContainerException If the file cannot be written.
     */
    public function dumpToFile($path)
    {
        $bytes = @file_put_contents($path, $this->dump());
        if ($bytes === false) {
            throw new ContainerException(sprintf('Could not write compiled container to "%s".', $path));
        }

        return $bytes;
    }
}
