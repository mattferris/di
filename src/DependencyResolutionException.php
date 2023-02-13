<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * DependencyResolutionException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;


class DependencyResolutionException extends Exception implements ContainerExceptionInterface
{
    /**
     * @var string The definition type
     */
    protected $type;

    /**
     * @param string $type The definition type
     * @param string $key The definition key
     */
    public function __construct($type) {
        $this->type = $type;
        $msg = 'Failed to resolve dependency "'.$type.'"';
        parent::__construct($msg);
    }

    /**
     * @return string The definition type
     */
    public function getType(): string {
        return $this->type;
    }
}
