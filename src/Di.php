<?php

/**
 * DI - A dependency injection library for PHP
 * www.bueller.ca/di
 *
 * Di.php
 * @copyright Copyright (c) 2015 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/di/license
 */

namespace MattFerris\Di;

class Di implements ContainerInterface
{
    /**
     * @var array
     */
    protected $definitions = array();

    /**
     * @var array
     */
    protected $instances = array();

    /**
     * @var array
     */
    protected $parameters = null;

    public function __construct()
    {
        $this->set('DI', $this);
    }

    /**
     * @param array $parameters
     * @return ContainerInterface
     */
    public function setParameters(array $parameters)
    {
        if (is_null($this->parameters)) {
            $this->parameters = $parameters;
        }
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key)
    {
        if (isset($this->parameters[$key]) || array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        } else {
            return null;
        }
    }

    /**
     * @param string $key
     * @param mixed $definition
     * @param bool $singleton
     * @return ContainerInterface
     */
    public function set($key, $definition, $singleton = false)
    {
        if (!isset($this->definitions[$key])) {
            $this->definitions[$key] = array(
                'singleton' => $singleton,
                'def' => $definition
            );
        }
        return $this;
    }

    /**
     * @param string $key
     * @return object
     */
    public function get($key)
    {
        if (!isset($this->definitions[$key])) {
            return null;
        }

        $def = $this->definitions[$key];
        $return = null;

        if ($def['singleton'] === true && isset($this->instances[$key])) {
            $return = $this->instances[$key];
        } else {
            if (is_callable($def['def'])) {
                $return = call_user_func($def['def'], $this);
                if ($def['singleton'] === true) {
                    $this->instances[$key] = $return;
                }
            } else {
                $return = $def['def'];
            }
        }

        return $return;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function find($prefix)
    {
        $results = array();
        foreach ($this->definitions as $name => $def) {
            if (strpos($name, $prefix) === 0) {
                $results[$name] = $this->get($name);
            }
        }
        return $results;
    }

    /**
     * @param BundleInterface $bundle
     */
    public function register(BundleInterface $bundle)
    {
        $bundle->register($this);
    }

    /**
     * @param object $reflection
     * @param array $args
     * @return array
     */
    protected function resolveArguments($reflection, array $args = array())
    {
        if (!($reflection instanceof \ReflectionMethod) && !($reflection instanceof \ReflectionFunction)) {
            throw new \InvalidArgumentException(
                '$reflection expects ReflectionMethod or ReflectionFunction'
            );
        }

        $invokeArgs = array();
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (isset($args[$name]) || array_key_exists($name, $args)) {
                $invokeArgs[] = $this->resolveArgument($args[$name]);
            }
        }

        return $invokeArgs;
    }

    /**
     * @param mixed $arg
     * @return array
     */
    protected function resolveArgument($arg)
    {
        /*
         * If the argument is an array, recursively resolve the array entries
         */
        if (is_array($arg)) {
            foreach ($arg as $k => $v) {
                $arg[$k] = $this->resolveArgument($v);
            }
            $invokeArg = $arg;
        }

        /*
         * If the argument is a placeholder for dependency (i.e. "%service"),
         * then try and resolve the dependency
         */
        elseif (is_string($arg) && strpos($arg, '%') === 0) {
            $ret = $this->get(substr($arg, 1));
            if ($ret !== null) {
                $invokeArg = $ret;
            } else {
                $invokeArg = $arg;
            }
        }

        /*
         * If the argument is a placeholder for a parameter (i.e. "%param"),
         * then try and resolve the parameter
         */
        elseif (is_string($arg) && strpos($arg, ':') === 0) {
            $ret = $this->getParameter(substr($arg, 1));
            if ($ret !== null) {
                $invokeArg = $ret;
            } else {
                $invokeArg = $arg;
            }
        }

        /*
         * The argument is just a regular string, so do nothing
         */
        else {
            $invokeArg = $arg;
        }

        return $invokeArg;
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function injectStaticMethod($class, $method, array $args)
    {
        $invokeArgs = $this->resolveArguments(new \ReflectionMethod($class, $method), $args);
        return call_user_func_array(array($class, $method), $invokeArgs);
    }

    /**
     * @param string $class
     * @param array $args
     * @return object
     */
    public function injectConstructor($class, array $args)
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->getConstructor() !== null) {
            $invokeArgs = $this->resolveArguments($reflection->getConstructor(), $args);
            return $reflection->newInstanceArgs($invokeArgs);
        } else {
            return new $class();
        }
    }

    /**
     * @param object $instance
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function injectMethod($instance, $method, array $args)
    {
        if (!is_object($instance)) {
            throw new \InvalidArgumentException(
                '$instance expects an instance of an object'
            );
        }

        $invokeArgs = $this->resolveArguments(new \ReflectionMethod($instance, $method), $args);
        return call_user_func_array(array($instance, $method), $invokeArgs);
    }

    /**
     * @param mixed $function
     * @param array $args
     * @return mixed
     */
    public function injectFunction($function, array $args)
    {
        $functionDoesntExist = !is_object($function) && !function_exists($function);
        $objectIsNotClosure = is_object($function) && !($function instanceof \Closure);

        if ($functionDoesntExist && $objectIsNotClosure) {
            throw new \InvalidArgumentException(
                '$function expects name of existing function or a Closure'
            );
        }

        $invokeArgs = $this->resolveArguments(new \ReflectionFunction($function), $args);
        return call_user_func_array($function, $invokeArgs);
    }
}

