<?php

/**
 * Dependency Container for Contao Open Source CMS
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  (c) 2013 Contao Community Alliance
 * @author     Tristan Lins <tristan.lins@bit3.de>
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
     * Init the global dependency container.
     *
     * @return void
     */
    public function init()
    {
        $container = $this->getContainer();

        $config = \Config::getInstance();

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

        $this->callHooks($container);
    }
}
