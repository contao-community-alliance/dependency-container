<?php

/**
 * Dependency Container for Contao Open Source CMS
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  (c) 2013 Contao Community Alliance
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
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
                'get'
            )
        );

        $stub->expects($this->any())
            ->method('get')
            ->with('dbDriver')
            ->will($this->returnValue('mySQL'));

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

        $initializer = $this->getMock('DependencyInjection\Container\ContainerInitializer', array('getActiveModules'));
        $initializer->expects($this->any())
            ->method('getActiveModules')
            ->will($this->returnValue(array()));
        /** @var ContainerInitializer $initializer */
        $initializer->init();

        $this->assertTrue(isset($GLOBALS['container']['config']));
        $this->assertTrue(isset($GLOBALS['container']['environment']));
        $this->assertTrue(isset($GLOBALS['container']['database.connection']));
        $this->assertTrue(isset($GLOBALS['container']['input']));
        $this->assertTrue(isset($GLOBALS['container']['user']));
        $this->assertTrue(isset($GLOBALS['container']['session']));
        $this->assertTrue(isset($GLOBALS['container']['page-provider']));

        $this->assertEquals(
            0,
            array_search(
                array('DependencyInjection\Container\PageProvider', 'setPage'),
                $GLOBALS['TL_HOOKS']['getPageLayout']
            ),
            'PageProvider::setPage() is not the first hook in TL_HOOKS::getPageLayout!'
        );

        $this->assertInstanceOf('\FrontendUser', $GLOBALS['container']['user']);
    }
}
