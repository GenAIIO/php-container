<?php

namespace GenAI\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when an entry is requested from the container but no definition has
 * been registered for the given id.
 *
 * Implements PSR-11's NotFoundExceptionInterface (which itself extends
 * ContainerExceptionInterface), so a missing entry is catchable both as a
 * not-found and as a general container error.
 *
 * Compatible with PHP 5.3.29.
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
