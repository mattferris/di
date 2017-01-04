DI
==

[![Build Status](https://travis-ci.org/mattferris/di.svg?branch=master)](https://travis-ci.org/mattferris/di)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a88c116a-3019-4573-8fa1-0b844dda22dd/mini.png)](https://insight.sensiolabs.com/projects/a88c116a-3019-4573-8fa1-0b844dda22dd)

DI is a dependency injection container library for PHP.

Registering Services
--------------------

To start out, create an instance of `MattFerris\Di\Di`. Use the `set()` method
to register services. Closures (anonymous functions) are used to return an
instance of the service and accomplish any appropriate intialization. You can
get an instance of the container by specifying an argument named `$di`, or by
specyfing any argument with a type-hint of `MattFerris\Di\Di`.

```php
use MattFerris\Di\Di;

$di = new Di();

// register 'FooService'
$di->set('FooService', function ($di) {
    return new \FooService($di);
});

// register `FooService` using type-hinted argument
$di->set('FooService', function (Di $container) {
    return new \FooService($container);
});
```

An instance of the service can now be retrieved via `get()`.

```php
$fooService = $di->get('FooService');
```

Each request for the service will return a new instance of `FooService`. By
default, all definitions are singletons. A third argument can be used to disable
this behaviour.

```php
$di->set('FooService', function ($di) {
    return new \FooService();
}, Di::NON_SINGLETON);
```

The closure now checks to see if an instance of `FooService` exists, and if so
it will return it. If an instance doesn't exist, it will be created, stored and
returned.

If you service relies on another service, a reference to the other service can
easily be retrieved from within the definition.

```php
$di->set('BarService', function ($di) {
    return new \BarService($di->get('FooService'));
});

You can check if a service has been defined using `has()`.

if ($di->has('FooService')) {
    echo 'Yay!';
}
```

You can use `find()` to return all services matching a prefix. This is useful if
you have multiple services of a similar type and their definitions are named 
imilarly. For example, if you two remote FTP servers and you wanted to save a
file to both of them, you could register the FTP services as `FTP.host1' and
`FTP.host2`. Using `find()` to pull instances of these services allows you
application to remain host agnostic, allow you to seamlessly configure
additional hosts in the future.

```php
$services = $di->find('FTP.');

foreach ($services as $key => $service) {
    $service->saveFile($file);
}
```

`$services` in this case would look like:

```php
array(
    'FTP.host1' => [instance of \FtpService],
    'FTP.host2' => [instance of \FtpService]
);
```

If you attempt to set a definition using a key that already exists, a
`DuplicateDefinitionException` will be throw. The duplicate key can be retrieved
using the `DuplicateDefinitionExeption::getKey()` method.

### Type-Based Injection

In some cases, you may require a specific type of object for your service
definition, but may not know what the name of the service's definition is within
the container. Instead of requesting the directly from the container, you can
have the service injected into your the closure of your service definition by
type-hinting the argument.

```php
$di->set('FooService', function (\Bar\Service\Class $bar) {
    return new \FooService($bar);
});
```

If a definition exists that has an instance of `\Bar\Service\Class`, it will
injected into the closure for consumption by your defined service. Likewise, you
could also specify an interface or a trait name to inject.

If a dependency can't be resolved, a `DependencyResolutionExceptioon` will be
thrown. The type that couldn't be resolved can be retrieved using the
`DependencyResolutionException::getType()` method.

Optionally, `Di` can use deep resolution of a dependency. This can be enabled
by calling `$di->setDeepTypeResolution(true)`. When enabled, for type-based
dependencies that can't be satisfied using definitions within the container,
`Di` will attempt to instantiate an new instance of the type directly and use
the new instance to satisfy the dependency. Constructor arguments will attempt
to be satisfied using dependency injection as well.

### Service Providers

You can use *providers* to isolate service configuration within your domains. A
service *provider* is any class that extends `MattFerris\Di\ServiceProvider`, or
implements `MattFerris\Provider\ProviderInterface`. When registered via
`register($provider)`, the *provider's* `provides()` method is passed an
instance of the container and can then register services.

```php
class MyProvider extends MattFerris\Di\ServiceProvider
{
    public function provides($consumer)
    {
        parent::provides($consumer); // validate $consumer

        $di->set('MyService', function () { ... });
    }
}

$di->register(new MyProvider());

$myService = $di->get('MyService');
```

Defining Parameters
-------------------

You can define parameters for any service definitions to use via
`setParameter()` and `getParameter()`. This allows you to create more dynamic
service definitions and configurations.

``php
$di->setParameter('db', array(
    'user' => 'joe',
    'pass' => 'p4ssw0rd',
    'host' => 'localhost',
    'db' => 'foo'
));

$di->set('DB', function ($di) {
    $cfg = $di->getParameter('db');
    return new \DbConnection(
        $cfg['user'], $cfg['pass'], $cfg['host'], $cfg['db']
    );
}, true);
```

The database connection settings can now be defined dynamically. Also notice the
DB service is defined as a singleton.

Delegation
----------

Key prefixes can be delegated to other containers for lookup. This allows parts
of your application, or third party components, to configure their own local
containers, but plug them into a central container.

```php
$di->delegate('Foo.', $container);
```

Now, any key beginning with `Foo.` will be delegated to `$container` for
handling.

Custom Injection
----------------

In some cases, you may want to dynamically instantiate a class, or call a method 
(static or otherwise), closure or function. The methods `injectConstructor()`,
`injectionMethod()`, `injectStaticMethod()`, and `injectFunction()` can
accomplish this in a flexible way.

```php
class FooClass
{
    public function __construct($argA, $argB)
    {
    }
}

$object = $di->injectConstructor(
    'FooClass',
     array('argA' => 'foo', 'argB' => 'bar')
);
```

The above example returns an instance of `FooClass` with injected constructor
arguments. Take note that the second argument passed to `injectConstructor()`
is an array, and this array defines which arguments get which values. These
values can be specified in any order, and the method will match the keys with
the actual argument names in the contructors signature. The following example
would have the same result.

```php
$object = $di->injectConstructor(
    'FooClass',
    array('blah' => 'bling', 'argB' => 'bar', 'argA' => 'foo')
);
```

`injectConstructor()` assigns the appropriate keys to their corresponding
arguments. Keys with no corresponding argument are ignored. This principle is
the same for the other `inject` methods.

```php
$result = $di->injectMethod($object, 'someMethod', array('foo' => 'bar'));

$result = $di->injectStaticMethod('FooClass', 'someMethod', array('foo' => 'bar'));

$result = $di->injectFunction('someFunction', array('foo' => 'bar'));

$result = $di->injectFunction($closure, array('foo' => 'bar'));
```

Of course, you could inject defined services and parameters as well.

```php
$object = $di->injectConstructor(
    'FooClass',
    array('argA' => $di->get('FooService'), 'argB' => $di->getParameter('foo'))
);
```

For convenience, the `inject` methods understand placeholders for services and
parameters.

```php
$object = $di->injectConstructor(
    'FooClass',
    array('argA' => '%FooService', 'argB' => ':foo')
);
```

Services can be referenced by prefixing the service name with a percent sign
(%), and parameters can be referenced by prefixing the parameter name with a
colon (:).

Type-based injection occurs in all cases when using the `inject*` methods unless
the argument is supplied when invoking the method. In this way, you can override
type-based injection for particular arguments. You can also force type-based
injection on arguments by supplying a type for an argument.

```php
$object = $di->injectConstructor(
    'FooClass',
    array('argA' => '\Foo\Service\Class')
);
```

Argument values string with a backslash are assumed to be type-hinted arguments.
