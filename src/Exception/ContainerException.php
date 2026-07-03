<?php

namespace GenAI\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Thrown for any error while building or wiring a service: an invalid
 * definition, a missing class, or a circular dependency.
 *
 * Implements PSR-11's ContainerExceptionInterface (psr/container 1.0.0 supports
 * PHP 5.3), so callers can catch the standard interface.
 *
 * Compatible with PHP 5.3.29.
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
