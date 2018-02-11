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
 * @author     Tristan Lins <tristan@lins.io>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container;

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
     * @throws \RuntimeException When the container can not be obtained.
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

        // 3. Nothing worked out, throw Exception as this may never happen.
        throw new \RuntimeException('Could not obtain symfony container.');
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
        $paths = $container->getSymfonyParameter('cca.legacy_dic');

        // include the module services configurations
        foreach ($paths as $file) {
            require_once $file;
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

        // Now load the additional service configurations.
        $this->loadServiceConfigurations($container);

        // Finally call the HOOKs to allow additional handling.
        $this->callHooks($container);
    }
}
