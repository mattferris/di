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

use MattFerris\Provider\ConsumerInterface;
use MattFerris\Provider\ProviderInterface;

class Di implements ContainerInterface, ConsumerInterface
{
    /**
     * @const bool
     */
    const NON_SINGLETON = false;

    /**
     * @const bool
     */
    const SINGLETON = true;

    /**
     * @var array
     */
    protected $definitions = array();

    /**
     * @var array
     */
    protected $types = array();

    /**
     * @var array
     */
    protected $instances = array();

    /**
     * @var array
     */
    protected $delegates = array();

    /**
     * @var array
     */
    protected $parameters = null;

    /**
     * @var bool Use deep type resolution; yes if true, no if false (default no)
     */
    protected $useDeepTypeResolution;

    public function __construct()
    {
        $this->set('DI', $this);
    }

    /**
     * Enable/disable deep type resolution on or off. By default, this feature
     * is disabled. If enabled, when Di can't find a definition for a particular
     * type, it will attempt to instantiate a new instance of the type
     * automatically and use this instance for injection.
     *
     * @param bool $status True to enable, false to disable
     * @return self
     */
    public function setDeepTypeResolution($status)
    {
        $this->useDeepTypeResolution = (bool)$status;
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
     * Setup a container definition. By default, all definitions are singletons,
     * but this can be changed via the $singleton aregument. Additionally, an
     * optional fourth argument, $type, can be specified to assist with type-
     * based injection. If no type is specified, then the container can't use
     * the definition for type-based injection unless the definition is simply
     * an object (as opposed to a Closure). This is because the container won't
     * know what type of of instance the definition will create.
     *
     * @param string $key The name of the definition
     * @param mixed $definition The definition (either an instance or Closure)
     * @param bool $singleton Optional. True if singleton, false othewise. Default is true.
     * @param string $type Optiona. The type (class) of the resulting instance.
     * @return ContainerInterface The container instance
     */
    public function set($key, $definition, $singleton = true, $type = null)
    {
        // make sure $key isn't already definied, otherwise throw an exception
        if (!isset($this->definitions[$key])) {
            $this->definitions[$key] = array(
                'singleton' => $singleton,
                'def' => $definition
            );

            // if the definition is an instance, use it's types
            $class = get_class($definition);
            if ($class !== 'Closure') {
                $type = $class;
                $ref = new \ReflectionClass($class);
                $types = $ref->getInterfaceNames();
                foreach ($types as $t) {
                    if (!isset($this->types[$t])) {
                        $this->types[$t] = array();
                    }
                    $this->types[$t][] = $key;
                }
            }

            // if $type is specified, set the definition type
            if (!is_null($type)) {
                $this->definitions[$key]['type'] = $type;

                if (!isset($this->types[$type])) {
                    $this->types[$type] = array();
                }

                $this->types[$type][] = $key;
            }
        } else {
            throw new DuplicateDefinitionException($key);
        }

        return $this;
    }

    /**
     * @param string $key
     * @return object
     */
    public function get($key)
    {
        // check if the key is delegated to another container
        foreach (array_keys($this->delegates) as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return $this->delegates[$prefix]->get($key);
            }
        }

        // PSR 11 requires that we throw an exception if $key doesn't exist
        if (!isset($this->definitions[$key])) {
            throw new NotFoundException($key);
        }

        $def = $this->definitions[$key];
        $return = null;

        if ($def['singleton'] === true && isset($this->instances[$key])) {
            $return = $this->instances[$key];
        } else {
            if (is_callable($def['def'])) {
                $return = $this->injectFunction($def['def'], array('di' => '%DI'));
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
        // if key is delegated, ask delegate container
        foreach (array_keys($this->delegates) as $prefix) {
            if (strpos($key, $prefix) === 0) {
                return $this->delegates[$prefix]->has($key);
            }
        }
        return isset($this->definitions[$key]);
    }

    /**
     * Delegate a key prefix to another container
     *
     * @param string $prefix The prefix to delegate
     * @param \Psr\Container\ContainerInterface $container The delegate
     * @return self
     */
    public function delegate($prefix, \Psr\Container\ContainerInterface $container)
    {
        $this->delegates[$prefix] = $container;
        return $this;
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
     * {@inheritDoc}
     */
    public function register(ProviderInterface $provider)
    {
        $provider->provides($this);
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

            $type = null;
            try {
                $class = $param->getClass();
                if (!is_null($class)) {
                    $type= $class->getName();
                }
            } catch (\ReflectionException $e) {
                $words = explode(' ', $e->getMessage());
                throw new DependencyResolutionException($words[1]);
            }

            if (isset($args[$name]) || array_key_exists($name, $args)) {
                // resolve parameter based on supplied $args
                $invokeArgs[] = $this->resolveArgument($args[$name]);
            } elseif (!is_null($type) &&
                (class_exists($type) || interface_exists($type) || trait_exists($type))) {
                // resolve parameter based on type
                $invokeArgs[] = $this->resolveType($type);
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
         * If the argument is a class name (i.e. starts with a '\'), then try
         * and resolve the type
         */
        elseif (is_string($arg) && strpos($arg, '\\') === 0) {
            try {
                $invokeArg = $this->resolveType($arg);
            } catch (DependencyResolutionFailedException $e) {
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
     * Given a type (class), try and resolve the type using dependencies that
     * have been registered with the container. If that fails, try and
     * instantiate a new instance of the class instead.
     *
     * @param string $type
     * @return object
     */
    public function resolveType($type)
    {
        $object = null;

        // strip leading slashes
        $type = ltrim($type, '\\');

        // resolution can only happen if one definition exists for the type
        if (isset($this->types[$type])) {
            if (count($this->types[$type]) === 1) {
                $def = $this->types[$type][0];
                $object = $this->get($def);
            } 
        } elseif ($this->useDeepTypeResolution) {
            $object = $this->injectConstructor($type);
        }

        if (is_null($object)) {
            throw new DependencyResolutionException($type);
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function injectStaticMethod($class, $method, array $args = array())
    {
        $invokeArgs = $this->resolveArguments(new \ReflectionMethod($class, $method), $args);
        return call_user_func_array(array($class, $method), $invokeArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function injectConstructor($class, array $args = array())
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
     * {@inheritDoc}
     */
    public function injectMethod($instance, $method, array $args = array())
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
     * {@inheritDoc}
     */
    public function injectFunction($function, array $args = array())
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

