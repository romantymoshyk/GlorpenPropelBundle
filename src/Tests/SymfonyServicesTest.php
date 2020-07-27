<?php

/**
 * This file is part of the GlorpenPropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license GPLv3
 */

namespace Glorpen\Propel\PropelBundle\Tests;

use Glorpen\Propel\PropelBundle\Tests\Fixtures\Services\EventTester;
use Glorpen\Propel\PropelBundle\Tests\TestKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Glorpen\Propel\PropelBundle\Dispatcher\EventDispatcherProxy;
use Glorpen\Propel\PropelBundle\Events\ModelEvent;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Arkadiusz Dzięgiel
 */
class SymfonyServicesTest extends WebTestCase
{
    protected $kernels = array();

    protected function doTearDown()
    {
        foreach ($this->kernels as $kernel) {
            $kernel->shutdown();
        }
    }

    protected function getContainer($containerBuilder)
    {
        $kernel = $this->createKernel();
        $kernel->setContainerBuilder($containerBuilder);
        $kernel->boot();

        $this->kernels[] = $kernel;

        return $kernel->getContainer();
    }

    protected function prepareEventTester($containerBuilder)
    {
        $c = $this->getContainer($containerBuilder);

        $model = $this->getMockBuilder('BaseObject')->getMock();
        EventDispatcherProxy::trigger('model.save', new ModelEvent($model));

        return $c;
    }

    public function builderForTestServiceWiring(ContainerBuilder $c)
    {
        $def = $c->register('test.events1', 'Glorpen\Propel\PropelBundle\Tests\Fixtures\Services\EventTester');
        $def->setPublic(true);
        $def->addTag('propel.event', array('event'=>'model.save', 'method'=>'handleEvent'));
    }

    public function testServiceWiring()
    {
        if(Kernel::MAJOR_VERSION >= 5){
            $this->markTestSkipped('PropelBundle has incomplete support of SF5');
        }

        $c = $this->prepareEventTester(array($this, 'builderForTestServiceWiring'));

        $this->assertNotNull($c->get('test.events1')->handledEvent, 'Event was handled');
    }

    public function builderForTestPublicCircularDependencies(ContainerBuilder $c)
    {
        $def = $c->register('test.events1', 'Glorpen\Propel\PropelBundle\Tests\Fixtures\Services\EventTester');
        $def->setPublic(true);
        $def->addArgument(new Reference('glorpen.propel.event.dispatcher'));
        $def->addTag('propel.event', array('event'=>'model.save', 'method'=>'handleEvent'));
    }

    public function testPublicCircularDependencies()
    {
        if(Kernel::MAJOR_VERSION >= 5){
            $this->markTestSkipped('PropelBundle has incomplete support of SF5');
        }

        $c = $this->prepareEventTester(array($this, 'builderForTestPublicCircularDependencies'));

        $te = $c->get('test.events1');
        $this->assertNotNull($te->arg1, 'EventDispatcher was injected');
        $this->assertNotNull($te->handledEvent, 'Event was handled');
    }

    public function builderForTestPrivateCircularDependencies(ContainerBuilder $c)
    {
        $def = $c->register('test.events1', 'Glorpen\Propel\PropelBundle\Tests\Fixtures\Services\EventTester');
        /* @var $def \Symfony\Component\DependencyInjection\Definition */
        $def->setPublic(false);
        $def->addArgument(new Reference('glorpen.propel.event.dispatcher'));
        $def->addTag('propel.event', array('event'=>'model.save', 'method'=>'handleEvent'));

        $def = $c->register('test.events_tracker', 'Glorpen\Propel\PropelBundle\Tests\Fixtures\Services\EventTester');
        $def->setPublic(true);
        $def->addArgument(new Reference('test.events1'));
    }

    public function testPrivateCircularDependencies()
    {
        if(Kernel::MAJOR_VERSION >= 5){
            $this->markTestSkipped('PropelBundle has incomplete support of SF5');
        }

        $c = $this->prepareEventTester(array($this, 'builderForTestPrivateCircularDependencies'));

        $te = $c->get('test.events_tracker')->arg1;

        $this->assertNotNull($te->arg1, 'EventDispatcher was injected');
        $this->assertNotNull($te->handledEvent, 'Event was handled');
    }

    //TODO: add test for subscribers

    protected static function getKernelClass()
    {
        return 'Glorpen\Propel\PropelBundle\Tests\TestKernel';
    }
}
