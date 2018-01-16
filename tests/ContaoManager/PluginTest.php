<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2018 Contao Community Alliance <https://c-c-a.org>
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/event-dispatcher/LICENSE LGPL-3.0+
 * @link       https://github.com/contao-community-alliance/event-dispatcher
 * @filesource
 */


namespace DependencyInjection\Container\Test\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use DependencyInjection\Container\CcaDependencyInjectionBundle;
use DependencyInjection\Container\ContaoManager\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $plugin = new Plugin();

        $this->assertInstanceOf('DependencyInjection\Container\ContaoManager\Plugin', $plugin);
    }

    /**
     * Tests the getBundles() method.
     */
    public function testGetBundles()
    {
        $parser = $this->getMockForAbstractClass(ParserInterface::class);

        /** @var BundleConfig $config */
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\BundleConfig', $config);
        $this->assertSame(CcaDependencyInjectionBundle::class, $config->getName());
        $this->assertSame(
            [
                ContaoCoreBundle::class,
                ContaoManagerBundle::class
            ],
            $config->getLoadAfter()
        );
    }
}
