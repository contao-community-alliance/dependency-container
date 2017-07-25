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
 * @copyright  2013-2015 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use DependencyInjection\Container\DependencyInjection\CcaDependencyInjectionExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * This tests the CcaDependencyInjectionExtension.
 */
class CcaDependencyInjectionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $extension = new CcaDependencyInjectionExtension();

        $this->assertInstanceOf(
            'DependencyInjection\Container\DependencyInjection\CcaDependencyInjectionExtension',
            $extension
        );
    }

    /**
     * Tests adding the bundle services to the container.
     *
     * @return void
     */
    public function testLoad()
    {
        $container = new ContainerBuilder(new ParameterBag());

        $extension = new CcaDependencyInjectionExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has('cca.legacy_dic'));
    }
}
