<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2018 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use DependencyInjection\Container\PimpleGate;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This tests the PimpleGate.
 */
class PimpleGateTest extends TestCase
{
    /**
     * Test that the container get's correctly instantiated.
     */
    public function testInstantiation(): void
    {
        $container = new PimpleGate();

        $this->assertInstanceOf('DependencyInjection\Container\PimpleGate', $container);
    }

    /**
     * Test that the container get's correctly instantiated.
     */
    public function testInstantiationWithSymfonyContainer(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate([], $symfony);

        $this->assertInstanceOf('DependencyInjection\Container\PimpleGate', $container);
        $this->assertSame($symfony, $container->getContainer());
        $this->assertSame($symfony, $container['symfony']);
    }

    /**
     * Test that symfony services get delegated.
     */
    public function testGetSymfonyService(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate([], $symfony);

        $symfony->expects($this->once())->method('get')->with('dummy-service')->willReturn($service = new stdClass());

        $this->assertSame($service, $container->getSymfonyService('dummy-service'));
    }

    /**
     * Test that symfony services get delegated.
     */
    public function testDelegateSymfonyService(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate([], $symfony);

        $symfony->expects($this->once())->method('get')->with('dummy-service')->willReturn($service = new stdClass());

        $container->provideSymfonyService('dummy-service');

        $this->assertTrue(isset($container['dummy-service']));
        $this->assertSame($service, $container['dummy-service']);
    }

    /**
     * Test that symfony services cannot get delegated if a service exists.
     */
    public function testDelegateSymfonyServiceFailsForExistingService(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate(['dummy-service' => new stdClass()], $symfony);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service dummy-service has already been defined.');
        $container->provideSymfonyService('dummy-service');
    }

    /**
     * Test that delegated symfony services can not get overwritten.
     */
    public function testDelegateSymfonyServiceCannotGetOverridden(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate([], $symfony);

        $container->provideSymfonyService('dummy-service');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service dummy-service has been delegated to symfony, cannot set.');
        $container['dummy-service'] = 'test override';
    }

    /**
     * Test that delegated symfony services can not get removed.
     */
    public function testDelegateSymfonyServiceCannotBeRemoved(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate([], $symfony);

        $container->provideSymfonyService('dummy-service');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service dummy-service has been delegated to symfony, cannot unset.');
        unset($container['dummy-service']);
    }

    /**
     * Test that non delegated symfony services can get removed.
     */
    public function testServiceCanBeRemoved(): void
    {
        $symfony   = $this->getMockForAbstractClass(ContainerInterface::class);
        $container = new PimpleGate(['dummy-service' => 'bar'], $symfony);

        unset($container['dummy-service']);

        $this->assertFalse(isset($container['dummy-service']));
    }
}
