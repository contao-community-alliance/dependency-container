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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Session;
use DependencyInjection\Container\PageProvider;
use LogicException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The class provides services for create.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ServiceFactory
{
    /**
     * The contao framework.
     */
    protected ContainerInterface $container;

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
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createConfigService()
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->getFramework()->createInstance(Config::class);
    }

    /**
     * Create the environment service for contao environment.
     *
     * @return Environment
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createEnvironmentService()
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->getFramework()->createInstance(Environment::class);
    }

    /**
     * Create the user service for contao user.
     *
     * @return BackendUser|FrontendUser
     *
     * @throws RuntimeException Throw an exception if contao mode not defined.
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createUserService()
    {
        $config = $this->container->get('cca.legacy_dic.contao_config');
        assert($config instanceof Config);
        // Work around the fact that \Contao\Database::getInstance() always creates an instance,
        // even when no driver is configured (Database and Config are being imported into the user class and therefore
        // causing a fatal error).
        if (!$this->container->hasParameter('database_host') || !$config->get('dbDatabase')) {
            throw new RuntimeException('Contao Database is not properly configured.');
        }

        $matcher = $this->container->get('contao.routing.scope_matcher');
        /** @var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');

        $request = $requestStack->getCurrentRequest();
        assert($matcher instanceof ScopeMatcher);

        // NULL request => CLI mode.
        if ((null === $request) || $matcher->isBackendRequest($request)) {
            /** @psalm-suppress LessSpecificReturnStatement */
            return $this->getFramework()->createInstance(BackendUser::class);
        }

        if ($matcher->isFrontendRequest($request)) {
            /** @psalm-suppress LessSpecificReturnStatement */
            return $this->getFramework()->createInstance(FrontendUser::class);
        }

        throw new RuntimeException('Unknown TL_MODE encountered', 1);
    }

    /**
     * Create the database connection service for contao database.
     *
     * @return Database
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createDatabaseConnectionService()
    {
        // Ensure the user is loaded before the database class.
        $this->container->get('cca.legacy_dic.contao_user');

        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->getFramework()->createInstance(Database::class);
    }

    /**
     * Create the input service for contao input.
     *
     * @return Input
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createInputService()
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->getFramework()->createInstance(Input::class);
    }

    /**
     * Create the session service for contao session.
     *
     * @psalm-suppress DeprecatedClass
     * @psalm-suppress MixedMethodCall
     *
     * @return Session
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function createSessionService()
    {
        /** @psalm-suppress LessSpecificReturnStatement */
        return $this->getFramework()->createInstance(Session::class);
    }

    /**
     * Create the page provider service for provide the current active page model.
     *
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedArrayAccess
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function createPageProviderService(): PageProvider
    {
        $pageProvider = new PageProvider();

        if (isset($GLOBALS['TL_HOOKS']['getPageLayout']) && is_array($GLOBALS['TL_HOOKS']['getPageLayout'])) {
            $GLOBALS['TL_HOOKS']['getPageLayout'] = [];
        }
        unset($GLOBALS['TL_HOOKS']['getPageLayout'][PageProvider::class . '::setPage']);
        $GLOBALS['TL_HOOKS']['getPageLayout'][PageProvider::class . '::setPage'] = [PageProvider::class, 'setPage'];

        return $pageProvider;
    }

    /** @psalm-suppress DeprecatedClass */
    private function getFramework(): ContaoFrameworkInterface
    {
        $framework = $this->container->get('contao.framework');

        if (!$framework instanceof ContaoFrameworkInterface) {
            throw new LogicException('Failed to obtain Contao Framework');
        }

        return $framework;
    }
}
