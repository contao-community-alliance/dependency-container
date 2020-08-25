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
 * @package    contao-community-alliance/dependency-container* @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2013-2020Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test\DependencyInjection;

use DependencyInjection\Container\DependencyInjection\CcaDependencyInjectionExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function dirname;

final class CcaDependencyInjectionExtensionTest extends TestCase
{
    /**
     * Temporary directory.
     *
     * @var string
     */
    private $tempDir;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('cca-dic-test');
        mkdir($this->tempDir, 0700, true);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        if (!file_exists($this->tempDir)) {
            return;
        }
        $children = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($children as $child) {
            if ($child->isDir()) {
                rmdir($child);
            } else {
                unlink($child);
            }
        }
        rmdir($this->tempDir);
    }

    /**
     * Tests adding the bundle services to the container.
     *
     * @return void
     */
    public function testLoad()
    {
        $container = $this->mockContainerBuilder();
        $extension = new CcaDependencyInjectionExtension();
        $extension->load([], $container);

        $this->assertTrue($container->has('cca.legacy_dic'));
    }

    /**
     * Test container building.
     *
     * @return void
     */
    public function testBuild()
    {
        $container = $this->mockContainerBuilder();
        $extension = new CcaDependencyInjectionExtension();

        mkdir($this->tempDir . '/system/modules/foobar/config', 0700, true);
        touch($this->tempDir . '/system/modules/foobar/config/services.php');
        mkdir($this->tempDir . '/app/Resources/contao/config', 0700, true);
        touch($this->tempDir . '/app/Resources/contao/config/services.php');
        mkdir($this->tempDir . '/system/config', 0700, true);
        touch($this->tempDir . '/system/config/services.php');

        $container->setParameter('kernel.root_dir', $this->tempDir . '/app');
        $container->setParameter('kernel.bundles', [
            'TestBundle' => 'DependencyInjection\Container\Test\Mocks\Bundles\TestBundle\TestBundle',
            'foobar'     => 'Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle',
        ]);

        $extension->load([], $container);

        $this->assertSame(
            [
                dirname(__DIR__) . '/Mocks/Bundles/TestBundle/Resources/contao/config/services.php',
                $this->tempDir . '/system/modules/foobar/config/services.php',
                $this->tempDir . '/app/Resources/contao/config/services.php',
                $this->tempDir . '/system/config/services.php'
            ],
            $container->getParameter('cca.legacy_dic')
        );
    }

    /**
     * Test container building.
     *
     * @return void
     */
    public function testBuildWithBundlesWithoutResources()
    {
        $container = $this->mockContainerBuilder();
        $extension = new CcaDependencyInjectionExtension();

        $container->setParameter('kernel.root_dir', $this->tempDir . '/app');
        $container->setParameter('kernel.bundles', [
            'TestBundleNoResources' => 'DependencyInjection\Container\Test\Mocks\Bundles\TestBundleNoResources\TestBundleNoResources',
            'foobar'     => 'Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle',
        ]);

        $extension->load([], $container);

        $this->assertSame([], $container->getParameter('cca.legacy_dic'));
    }

    /**
     * Returns a Symfony container with the Contao core extension configuration.
     */
    protected function mockContainerBuilder($projectDir = '')
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.cache_dir', $projectDir.'/var/cache');
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter('kernel.root_dir', $projectDir.'/app');
        $container->setParameter('kernel.bundles', []);

        return $container;
    }
}
