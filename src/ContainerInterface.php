<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * ContainerInterface.php
 * @copyright Copyright (c) 2015 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

interface ContainerInterface extends \Interop\Container\ContainerInterface
{
    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters);

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key);

    /**
     * @param string $key
     * @param mixed $definition
     * @param bool $singleton
     * @return self
     */
    public function set($key, $definition, $singleton = false);

    /**
     * @param string $prefix
     * @param \Interop\Container\ContainerInterface $container
     * @return self
     */
    public function delegate($prefix, \Interop\Container\ContainerInterface $container);

    /**
     * @param string $class
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function injectStaticMethod($class, $method, array $args);

    /**
     * @param string $class
     * @param array $args
     * @return object
     */
    public function injectConstructor($class, array $args);

    /**
     * @param object $instance
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function injectMethod($instance, $method, array $args);
}

