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
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\ContaoServices;

use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Session;
use DependencyInjection\Container\PageProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class provides services for create.
 */
class ServiceFactory
{
    /**
     * The contao framework.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Create a new instance.
     *
     * @param ContainerInterface $container The container instance.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create the config service for contao config.
     *
     * @return Config
     */
    public function createConfigService()
    {
        return $this->container->get('contao.framework')->createInstance(Config::class);
    }

    /**
     * Create the environment service for contao environment.
     *
     * @return Environment
     */
    public function createEnvironmentService()
    {
        return $this->container->get('contao.framework')->createInstance(Environment::class);
    }

    /**
     * Create the user service for contao user.
     *
     * @return BackendUser|FrontendUser
     *
     * @throws \RuntimeException Throw an exception if contao mode not defined.
     */
    public function createUserService()
    {
        $config = $this->container->get('cca.legacy_dic.contao_config');
        // Work around the fact that \Contao\Database::getInstance() always creates an instance,
        // even when no driver is configured (Database and Config are being imported into the user class and there-
        // fore causing an fatal error).
        if (!$this->container->hasParameter('database_host') || !$config->get('dbDatabase')) {
            throw new \RuntimeException('Contao Database is not properly configured.');
        }

        $matcher = $this->container->get('contao.routing.scope_matcher');
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // NULL request => CLI mode.
        if ((null === $request) || $matcher->isBackendRequest($request)) {
            return $this->container->get('contao.framework')->createInstance(BackendUser::class);
        }

        if ($matcher->isFrontendRequest($request)) {
            return $this->container->get('contao.framework')->createInstance(FrontendUser::class);
        }

        throw new \RuntimeException('Unknown TL_MODE encountered', 1);
    }

    /**
     * Create the database connection service for contao database.
     *
     * @return Database
     */
    public function createDatabaseConnectionService()
    {
        // Ensure the user is loaded before the database class.
        $this->container->get('cca.legacy_dic.contao_user');

        return $this->container->get('contao.framework')->createInstance(Database::class);
    }

    /**
     * Create the input service for contao input.
     *
     * @return Input
     */
    public function createInputService()
    {
        return $this->container->get('contao.framework')->createInstance(Input::class);
    }

    /**
     * Create the session service for contao session.
     *
     * @return Session
     */
    public function createSessionService()
    {
        return $this->container->get('contao.framework')->createInstance(Session::class);
    }

    /**
     * Create the page provider service for provide the current active page model.
     *
     * @return PageProvider
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function createPageProviderService()
    {
        $pageProvider = new PageProvider();

        if (isset($GLOBALS['TL_HOOKS']['getPageLayout']) && is_array($GLOBALS['TL_HOOKS']['getPageLayout'])) {
            array_unshift(
                $GLOBALS['TL_HOOKS']['getPageLayout'],
                [PageProvider::class, 'setPage']
            );
        } else {
            $GLOBALS['TL_HOOKS']['getPageLayout'] = [[PageProvider::class, 'setPage']];
        }

        return $pageProvider;
    }
}
