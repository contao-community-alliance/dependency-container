<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2022 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2013-2022 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\DependencyInjection;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

use function dirname;
use function is_dir;

/**
 * This is the class that loads and manages the bundle configuration
 */
class CcaDependencyInjectionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        // Add all resource paths to keep them handy.
        $container->setParameter('cca.legacy_dic', $this->getResourcePaths($container));
    }

    /**
     * Returns the Contao resources paths as array.
     *
     * @param ContainerBuilder $container The container builder.
     *
     * @return list<string>
     */
    private function getResourcePaths(ContainerBuilder $container): array
    {
        $paths = [];
        /**
         * @psalm-suppress UndefinedDocblockClass - PHP 8 support for UnitEnum declaration.
         * @psalm-suppress InvalidArgument
         * @psalm-suppress PossiblyInvalidCast
         */
        $rootDir = $container->getParameter('kernel.project_dir');
        assert(is_string($rootDir));

        /**
         * @psalm-suppress UndefinedDocblockClass - PHP 8 support for UnitEnum declaration.
         * @var array<string, class-string> $bundles
         */
        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $name => $class) {
            $path = $this->getResourcePathFromBundle($rootDir, $name, $class);
            if (null !== $path) {
                $paths[] = $path;
            }
        }

        if (is_readable($rootDir . '/app/Resources/contao/config/services.php')) {
            $paths[] = $rootDir . '/app/Resources/contao/config/services.php';
        }

        if (is_readable($rootDir . '/system/config/services.php')) {
            $paths[] = $rootDir . '/system/config/services.php';
        }

        return $paths;
    }

    /**
     * Generate the path from the bundle (if any exists).
     *
     * @param string       $rootDir The app root dir.
     * @param string       $name    The name of the bundle.
     * @param class-string $class   The bundle class name.
     */
    private function getResourcePathFromBundle(string $rootDir, string $name, string $class): ?string
    {
        $path = (ContaoModuleBundle::class === $class)
            ? sprintf('%s/system/modules/%s', $rootDir, $name)
            : $this->getResourcePathFromClassName($class);

        if (null === $path) {
            return null;
        }

        $file = $path . '/config/services.php';
        if (is_readable($file)) {
            return $file;
        }

        return null;
    }

    /**
     * Returns the resources path from the class name.
     *
     * @param class-string $class The class name of the bundle.
     */
    private function getResourcePathFromClassName(string $class): ?string
    {
        $reflection = new ReflectionClass($class);
        $dir = dirname($reflection->getFileName()) . '/Resources/contao';
        if (is_dir($dir)) {
            return $dir;
        }

        return null;
    }
}
