<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * ServiceProvider.php
 * @copyright Copyright (c) 2015 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

use MattFerris\Provider\ProviderInterface;
use MattFerris\Provider\InvalidConsumerException;

class ServiceProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function provides($consumer)
    {
        if (!($consumer instanceof ContainerInterface)) {
            throw new InvalidConsumerException();
        }
    }
}

