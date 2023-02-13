<?php

use MattFerris\Di\Di;
use MattFerris\Di\ContainerInterface;
use MattFerris\Di\DependencyResolutionException;
use MattFerris\Di\DuplicateDefinitionException;
use MattFerris\Di\NotFoundException;
use MattFerris\Provider\ProviderInterface;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class DiTest extends TestCase
{
    public function testGetSet() {
        // test set() with an instance
        $di = new Di();
        $obj = new stdClass();
        $di->set('Test', $obj);
        $this->assertEquals($di->get('Test'), $obj);

        // test set() with a Closure
        $di = new Di();
        $di->set('Test', function() {
            $obj = new stdClass();
            $obj->foo = 'bar';
            return $obj;
        }); 
        $this->assertEquals($di->get('Test')->foo, 'bar');

        // test set() with a class name
        $di = new Di();
        $di->set('Test', DiTest_A::class);
        $this->assertEquals($di->get('Test')->getDi(), $di);

        // test singleton
        $di = new Di();
        $di->set('Test', function () {
            return new stdClass();
        }, true);
        $obj = $di->get('Test');
        $obj->foo = 'bar';
        unset($obj);
        $obj = $di->get('Test');
        $this->assertEquals($obj->foo, 'bar');
    }


    #[Depends('testGetSet')
    public function testGetWithNonExistentId() {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No definition found for "Foo"');

        $di = new Di();
        var_dump($di->get('Foo'));
    }


    #[Depends('testGetSet')
    public function testDuplicateDefinitionException() {
        $this->expectException(DuplicateDefinitionException::class);
        $this->expectExceptionMessage('Duplicate definition for "DI"');

        $di = new Di();
        $di->set('DI', new stdClass());
    }


    #[Depends('testGetSet')
    public function testFind() {
        $di = new Di();
        $di
            ->set('Foo.Foo', function ($di) {
                $obj = new stdClass();
                $obj->foo = 'foo';
                return $obj;
            })
            ->set('Foo.Bar', function ($di) {
                $obj = new stdClass();
                $obj->foo = 'bar';
                return $obj;
            });
        $objs = $di->find('Foo.');
        $this->assertTrue(is_array($objs));
        $this->assertCount(2, $objs);
        $this->assertInstanceOf('stdClass', $objs['Foo.Foo']);
        $this->assertInstanceOf('stdClass', $objs['Foo.Bar']);
        $this->assertEquals('foo', $objs['Foo.Foo']->foo);
        $this->assertEquals('bar', $objs['Foo.Bar']->foo);
    }


    public function testGetSetParameters() {
        $di = new Di();
        $di->setParameters(array('foo' => 'bar'));
        $this->assertEquals($di->getParameter('foo'), 'bar');
    }


    public function testInjectStaticMethod() {
        $di = new Di();
        $di->setParameters(array('bar' => 'bar'));
        $class = 'DiTest_A';

        $return = $di->injectStaticMethod($class, 'test', array('di' => '%DI'));
        $this->assertEquals($return['di'], $di);
        $this->assertEquals($return['foo'], null);
        $this->assertEquals($return['bar'], null);

        $return = $di->injectStaticMethod($class, 'test', array('di' => '%DI', 'foo' => 'foo'));
        $this->assertEquals($return['di'], $di);
        $this->assertEquals($return['foo'], 'foo');
        $this->assertEquals($return['bar'], null);

        $return = $di->injectStaticMethod($class, 'test', array('di' => '%DI', 'foo' => 'foo', 'bar' => ':bar'));
        $this->assertEquals($return['di'], $di);
        $this->assertEquals($return['foo'], 'foo');
        $this->assertEquals($return['bar'], 'bar');
    }


    #[Depends('testInjectStaticMethod')
    public function testInjectConstructor() {
        $di = new Di();
        $class = 'DiTest_A';

        $a = $di->injectConstructor($class, array('di' => '%DI'));
        $this->assertEquals($a->getDi(), $di);
    }


    #[Depends('testInjectConstructor')
    public function testInjectConsructorWithNoConstructor() {
        $di = new Di();
        $class = 'DiTest_B';

        $a = $di->injectConstructor($class, array('di' => '%DI'));
        $this->assertTrue(is_object($a));
    }


    #[Depends('testInjectStaticMethod')
    public function testInjectMethod() {
        $di = new Di();
        $a = new DiTest_A($di);
        $return = $di->injectMethod($a, 'test', array('di' => '%DI'));
        $this->assertEquals($return['di'], $di);
    }


    #[Depends('testInjectStaticMethod')
    public function testInjectFunction() {
        $di = new Di();
        $this->assertEquals($di->injectFunction(function (Di $di) {
            return $di;
        }, array('di' => '%DI')), $di);
    }


    #[Depends('testInjectStaticMethod')
    public function testTypeResolution() {
        $di = new Di();

        // resolve type based on supplied arguments
        $this->assertEquals($di->injectFunction(function (Di $di) {
            return $di;
        }, array('di' => '\MattFerris\Di\Di')), $di);

        // resolve type based on type-hinted definition closure
        $obj = new \stdClass();
        $di->set('Bar', $obj);
        $di->set('Baz', function (\stdClass $obj) {
            return $obj;
        });
        $this->assertEquals($di->get('Baz'), $obj);

        // resolve type based on type-hinted interface
        $di->set('Foo', function (\MattFerris\Di\ContainerInterface $container) {
            return $container;
        });
        $this->assertEquals($di->get('Foo'), $di);
    }


    #[Depends('testTypeResolution')
    public function testDependencyResolutionException() {
        $this->expectException(DependencyResolutionException::class);
        $this->expectExceptionMessage('Failed to resolve dependency "stdClass"');

        $di = new Di();
        $di->set('Foo', function (\stdClass $bar) {
            return 'baz';
        });
        $di->get('Foo');
    }


    #[Depends('testTypeResolution')
    public function testDeepTypeResolution() {
        $di = new Di();
        $di->setDeepTypeResolution(true);

        $di->set('Foo', function (\DiTest_A $foo) {
            return $foo;
        });

        $this->assertEquals(get_class($di->get('Foo')), 'DiTest_A');
    }


    #[Depends('testDeepTypeResolution')
    public function testDeepTypeResolutionFailure() {
        $this->expectException(DependencyResolutionException::class);
        $this->expectExceptionMessage('Failed to resolve dependency "DiTest_D"');

        $di = new Di();
        $di->setDeepTypeResolution(true);

        $di->set('Foo', function (\DiTest_C $foo) {
            return;
        });

        $di->get('Foo');
    }


    #[Depends('testGetSet')
    public function testDelegation() {
        $diA = new Di();
        $diB = new Di();
        $diA->delegate('Foo', $diB);
        $diB->set('Foo', new stdClass());

        $this->assertTrue($diA->has('Foo'));
        $this->assertEquals(get_class($diA->get('Foo')), 'stdClass');

        $diA = new Di();
        $diB = new Di();
        $diA->delegate('Foo.', $diB);
        $diB->set('Foo.Bar', new stdClass());

        $this->assertEquals(get_class($diA->get('Foo.Bar')), 'stdClass');
    }


    #[Depends('testGetSet');
    public function testRegister() {
        $di = new Di();
        $di->register(new DiTest_Provider());
        $this->assertInstanceOf('stdClass', $di->get('test'));
    }
}


class DiTest_A
{
    protected $di;

    public function __construct(Di $di)
    {
        $this->di = $di;
    }

    public function getDi()
    {
        return $this->di;
    }

    static public function test(Di $di, $foo = null, $bar = null)
    {
        return array('di' => $di, 'foo' => $foo, 'bar' => $bar);
    }
}

class DiTest_B
{
}

class DiTest_C
{
    public function __construct(\DiTest_D $di_d)
    {
    }
}

class DiTest_Provider implements ProviderInterface
{
    public function provides($consumer)
    {
        $consumer->set('test', new stdClass());
    }
}
