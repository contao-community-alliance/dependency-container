<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2016 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2016 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use DependencyInjection\Container\PimpleGate;

/**
 * This tests the PimpleGate.
 */
class PimpleGateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the container get's correctly instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $container = new PimpleGate();

        $this->assertInstanceOf('DependencyInjection\Container\PimpleGate', $container);
    }

    /**
     * Test that the container get's correctly instantiated.
     *
     * @return void
     */
    public function testInstantiationWithSymfonyContainer()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate([], $symfony);

        $this->assertInstanceOf('DependencyInjection\Container\PimpleGate', $container);
        $this->assertSame($symfony, $container->getContainer());
        $this->assertSame($symfony, $container['symfony']);
    }

    /**
     * Test that isContao4() returns true if symfony container is present.
     *
     * @return void
     */
    public function testIsContao4()
    {
        $container = new PimpleGate(
            [],
            $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface')
        );

        $this->assertTrue($container->isContao4());
    }

    /**
     * Test that isContao4() returns false if no symfony container is present.
     *
     * @return void
     */
    public function testIsNotContao4()
    {
        $container = new PimpleGate();

        $this->assertFalse($container->isContao4());
    }

    /**
     * Test that symfony services get delegated.
     *
     * @return void
     */
    public function testGetSymfonyService()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate([], $symfony);

        $symfony->expects($this->once())->method('get')->with('dummy-service')->willReturn($service = new \stdClass());

        $this->assertSame($service, $container->getSymfonyService('dummy-service'));
    }

    /**
     * Test that symfony services get delegated.
     *
     * @return void
     */
    public function testDelegateSymfonyService()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate([], $symfony);

        $symfony->expects($this->once())->method('get')->with('dummy-service')->willReturn($service = new \stdClass());

        $container->provideSymfonyService('dummy-service');

        $this->assertTrue(isset($container['dummy-service']));
        $this->assertSame($service, $container['dummy-service']);
    }

    /**
     * Test that symfony services cannot get delegated if a service exists.
     *
     * @return void
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Service dummy-service has already been defined.
     */
    public function testDelegateSymfonyServiceFailsForExistingService()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate(['dummy-service' => new \stdClass()], $symfony);

        $container->provideSymfonyService('dummy-service');
    }

    /**
     * Test that delegated symfony services can not get overwritten.
     *
     * @return void
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Service dummy-service has been delegated to symfony, cannot set.
     */
    public function testDelegateSymfonyServiceCannotGetOverridden()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate([], $symfony);

        $container->provideSymfonyService('dummy-service');

        $container['dummy-service'] = 'test override';
    }

    /**
     * Test that delegated symfony services can not get removed.
     *
     * @return void
     *
     * @expectedException        \LogicException
     * @expectedExceptionMessage Service dummy-service has been delegated to symfony, cannot unset.
     */
    public function testDelegateSymfonyServiceCannotBeRemoved()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate([], $symfony);

        $container->provideSymfonyService('dummy-service');

        unset($container['dummy-service']);
    }

    /**
     * Test that non delegated symfony services can get removed.
     *
     * @return void
     */
    public function testServiceCanBeRemoved()
    {
        $symfony   = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
        $container = new PimpleGate(['dummy-service' => 'bar'], $symfony);

        unset($container['dummy-service']);

        $this->assertFalse(isset($container['dummy-service']));
    }
}
