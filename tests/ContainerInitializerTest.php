<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2018 Contao Community Alliance
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2013-2018 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use Contao\System;
use DateTime;
use DependencyInjection\Container\ContainerInitializer;
use DependencyInjection\Container\PimpleGate;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test the class ContainerInitializer.
 */
class ContainerInitializerTest extends TestCase
{
    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($GLOBALS['container']);
        $reflection = new ReflectionProperty(System::class, 'objContainer');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);
    }

    /**
     * Test that an exception is thrown when the container is invalid.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testBailsForInvalidContainer(): void
    {
        $GLOBALS['container'] = new DateTime();

        $initializer = new ContainerInitializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Dependency container is incompatible class. Expected PimpleGate but found DateTime'
        );
        $initializer->init();
    }

    /**
     * Test that the symfony container is fetched.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testObtainsSymfonyContainerFromSystemClass(): void
    {
        System::setContainer($container = $this->getMockForAbstractClass(ContainerInterface::class));

        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('cca.legacy_dic')
            ->willReturn([]);

        $initializer = $this->mockInitializer();

        $initializer->init();

        $this->assertSame($container, $GLOBALS['container']->getContainer());
        $this->assertSame($container, $GLOBALS['container']['symfony']);
    }

    /**
     * Test that the symfony container is fetched.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testObtainsSymfonyContainerFromKernel(): void
    {
        $GLOBALS['kernel'] = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\KernelInterface');

        $GLOBALS['kernel']->expects($this->once())->method('getContainer')->willReturn(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('cca.legacy_dic')
            ->willReturn([]);

        $initializer = $this->mockInitializer();

        $initializer->init();

        $this->assertSame($container, $GLOBALS['container']->getContainer());
        $this->assertSame($container, $GLOBALS['container']['symfony']);
    }

    /**
     * Test that the symfony container is not fetched when none is available.
     */
    public function testThrowsWhenSymfonyContainerNotAvailable(): void
    {
        $initializer = $this->mockInitializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not obtain symfony container');

        $initializer->init();
    }

    /**
     * Test the init method.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testInit()
    {
        System::setContainer(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('cca.legacy_dic')
            ->willReturn([__DIR__ . '/Mocks/Bundles/TestBundle/Resources/contao/config/services.php']);

        $GLOBALS['container'] = new PimpleGate([], $container);

        $initializer = $this->mockInitializer();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches(
            '#.*/Mocks/Bundles/TestBundle/Resources/contao/config/services.php loaded#'
        );
        /** @var ContainerInitializer $initializer */
        $initializer->init();
    }

    /**
     * Mock an initializer with the passed singletons
     *
     * @return MockObject|ContainerInitializer
     */
    private function mockInitializer(array $singletons = []): ContainerInitializer
    {
        $initializer = $this->getMockBuilder(ContainerInitializer::class)
            ->onlyMethods(['getInstanceOf'])
            ->getMock();

        if (empty($singletons)) {
            $singletons = [
                'Contao\Config' => $config = $this->getMockBuilder('stdClass')->addMethods(['get'])->getMock()
            ];
            $config
                ->expects($this->any())
                ->method('get')
                ->with('dbDatabase')
                ->willReturn('databaseName');
        }

        $initializer->expects($this->any())
            ->method('getInstanceOf')
            ->willReturnCallback(function ($className) use ($singletons) {
                $singleton = trim($className, '\\');
                if (!isset($singletons[$singleton])) {
                    throw new RuntimeException('Not mocked! ' . $className);
                }

                return $singletons[$singleton];
            });

        return $initializer;
    }
}
