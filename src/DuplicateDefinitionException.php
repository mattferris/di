<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * DuplicateDefinitionException.php
 * @copyright Copyright (c) 2016 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use Exception;

class DuplicateDefinitionException extends Exception
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
        $msg = 'Duplicate definition for "'.$key.'"';
        parent::__construct($msg);
    }

    /**
     * @return string The definition key
     */
    public function getKey()
    {
        return $this->key;
    }
}
