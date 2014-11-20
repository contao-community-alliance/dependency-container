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
     * Call the initialization hooks.
     *
     * @param \Pimple $container The container that got initialized.
     *
     * @return void
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
                    $class  = new \ReflectionClass($callback[0]);
                    $method = $class->getMethod($callback[1]);
                    $object = null;

                    if (!$method->isStatic()) {
                        if ($class->hasMethod('getInstance')) {
                            $object = $class->getMethod('getInstance')->invoke(null);
                        } else {
                            $object = $class->newInstance();
                        }
                    }

                    $method->invoke($object, $container);
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
     * @return callable
     */
    protected function getSingleton($className)
    {
        $initializer = $this;
        return function () use ($initializer, $className) {
            $initializer->ensureClassIsLoaded($className);

            $class  = new \ReflectionClass($className);
            $object = null;

            if ($class->hasMethod('getInstance')) {
                $object = $class->getMethod('getInstance')->invoke(null);
            } else {
                $object = $class->newInstance();
            }

            return $object;
        };
    }

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return callable
     */
    protected function getConfigProvider()
    {
        return $this->getSingleton('\\Config');
    }

    /**
     * Create the closure to provide the Contao Environment.
     *
     * @return callable
     */
    protected function getEnvironmentProvider()
    {
        return $this->getSingleton('\\Environment');
    }

    /**
     * Create the closure to provide the Contao Session.
     *
     * @return callable
     */
    protected function getSessionProvider()
    {
        return $this->getSingleton('\\Session');
    }

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return callable
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
     * @return callable
     */
    protected function getInputProvider()
    {
        return $this->getSingleton('\\Input');
    }

    /**
     * Create the closure to provide the Contao Config.
     *
     * @return callable
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
     * Add the Contao singletons to the DIC.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    protected function provideSingletons(\Pimple $container)
    {
        if (!isset($container['config'])) {
            $container['config'] = $container->share($this->getConfigProvider());
        }

        if (!isset($container['environment'])) {
            $container['environment'] = $container->share($this->getEnvironmentProvider());
        }

        if (!isset($container['database.connection'])) {
            $container['database.connection'] = $container->share($this->getDatabaseProvider());
        }

        if (!isset($container['input'])) {
            $container['input'] = $container->share($this->getInputProvider());
        }

        if (!isset($container['user'])) {
            $container['user'] = $container->share($this->getUserProvider());
        }

        if (!isset($container['session'])) {
            $container['session'] = $container->share($this->getSessionProvider());
        }
    }

    /**
     * Load all services files.
     *
     * @param \Pimple $container The DIC to populate.
     *
     * @return void
     */
    protected function loadServiceConfigurations($container)
    {
        /** @var \Contao\Config $config */
        $config = $container['config'];

        // include the module services configurations
        foreach ($config->getActiveModules() as $module) {
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
