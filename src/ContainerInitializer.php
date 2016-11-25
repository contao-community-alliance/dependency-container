<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2016 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @copyright  2013-2016 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container;

use Contao\Config;
use Contao\Database;
use Contao\ModuleLoader;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The initialization handler class for the dependency container.
 */
class ContainerInitializer
{
    /**
     * Get the currently defined global container or create it if no container is present so far.
     *
     * @return PimpleGate
     *
     * @throws \RuntimeException When an incompatible DIC is encountered.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function getContainer()
    {
        if (!isset($GLOBALS['container'])) {
            $GLOBALS['container'] = new PimpleGate([], $this->getSymfonyContainer());
        }
        $container = $GLOBALS['container'];

        if (!$container instanceof PimpleGate) {
            throw new \RuntimeException(
                'Dependency container is incompatible class. Expected PimpleGate but found ' . get_class($container),
                1
            );
        }

        return $container;
    }

    /**
     * Determine the symfony container.
     *
     * @return ContainerInterface|null
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function getSymfonyContainer()
    {
        // 1. Preferred way in contao 4.0+
        if (method_exists('Contao\System', 'getContainer')
            && ($container = System::getContainer()) instanceof ContainerInterface
        ) {
            return $container;
        }

        // 2. Fallback to fetch from kernel.
        if (isset($GLOBALS['kernel'])
            && $GLOBALS['kernel'] instanceof KernelInterface
            && ($container = $GLOBALS['kernel']->getContainer()) instanceof ContainerInterface
        ) {
            return $container;
        }

        // 3. Nothing worked out, return no container.
        return null;
    }

    /**
     * Retrieve an instance of a certain class.
     *
     * @param string $className The class name.
     *
     * @return object
     */
    public function getInstanceOf($className)
    {
        $class = new \ReflectionClass($className);

        if ($class->hasMethod('getInstance')) {
            return $class->getMethod('getInstance')->invoke(null);
        }

        return $class->newInstance();
    }

    /**
     * Call the initialization hooks.
     *
     * @param PimpleGate $container The container that got initialized.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the hook method is invalid.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function callHooks(PimpleGate $container)
    {
        if (isset($GLOBALS['TL_HOOKS']['initializeDependencyContainer']) &&
            is_array($GLOBALS['TL_HOOKS']['initializeDependencyContainer'])
        ) {
            foreach ($GLOBALS['TL_HOOKS']['initializeDependencyContainer'] as $callback) {
                if (is_array($callback)) {
                    $class = new \ReflectionClass($callback[0]);
                    if (!$class->hasMethod($callback[1])) {
                        if ($class->hasMethod('__call')) {
                            $method = $class->getMethod('__call');
                            $args   = [$callback[1], $container];
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf('No such Method %s::%s', $callback[0], $callback[1])
                            );
                        }
                    } else {
                        $method = $class->getMethod($callback[1]);
                        $args   = [$container];
                    }
                    $object = null;

                    if (!$method->isStatic()) {
                        $object = $this->getInstanceOf($callback[0]);
                    }

                    $method->invokeArgs($object, $args);
                } else {
                    call_user_func($callback, $container);
                }
            }
        }
    }

    /**
     * Create closure to autoload the class and return the instance.
     *
     * @param string $className The class name to load.
     *
     * @return \Closure
     *
     * @internal This will become protected or private when PHP 5.3 support get's dropped.
     */
    public function getSingleton($className)
    {
        $initializer = $this;
        return function () use ($initializer, $className) {
            if (!class_exists($className)) {
                throw new \RuntimeException('Could not load class ' . $className);
            }

            $object = $initializer->getInstanceOf($className);

            return $object;
        };
    }

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return \Closure
     */
    // @codingStandardsIgnoreStart - Ignore false positive for thrown exception.
    protected function getDatabaseProvider()
    {
        return function ($container) {
            /** @var \Config $config */
            $config = $container['config'];

            // Ensure the user is loaded before the database class.
            if (empty($container['user'])) {
                throw new \RuntimeException('User has not been preloaded.');
            }

            // Work around the fact that \Contao\Database::getInstance() always creates an instance,
            // even when no driver is configured.
            if (!$config->get('dbDriver')) {
                throw new \RuntimeException('Contao Database is not properly configured.');
            }

            return Database::getInstance();
        };
    }
    // @codingStandardsIgnoreEnd

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return \Closure
     *
     * @throws \RuntimeException When an unknown TL_MODE is encountered.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariables)
     */
    protected function getUserProvider()
    {
        return function ($container) {
            if (!defined('TL_MODE')) {
                throw new \RuntimeException(
                    'TL_MODE not defined.',
                    1
                );
            }

            /** @var Config $config */
            $config = $container['config'];
            // Work around the fact that \Contao\Database::getInstance() always creates an instance,
            // even when no driver is configured (Database and Config are being imported into the user class and there-
            // fore causing an fatal error).
            if (!$config->get('dbDriver')) {
                throw new \RuntimeException('Contao Database is not properly configured.');
            }

            if ((TL_MODE == 'BE') || (TL_MODE == 'CLI')) {
                return call_user_func($this->getSingleton('\\Contao\\BackendUser'));
            } elseif (TL_MODE == 'FE') {
                return call_user_func($this->getSingleton('\\Contao\\FrontendUser'));
            }

            throw new \RuntimeException(
                'Unknown TL_MODE encountered "' . var_export(constant('TL_MODE'), true) . '"',
                1
            );
        };
    }

    /**
     * Add the Contao Config Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function provideConfig(PimpleGate $container)
    {
        if (!isset($container['config'])) {
            $container['config'] = $container->share($this->getSingleton('\\Contao\\Config'));
        }
    }

    /**
     * Add the Contao Environment Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     */
    private function provideEnvironment(PimpleGate $container)
    {
        if (!isset($container['environment'])) {
            $container['environment'] = $container->share($this->getSingleton('\\Contao\\Environment'));
        }
    }

    /**
     * Add the Contao Database Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     */
    private function provideDatabase(PimpleGate $container)
    {
        if (!isset($container['database.connection'])) {
            $container['database.connection'] = $container->share($this->getDatabaseProvider());
        }
    }

    /**
     * Add the Contao Input Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     */
    private function provideInput(PimpleGate $container)
    {
        if (!isset($container['input'])) {
            $container['input'] = $container->share($this->getSingleton('\\Contao\\Input'));
        }
    }

    /**
     * Add the Contao User Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     */
    private function provideUser(PimpleGate $container)
    {
        if (!isset($container['user'])) {
            $container['user'] = $container->share($this->getUserProvider());
        }
    }

    /**
     * Add the Contao Session Singleton to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function provideSession(PimpleGate $container)
    {
        if (!isset($container['session'])) {
            $container['session'] = $container->share($this->getSingleton('\\Contao\\Session'));
        }

        if (!isset($container['page-provider'])) {
            $container['page-provider'] = new PageProvider();

            if (isset($GLOBALS['TL_HOOKS']['getPageLayout']) && is_array($GLOBALS['TL_HOOKS']['getPageLayout'])) {
                array_unshift(
                    $GLOBALS['TL_HOOKS']['getPageLayout'],
                    ['DependencyInjection\Container\PageProvider', 'setPage']
                );
            } else {
                $GLOBALS['TL_HOOKS']['getPageLayout'] = [['DependencyInjection\Container\PageProvider', 'setPage']];
            }
        }
    }

    /**
     * Add the Contao singletons to the DIC.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     */
    protected function provideSingletons(PimpleGate $container)
    {
        $this->provideConfig($container);
        $this->provideEnvironment($container);
        $this->provideDatabase($container);
        $this->provideInput($container);
        $this->provideUser($container);
        $this->provideSession($container);
    }

    /**
     * Load all services files.
     *
     * @param PimpleGate $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function loadServiceConfigurations(PimpleGate $container)
    {
        $paths = $container->isContao4()
            ? $container->getSymfonyParameter('contao_community_alliance.legacy_dic')
            : $this->getActiveModulePaths();

        // include the module services configurations
        foreach ($paths as $file) {
            include $file;
        }
    }

    /**
     * Return the active modules as array.
     *
     * @return string[] An array of active modules
     */
    protected function getActiveModulePaths()
    {
        $paths = array_map(function ($module) {
            return TL_ROOT . '/system/modules/' . $module . '/config/services.php';
        }, ModuleLoader::getActive());
        // include the local services configuration
        $paths[] = TL_ROOT . '/system/config/services.php';

        return array_filter($paths, function ($path) {
            return is_readable($path);
        });
    }

    /**
     * Init the global dependency container.
     *
     * @return void
     */
    public function init()
    {
        // Retrieve the default service container.
        $container = $this->getContainer();

        // Provide the Contao singletons first.
        $this->provideSingletons($container);

        // Now load the additional service configurations.
        $this->loadServiceConfigurations($container);

        // Finally call the HOOKs to allow additional handling.
        $this->callHooks($container);
    }
}
