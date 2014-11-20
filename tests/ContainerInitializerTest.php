<?php

/**
 * Dependency Container for Contao Open Source CMS
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  (c) 2013 Contao Community Alliance
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    dependency-container
 * @license    LGPL-3.0+
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use DependencyInjection\Container\ContainerInitializer;

/**
 * Test the class ContainerInitializer.
 */
class ContainerInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock the config class.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function mockConfig()
    {
        $GLOBALS['container'] = new \Pimple();

        $stub = $this->getMock(
            'Config',
            array(
                'getInstance',
                'getActiveModules',
            )
        );

        $stub->expects($this->any())
            ->method('getActiveModules')
            ->will($this->returnValue(array()));

        $GLOBALS['container']['config'] = $stub;
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
        $GLOBALS['container'] = new \Pimple();

        $this->mockConfig();

        define('TL_ROOT', __DIR__);
        define('TL_MODE', 'FE');
        $this->getMock('FrontendUser');

        $initializer = new ContainerInitializer();
        $initializer->init();

        $this->assertTrue(isset($GLOBALS['container']['config']));
        $this->assertTrue(isset($GLOBALS['container']['environment']));
        $this->assertTrue(isset($GLOBALS['container']['database.connection']));
        $this->assertTrue(isset($GLOBALS['container']['input']));
        $this->assertTrue(isset($GLOBALS['container']['user']));
        $this->assertTrue(isset($GLOBALS['container']['session']));

        $this->assertInstanceOf('\FrontendUser', $GLOBALS['container']['user']);
    }
}