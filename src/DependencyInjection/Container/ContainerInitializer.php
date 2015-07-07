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

namespace DependencyInjection\Container;

/**
 * The initialization handler class for the dependency container.
 */
class ContainerInitializer
{
    /**
     * Get the currently defined global container or create it if no container is present so far.
     *
     * @return \Pimple
     *
     * @throws \RuntimeException When an incompatible DIC is encountered.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function getContainer()
    {
        if (!isset($GLOBALS['container'])) {
            $GLOBALS['container'] = new \Pimple();
        }
        $container = $GLOBALS['container'];

        if (!$container instanceof \Pimple) {
            throw new \RuntimeException(
                'Dependency container is incompatible class. Expected \Pimple but found ' .
                get_class($container),
                1
            );
        }

        return $container;
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
     * @param \Pimple $container The container that got initialized.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the hook method is invalid.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function callHooks($container)
    {
        if (
            isset($GLOBALS['TL_HOOKS']['initializeDependencyContainer']) &&
            is_array($GLOBALS['TL_HOOKS']['initializeDependencyContainer'])
        ) {
            foreach ($GLOBALS['TL_HOOKS']['initializeDependencyContainer'] as $callback) {
                if (is_array($callback)) {
                    $class = new \ReflectionClass($callback[0]);
                    if (!$class->hasMethod($callback[1])) {
                        if ($class->hasMethod('__call')) {
                            $method = $class->getMethod('__call');
                            $args   = array($callback[1], $container);
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf('No such Method %s::%s', $callback[0], $callback[1])
                            );
                        }
                    } else {
                        $method = $class->getMethod($callback[1]);
                        $args   = array($container);
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
     * Autoload the Contao class and alias if needed.
     *
     * @param string $className The class name.
     *
     * @return void
     */
    // @codingStandardsIgnoreStart - Ignore false positive for thrown exception.
    public function ensureClassIsLoaded($className)
    {
        if (!class_exists($className)) {
            $realClassName = 'Contao' . $className;
            if (!class_exists($realClassName)) {
                throw new \RuntimeException('Could not load class ' . $realClassName);
            }
            class_alias($realClassName, $className);
        }
    }
    // @codingStandardsIgnoreEnd

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
            $initializer->ensureClassIsLoaded($className);

            $object = $initializer->getInstanceOf($className);

            return $object;
        };
    }

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return \Closure
     */
    protected function getConfigProvider()
    {
        return $this->getSingleton('\\Config');
    }

    /**
     * Create the closure to provide the Contao Environment.
     *
     * @return \Closure
     */
    protected function getEnvironmentProvider()
    {
        return $this->getSingleton('\\Environment');
    }

    /**
     * Create the closure to provide the Contao Session.
     *
     * @return \Closure
     */
    protected function getSessionProvider()
    {
        return $this->getSingleton('\\Session');
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

            return \Database::getInstance();
        };
    }
    // @codingStandardsIgnoreEnd

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return \Closure
     */
    protected function getInputProvider()
    {
        return $this->getSingleton('\\Input');
    }

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
        $initializer = $this;
        return function ($container) use ($initializer) {
            if (!defined('TL_MODE')) {
                throw new \RuntimeException(
                    'TL_MODE not defined.',
                    1
                );
            }

            /** @var \Config $config */
            $config = $container['config'];
            // Work around the fact that \Contao\Database::getInstance() always creates an instance,
            // even when no driver is configured (Database and Config are being imported into the user class and there-
            // fore causing an fatal error).
            if (!$config->get('dbDriver')) {
                throw new \RuntimeException('Contao Database is not properly configured.');
            }

            if (TL_MODE == 'BE') {
                return call_user_func($initializer->getSingleton('\\BackendUser'));
            } elseif (TL_MODE == 'FE') {
                return call_user_func($initializer->getSingleton('\\FrontendUser'));
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
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function provideConfig(\Pimple $container)
    {
        if (!isset($container['config'])) {
            $container['config'] = $container->share($this->getConfigProvider());
        }
    }

    /**
     * Add the Contao Environment Singleton to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    private function provideEnvironment(\Pimple $container)
    {
        if (!isset($container['environment'])) {
            $container['environment'] = $container->share($this->getEnvironmentProvider());
        }
    }

    /**
     * Add the Contao Database Singleton to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    private function provideDatabase(\Pimple $container)
    {
        if (!isset($container['database.connection'])) {
            $container['database.connection'] = $container->share($this->getDatabaseProvider());
        }
    }

    /**
     * Add the Contao Input Singleton to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    private function provideInput(\Pimple $container)
    {
        if (!isset($container['input'])) {
            $container['input'] = $container->share($this->getInputProvider());
        }
    }

    /**
     * Add the Contao User Singleton to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    private function provideUser(\Pimple $container)
    {
        if (!isset($container['user'])) {
            $container['user'] = $container->share($this->getUserProvider());
        }
    }

    /**
     * Add the Contao Session Singleton to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function provideSession(\Pimple $container)
    {
        if (!isset($container['session'])) {
            $container['session'] = $container->share($this->getSessionProvider());
        }

        if (!isset($container['page-provider'])) {
            $container['page-provider'] = new PageProvider();

            if (isset($GLOBALS['TL_HOOKS']['getPageLayout']) && is_array($GLOBALS['TL_HOOKS']['getPageLayout'])) {
                $GLOBALS['TL_HOOKS']['getPageLayout'] = array_merge(
                    array(array('DependencyInjection\Container\PageProvider', 'setPage')),
                    $GLOBALS['TL_HOOKS']['getPageLayout']
                );
            } else {
                $GLOBALS['TL_HOOKS']['getPageLayout'] = array(
                    array('DependencyInjection\Container\PageProvider', 'setPage')
                );
            }
        }
    }

    /**
     * Add the Contao singletons to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    protected function provideSingletons(\Pimple $container)
    {
        $this->provideConfig($container);
        $this->provideEnvironment($container);
        $this->provideDatabase($container);
        $this->provideInput($container);
        $this->provideUser($container);
        $this->provideSession($container);
    }

    /**
     * Return the active modules as array.
     *
     * @return string[] An array of active modules
     */
    protected function getActiveModules()
    {
        return \ModuleLoader::getActive();
    }

    /**
     * Load all services files.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function loadServiceConfigurations($container)
    {
        // include the module services configurations
        foreach ($this->getActiveModules() as $module) {
            $file = TL_ROOT . '/system/modules/' . $module . '/config/services.php';

            if (file_exists($file)) {
                include $file;
            }
        }

        // include the local services configuration
        $file = TL_ROOT . '/system/config/services.php';

        if (file_exists($file)) {
            include $file;
        }
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
