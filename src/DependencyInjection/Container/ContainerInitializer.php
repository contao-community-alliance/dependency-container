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
     * Init the global dependency container.
     *
     * @return void
     */
    public function init()
    {
        if (!isset($GLOBALS['container'])) {
            $GLOBALS['container'] = new \Pimple();
        }
        $container = $GLOBALS['container'];

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
}
