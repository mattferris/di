<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * BundleInterface.php
 * @copyright Copyright (c) 2015 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

interface BundleInterface
{
    /**
     * @param ContainerInterface $di
     */
    public function register(ContainerInterface $di);
}

