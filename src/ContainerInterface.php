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

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
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
     * @param \Psr\Container\ContainerInterface $container
     * @return self
     */
    public function delegate($prefix, \Psr\Container\ContainerInterface $container);

    /**
     * Invoke a static method using injected argument values.
     *
     * @param string $class The class which the method belongs to
     * @param string $method The method to invoke
     * @param array $args Optional array of arguments to use for injection
     * @return mixed The value returned by the invoked method
     */
    public function injectStaticMethod($class, $method, array $args = array());

    /**
     * Invoke a constructor using injected argument values.
     *
     * @param string $class The class which the constructor belongs to
     * @param array $args Optional array of arguments to use for injection
     * @return object The instance returned by the invoked constructor
     */
    public function injectConstructor($class, array $args = array());

    /**
     * Invoke a method using injected argument values.
     *
     * @param object $instance The object which the method belongs to
     * @param string $method The method to invoke
     * @param array $args Optional array of arguments to use for injection
     * @return mixed The value returned by the invoked method
     */
    public function injectMethod($instance, $method, array $args = array());

    /**
     * Invoke a function using injected argument values.
     *
     * @param mixed $function The function to invoke
     * @param array $args Optional array of arguments to use for injection
     * @return mixed The value returned by the invoked function
     */
    public function injectFunction($function, array $args = array());
}

