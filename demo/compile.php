<?php

/**
 * Declare beans as Definitions, compile to a reflection-free file, then resolve.
 *
 *   build time : set(Definition) on the container, Dumper compiles them
 *   runtime    : setValue() the scalars, loadCompiled(), get() your beans
 *
 * The container never instantiates from a Definition — objects come from the
 * compiled factories. No ReflectionClass / newInstanceArgs at runtime. Run:
 *
 *   php compile.php
 */

require __DIR__ . '/autoload.php';

use GenAI\Container\Bean\Definition;
use GenAI\Container\Container;
use GenAI\Container\ContainerRegister;

// --- Classes to wire -----------------------------------------------------

class Pdo_Stub
{
    public $dsn;
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }
}

class UserRepository
{
    public $db;
    public function __construct(Pdo_Stub $db)
    {
        $this->db = $db;
    }
}

class UserController
{
    public $repo;
    public $pageSize;
    public function __construct(UserRepository $repo, $pageSize = 25)
    {
        $this->repo     = $repo;
        $this->pageSize = $pageSize;
    }
}

// --- BUILD TIME: declare definitions, then compile -----------------------
//
// Declared out of order on purpose: compilation does not care, and resolution
// is lazy at runtime.

$register = new ContainerRegister();

// Definition::of() autowires straight from each constructor:
//   UserController($repo: UserRepository, $pageSize = 25)
//     -> repo = dependency(UserRepository), pageSize = value(25)  [baked default]
//   UserRepository($db: Pdo_Stub)
//     -> db   = dependency(Pdo_Stub)
//   Pdo_Stub($dsn)                       (scalar, no default)
//     -> dsn  = parameter (its value is declared below, baked into the dump)
$register->set('UserController', Definition::of('UserController'));
$register->set('UserRepository', Definition::of('UserRepository'));
$register->set('Pdo_Stub', Definition::of('Pdo_Stub'));

// Parameters are declared at build time too — baked into the compiled file.
$register->setParameter('Pdo_Stub::dsn', 'mysql:host=localhost;dbname=app');

// Explicit construction still works if you want full control:
//   Definition::create('UserController', array(
//       Param::dependency('repo', 'UserRepository'),
//       Param::value('pageSize', 25),
//   ));

@mkdir(__DIR__ . '/cache', 0777, true);
$file = __DIR__ . '/cache/Container.php';      // class Cache\Container (PSR-4: cache/Container.php)
$register->dumpToFile($file);                  // ContainerRegister drives the Dumper helper

echo "--- generated " . basename($file) . " ---\n";
echo file_get_contents($file);
echo "--- end generated ---\n\n";

// --- RUNTIME: the compiled container is a ready subclass — just `new` it ----

$c = new \Cache\Container();                    // every factory baked into its constructor

$controller = $c->get('UserController');       // built from the dump, zero reflection

printf("Pdo_Stub::dsn    : %s\n", $c->get('Pdo_Stub::dsn'));
printf("controller class : %s\n", get_class($controller));
printf("  page size      : %d\n", $controller->pageSize);
printf("  repo class     : %s\n", get_class($controller->repo));
printf("  db dsn         : %s\n", $controller->repo->db->dsn);
printf("  db is shared   : %s\n", $c->get('Pdo_Stub') === $controller->repo->db ? 'yes' : 'no');
printf("has UserController: %s\n", $c->has('UserController') ? 'yes' : 'no');
printf("keys             : %s\n", implode(', ', $c->keys()));
