<?php

use MattFerris\Di\Di;
use MattFerris\Di\ContainerInterface;
use MattFerris\Di\BundleInterface;

class DiTest extends PHPUnit_Framework_TestCase
{
    public function testGetSet()
    {
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

    /**
     * @testGetSet
     * @expectedException MattFerris\Di\DuplicateDefinitionException
     * @expectedExceptionMessage Duplicate definition for "DI"
     */
    public function testDuplicateDefinitionException()
    {
        $di = new Di();
        $di->set('DI', new stdClass());
    }

    /**
     * @depends testGetSet
     */
    public function testFind()
    {
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

    public function testGetSetParameters()
    {
        $di = new Di();
        $di->setParameters(array('foo' => 'bar'));
        $this->assertEquals($di->getParameter('foo'), 'bar');
    }

    public function testInjectStaticMethod()
    {
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

    /**
     * @depends testInjectStaticMethod
     */
    public function testInjectConstructor()
    {
        $di = new Di();
        $class = 'DiTest_A';

        $a = $di->injectConstructor($class, array('di' => '%DI'));
        $this->assertEquals($a->getDi(), $di);
    }

    /**
     * @depends testInjectConstructor
     */
    public function testInjectConsructorWithNoConstructor()
    {
        $di = new Di();
        $class = 'DiTest_B';

        $a = $di->injectConstructor($class, array('di' => '%DI'));
        $this->assertTrue(is_object($a));
    }

    /**
     * @depends testInjectStaticMethod
     */
    public function testInjectMethod()
    {
        $di = new Di();
        $a = new DiTest_A($di);
        $return = $di->injectMethod($a, 'test', array('di' => '%DI'));
        $this->assertEquals($return['di'], $di);
    }

    /**
     * @depends testInjectStaticMethod
     */
    public function testInjectFunction()
    {
        $di = new Di();
        $this->assertEquals($di->injectFunction(function (Di $di) {
            return $di;
        }, array('di' => '%DI')), $di);
    }

    /**
     * @depends testInjectStaticMethod
     */
    public function testTypeResolution()
    {
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
    }

    /**
     * @depends testTypeResolution
     * @expectedException MattFerris\Di\DependencyResolutionException
     * @expectedExceptionMessage Failed to resolve dependency "stdClass"
     */
    public function testDependencyResolutionException()
    {
        $di = new Di();
        $di->set('Foo', function (\stdClass $bar) {
            return 'baz';
        });
        $di->get('Foo');
    }

    /**
     * @depends testGetSet
     */
    public function testRegister()
    {
        $di = new Di();
        $di->register(new DiTest_Bundle());
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

class DiTest_Bundle implements BundleInterface
{
    public function register(ContainerInterface $di)
    {
        $di->set('test', new stdClass());
    }
}
