<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2017 Contao Community Alliance
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
 * @copyright  2013-2017 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use Contao\System;
use DependencyInjection\Container\ContainerInitializer;
use DependencyInjection\Container\PimpleGate;
use DependencyInjection\Container\Test\Mocks\Contao\Config;

/**
 * Test the class ContainerInitializer.
 */
class ContainerInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($GLOBALS['container']);
    }

    /**
     * Test that an exception is thrown when the container is invalid.
     *
     * @return void
     *
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Dependency container is incompatible class. Expected PimpleGate but found DateTime
     */
    public function testBailsForInvalidContainer()
    {
        $GLOBALS['container'] = new \DateTime();

        $initializer = new ContainerInitializer();

        $initializer->init();
    }

    /**
     * Test that the symfony container is fetched.
     *
     * @return void
     */
    public function testObtainsSymfonyContainerFromSystemClass()
    {
        if (!interface_exists('Contao\CoreBundle\Framework\ContaoFrameworkInterface')) {
            $this->markTestSkipped('Only available in Contao 4');
        }

        System::setContainer(
            $container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface')
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
     * Test that the symfony container is fetched.
     *
     * @return void
     */
    public function testObtainsSymfonyContainerFromKernel()
    {
        if (!interface_exists('Contao\CoreBundle\Framework\ContaoFrameworkInterface')) {
            $this->markTestSkipped('Only available in Contao 4');
        }

        $GLOBALS['kernel'] = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\KernelInterface');

        $GLOBALS['kernel']->expects($this->once())->method('getContainer')->willReturn(
            $container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface')
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
     *
     * @return void
     */
    public function testDoesNotFindSymfonyContainerWhenNoneAvailable()
    {
        $initializer = $this->mockInitializer();

        $initializer->init();

        $this->assertNull($GLOBALS['container']->getContainer());
        $this->assertNull($GLOBALS['container']['symfony']);
    }

    /**
     * Test the init method.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testInit()
    {
        $GLOBALS['container'] = new PimpleGate();

        define('TL_ROOT', __DIR__);
        define('TL_MODE', 'FE');

        $initializer = $this->mockInitializer([
            'Contao\Config'       => $config = new Config(['dbDriver' => 'mySQL']),
            'Contao\FrontendUser' => $user = new \stdClass()
        ]);

        /** @var ContainerInitializer $initializer */
        $initializer->init();
    }

    /**
     * Test the init method.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testInitCli()
    {
        $GLOBALS['container'] = new PimpleGate();

        define('TL_ROOT', __DIR__);
        define('TL_MODE', 'CLI');

        $initializer = $this->mockInitializer([
            'Contao\Config'      => $config = new Config(['dbDriver' => 'mySQL']),
            'Contao\BackendUser' => $user   = new \stdClass()
        ]);

        /** @var ContainerInitializer $initializer */
        $initializer->init();
    }

    /**
     * Mock an initializer with the passed singletons
     *
     * @param array $singletons
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainerInitializer
     */
    private function mockInitializer($singletons = [])
    {
        $initializer = $this->getMock(
            'DependencyInjection\Container\ContainerInitializer',
            ['getActiveModulePaths', 'getInstanceOf']
        );
        $initializer->expects($this->any())
            ->method('getActiveModulePaths')
            ->willReturn([]);

        if (empty($singletons)) {
            $singletons = ['Contao\Config' => $config = new Config(['dbDriver' => 'mySQL'])];
        }

        $initializer->expects($this->any())
            ->method('getInstanceOf')
            ->willReturnCallback(function ($className) use ($singletons) {
                $singleton = trim($className, '\\');
                if (!isset($singletons[$singleton])) {
                    throw new \RuntimeException('Not mocked! ' . $className);
                }

                return $singletons[$singleton];
            });

        return $initializer;
    }
}
