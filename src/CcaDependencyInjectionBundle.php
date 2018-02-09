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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * This is the bundle for the legacy dependency injection container.
 */
class CcaDependencyInjectionBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Add all resource paths to keep them handy.
        $container->setParameter('cca.legacy_dic', $this->getResourcePaths($container));
    }

    /**
     * Returns the Contao resources paths as array.
     *
     * @param ContainerBuilder $container The container builder.
     *
     * @return array
     */
    private function getResourcePaths(ContainerBuilder $container)
    {
        $paths   = [];
        $rootDir = dirname($container->getParameter('kernel.root_dir'));

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            if (null !== ($path = $this->getResourcePathFromBundle($rootDir, $name, $class))) {
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
     * @param string $rootDir The app root dir.
     * @param string $name    The name of the bundle.
     * @param string $class   The bundle class name.
     *
     * @return string|null
     */
    private function getResourcePathFromBundle($rootDir, $name, $class)
    {
        if ('Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle' === $class) {
            $path = sprintf('%s/system/modules/%s', $rootDir, $name);
        } else {
            $path = $this->getResourcePathFromClassName($class);
        }

        if (null !== $path && is_readable($file = $path . '/config/services.php')) {
            return $file;
        }

        return null;
    }

    /**
     * Returns the resources path from the class name.
     *
     * @param string $class The class name of the bundle.
     *
     * @return string|null
     */
    private function getResourcePathFromClassName($class)
    {
        $reflection = new \ReflectionClass($class);

        if (is_dir($dir = dirname($reflection->getFileName()).'/Resources/contao')) {
            return $dir;
        }

        return null;
    }
}
