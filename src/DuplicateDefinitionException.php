<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * DuplicateDefinitionException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;


class DuplicateDefinitionException extends Exception implements ContainerExceptionInterface
{
    /**
     * @var string The duplicate entry ID
     */
    protected $id;

    /**
     * @param string $id The duplicate entry ID
     */
    public function __construct(string $id) {
        $this->id = $id;
        $msg = 'Duplicate definition for "'.$id.'"';
        parent::__construct($msg);
    }

    /**
     * Return the duplicate entry ID
     *
     * @return string The duplicate entry ID
     */
    public function getId(): string {
        return $this->id;
    }


    /**
     * Alias for getId()
     */
    public function getKey(): string {
        return $this->getId();
    }
}
