DI
==

[![Build Status](https://travis-ci.org/mattferris/di.svg?branch=master)](https://travis-ci.org/mattferris/di)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a88c116a-3019-4573-8fa1-0b844dda22dd/mini.png)](https://insight.sensiolabs.com/projects/a88c116a-3019-4573-8fa1-0b844dda22dd)

DI is a dependency injection container library for PHP.

Registering Services
--------------------

To start out, create an instance of `MattFerris\Di\Di`. Use the `set()` method to register services. Closures (anonymous functions) are used to return an instance of the service and accomplish any appropriate intialization. The container passes an instance of itself to the closure as an argument.

    use MattFerris\Di\Di;

    $di = new Di();

    // register 'FooService'
    $di->set('FooService', function ($di) {
        return new \FooService();
    });

An instance of the service can now be retrieved via `get()`.

    $fooService = $di->get('FooService');

Each request for the service will return a new instance of `FooService`. You can define a service as a singleton by passing `true` as the third argument.

    $di->set('FooService', function ($di) {
        return new \FooService();
    }, true);

The closure now checks to see if an instance of `FooService` exists, and if so it will return it. If an instance doesn't exist, it will be created, stored and returned.

If you service relies on another service, a reference to the other service can easily be retrieved from within the definition.

    $di->set('BarService', function ($di) {
        return new \BarService($di->get('FooService'));
    });

You can check if a service has been defined using `has()`.

    if ($di->has('FooService')) {
        echo 'Yay!';
    }

You can use `find()` to return all services matching a prefix. This is useful if you have multiple services of a similar type and their definitions are named similarly. For example, if you two remote FTP servers and you wanted to save a file to both of them, you could register the FTP services as `FTP.host1' and `FTP.host2`. Using `find()` to pull instances of these services allows you application to remain host agnostic, allow you to seamlessly configure additional hosts in the future.

    $services = $di->find('FTP.');

    foreach ($services as $key => $service) {
        $service->saveFile($file);
    }

`$services` in this case would look like:

    array(
        'FTP.host1' => [instance of \FtpService],
        'FTP.host2' => [instance of \FtpService]
    );

Defining Parameters
-------------------

You can define parameters for any service definitions to use via `setParameter()` and `getParameter()`. This allows you to create more dynamic service definitions and configurations.

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

The database connection settings can now be defined dynamically. Also notice the DB service is defined as a singleton.

Custom Injection
----------------

In some cases, you may want to dynamically instantiate a class, or call a method (static or otherwise), closure or function. The methods `injectConstructor()`, `injectionMethod()`, `injectStaticMethod()`, and `injectFunction()` can accomplish this in a flexible way.

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

The above example returns an instance of `FooClass` with injected constructor arguments. Take note that the second argument passed to `injectConstructor()` is an array, and this array defines which arguments get which values. These values can be specified in any order, and the method will match the keys with the actual argument names in the contructors signature. The following example would have the same result.

    $object = $di->injectConstructor(
        'FooClass',
        array('blah' => 'bling', 'argB' => 'bar', 'argA' => 'foo')
    );

`injectConstructor()` assigns the appropriate keys to their corresponding arguments. Keys with no corresponding argument are ignored. This principle is the same for the other `inject` methods.

    $result = $di->injectMethod($object, 'someMethod', array('foo' => 'bar'));

    $result = $di->injectStaticMethod('FooClass', 'someMethod', array('foo' => 'bar'));

    $result = $di->injectFunction('someFunction', array('foo' => 'bar'));

    $result = $di->injectFunction($closure, array('foo' => 'bar'));

Of course, you could inject defined services and parameters as well.

    $object = $di->injectConstructor(
        'FooClass',
        array('argA' => $di->get('FooService'), 'argB' => $di->getParameter('foo'))
    );

For convenience, the `inject` methods understand placeholders for services and parameters.

    $object = $di->injectConstructor(
        'FooClass',
        array('argA' => '%FooService', 'argB' => ':foo')
    );

Services can be referenced by prefixing the service name with a percent sign (%), and parameters can be referenced by prefixing the parameter name with a colon (:).
