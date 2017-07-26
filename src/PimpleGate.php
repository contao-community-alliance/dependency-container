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
 * @copyright  2013-2016 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class is a simple gateway to the symfony dependency container.
 */
class PimpleGate extends \Pimple
{
    /**
     * The delegating container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Delegate service lookup map.
     *
     * @var string[]
     */
    private $delegates = [];

    /**
     * Instantiate the container.
     *
     * It want's the real symfony DIC as argument.
     *
     * @param array              $services  The initial values.
     * @param ContainerInterface $container The container in use.
     */
    public function __construct($services = [], ContainerInterface $container = null)
    {
        parent::__construct($services);

        $this->container         = $container;
        $this->values['symfony'] = $container;
    }

    /**
     * Retrieve the symfony DIC.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Provide a symfony service in this container.
     *
     * @param string      $name        The name of the service.
     * @param null|string $symfonyName The name of the service in the symfony container (if not equal).
     *
     * @return void
     *
     * @throws \LogicException When the service being set is already contained in the DIC.
     */
    public function provideSymfonyService($name, $symfonyName = null)
    {
        if (null === $symfonyName) {
            $symfonyName = $name;
        }

        if (parent::offsetExists($name)) {
            throw new \LogicException(sprintf('Service %s has already been defined.', $name));
        }

        $this->delegates[$name] = $symfonyName;
    }

    /**
     * Retrieve a symfony service.
     *
     * @param string $symfonyName The name of the service in the symfony container.
     *
     * @return mixed
     */
    public function getSymfonyService($symfonyName)
    {
        return $this->container->get($symfonyName);
    }

    /**
     * Retrieve a symfony parameter.
     *
     * @param string $symfonyName The name of the service in the symfony container.
     *
     * @return mixed
     */
    public function getSymfonyParameter($symfonyName)
    {
        return $this->container->getParameter($symfonyName);
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same a name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object.
     *
     * @param mixed  $value The value of the parameter or a closure to defined an object.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ShortVariableName)
     *
     * @throws \LogicException When the service being set is already delegated to the symfony DIC.
     */
    public function offsetSet($id, $value)
    {
        if (isset($this->delegates[$id])) {
            throw new \LogicException(sprintf('Service %s has been delegated to symfony, cannot set.', $id));
        }
        // @codingStandardsIgnoreStart
        @trigger_error(
            'get service: The pimple based DIC is deprecated, use the symfony DIC in new projects.',
            E_USER_DEPRECATED
        );
        // @codingStandardsIgnoreEnd

        $this->values[$id] = $value;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object.
     *
     * @return mixed The value of the parameter or an object.
     *
     * @throws \InvalidArgumentException If the identifier is not defined.
     *
     * @SuppressWarnings(PHPMD.ShortVariableName)
     */
    public function offsetGet($id)
    {
        if (isset($this->delegates[$id])) {
            return $this->getSymfonyService($this->delegates[$id]);
        }

        // @codingStandardsIgnoreStart
        @trigger_error(
            'get service: The pimple based DIC is deprecated, use the symfony DIC in new projects.',
            E_USER_DEPRECATED
        );
        // @codingStandardsIgnoreEnd

        return parent::offsetGet($id);
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $id The unique identifier for the parameter or object.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.ShortVariableName)
     */
    public function offsetExists($id)
    {
        if (isset($this->delegates[$id])) {
            return true;
        }
        // @codingStandardsIgnoreStart
        @trigger_error(
            'isset service: The pimple based DIC is deprecated, use the symfony DIC in new projects.',
            E_USER_DEPRECATED
        );
        // @codingStandardsIgnoreEnd

        return parent::offsetExists($id);
    }

    /**
     * Unset a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object.
     *
     * @return void
     *
     * @throws \LogicException When the service being unset is delegated to the symfony DIC.
     *
     * @SuppressWarnings(PHPMD.ShortVariableName)
     */
    public function offsetUnset($id)
    {
        if (isset($this->delegates[$id])) {
            throw new \LogicException(sprintf('Service %s has been delegated to symfony, cannot unset.', $id));
        }
        // @codingStandardsIgnoreStart
        @trigger_error(
            'unset service: The pimple based DIC is deprecated, use the symfony DIC in new projects.',
            E_USER_DEPRECATED
        );
        // @codingStandardsIgnoreEnd

        parent::offsetUnset($id);
    }
}
