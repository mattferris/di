<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * DuplicateDefinitionException.php
 * @copyright Copyright (c) 2020 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @var string The definition key
     */
    protected $key;

    /**
     * @param string $key The definition key
     */
    public function __construct($key)
    {
        $this->key = $key;
        $msg = 'No definition found for "'.$key.'"';
        parent::__construct($msg);
    }

    /**
     * @return string The definition key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * PSR 11 refers to "idenifier" instead of key, so this provides are more standard
     * getter instead of getKey()
     *
     * @return string The definition "identifier"
     */
    public function getIdentitifer()
    {
        return $this->key;
    }
}
