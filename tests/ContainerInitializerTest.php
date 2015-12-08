<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013 Contao Community Alliance
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  2013-2015 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
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
