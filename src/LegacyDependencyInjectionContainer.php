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

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

/**
 * This class provides a gateway from the legacy dependency container to the symfony dependency container.
 */
class LegacyDependencyInjectionContainer
{
    /**
     * The Contao framework.
     *
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Create a new instance.
     *
     * @param ContaoFrameworkInterface $framework The Contao framework.
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Retrieve a service from pimple.
     *
     * @param string $serviceName The name of the service to retrieve.
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getService($serviceName)
    {
        $this->framework->initialize();

        return $GLOBALS['container'][$serviceName];
    }
}
