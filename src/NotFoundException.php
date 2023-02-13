<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * NotFoundException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class NotFoundException extends Exception implements ContainerExceptionInterface, NotFoundExceptionInterface
{
    /**
     * @var string $id The entry ID that was not found in the container
     */
    protected $id;


    /**
     * @param string $id The entry ID that was not found in the container
     */
    public function __construct(string $id) {
        $this->id = $id;
        parent::__construct('No definition found for "'.$id.'"');
    }


    /**
     * Return the entry ID that was not found in the container`
     *
     * @return string $id The entry ID
     */
    public function getId(): string {
        return $this->id;
    }
}
