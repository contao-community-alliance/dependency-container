<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2017 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2017 Contao Community Alliance <https://c-c-a.org>
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
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

/**
 * The class provides services for create.
 */
class Services
{
    /**
     * The contao framework.
     *
     * @var ResettableContainerInterface
     */
    protected $container;

    /**
     * Create a new instance.
     *
     * @param ResettableContainerInterface $container The container instance.
     */
    public function __construct(ResettableContainerInterface $container)
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
        return Config::getInstance();
    }

    /**
     * Create the environment service for contao environment.
     *
     * @return Environment
     */
    public function createEnvironmentService()
    {
        return Environment::getInstance();
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
        if (!defined('TL_MODE')) {
            throw new \RuntimeException(
                'TL_MODE not defined.',
                1
            );
        }

        $config = $this->container->get('cca.legacy_dic.contao_config');
        // Work around the fact that \Contao\Database::getInstance() always creates an instance,
        // even when no driver is configured (Database and Config are being imported into the user class and there-
        // fore causing an fatal error).
        if (!$config->get('dbDatabase')) {
            throw new \RuntimeException('Contao Database is not properly configured.');
        }

        if (('BE' === TL_MODE) || ('CLI' === TL_MODE)) {
            return BackendUser::getInstance();
        }

        if ('FE' === TL_MODE) {
            return FrontendUser::getInstance();
        }

        throw new \RuntimeException(
            'Unknown TL_MODE encountered "' . var_export(constant('TL_MODE'), true) . '"',
            1
        );
    }

    /**
     * Create the database connection service for contao database.
     *
     * @return Database
     *
     * @throws \RuntimeException Throws an exception if user not been preloaded.
     * @throws \RuntimeException Throws an exception if database not configured.
     */
    public function createDatabaseConnectionService()
    {
        // Ensure the user is loaded before the database class.
        if (empty($this->container->get('cca.legacy_dic.contao_user'))) {
            throw new \RuntimeException('User has not been preloaded.');
        }

        $config = $this->container->get('cca.legacy_dic.contao_config');

        // Work around the fact that \Contao\Database::getInstance() always creates an instance,
        // even when no driver is configured.
        if (!$config->get('dbDatabase')) {
            throw new \RuntimeException('Contao Database is not properly configured.');
        }

        return Database::getInstance();
    }

    /**
     * Create the input service for contao input.
     *
     * @return Input
     */
    public function createInputService()
    {
        return Input::getInstance();
    }

    /**
     * Create the session service for contao session.
     *
     * @return Session
     */
    public function createSessionService()
    {
        return Session::getInstance();
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
