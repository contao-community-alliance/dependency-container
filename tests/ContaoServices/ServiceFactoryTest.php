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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test\ContaoServices;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use DependencyInjection\Container\ContaoServices\ServiceFactory;
use DependencyInjection\Container\PageProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use function array_values;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ServiceFactoryTest extends TestCase
{
    /**
     * Provider method for plain singletons.
     */
    public function plainSingletonProvider(): array
    {
        return [
            'Config' => [
                'singleton' => 'Contao\Config',
                'method'    => 'createConfigService',
            ],
            'Environment' => [
                'singleton' => 'Contao\Environment',
                'method'    => 'createEnvironmentService',
            ],
            'Input' => [
                'singleton' => 'Contao\Input',
                'method'    => 'createInputService',
            ],
            'Session' => [
                'singleton' => 'Contao\Session',
                'method'    => 'createSessionService',
            ],
        ];
    }

    /**
     * Test the createConfigService() method
     *
     * @param string $singleton The singleton class to create.
     * @param string $method    The factory method to call.
     *
     * @dataProvider plainSingletonProvider()
     */
    public function testPlainSingletonServiceCreator(string $singleton, string $method): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->expects($this->once())
            ->method('get')
            ->with('contao.framework')
            ->willReturn($framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class));

        $instance = new stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with($singleton)
            ->willReturn($instance);

        $this->assertSame($instance, $factory->$method());
    }

    /**
     * Test the createUserService method in Backend mode.
     */
    public function testCreateUserServiceWithoutDbParameter(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.framework'],
                ['request_stack']
            )
            ->willReturnOnConsecutiveCalls(
                $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock(),
                $this->getMockForAbstractClass(ContaoFrameworkInterface::class),
                $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock()
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(false);

        $config
            ->expects($this->never())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contao Database is not properly configured');
        $factory->createUserService();
    }

    /**
     * Test the createUserService method in Backend mode.
     */
    public function testCreateUserServiceWithoutDb(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.framework'],
                ['request_stack']
            )
            ->willReturnOnConsecutiveCalls(
                $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock(),
                $this->getMockForAbstractClass(ContaoFrameworkInterface::class),
                $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock()
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(true);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contao Database is not properly configured');
        $factory->createUserService();
    }

    /**
     * Test the createUserService method in Backend mode.
     */
    public function testCreateUserServiceForBackend(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $scopeMatcher = $this->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBackendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock();
        $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class);

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.routing.scope_matcher'],
                ['request_stack'],
                ['contao.framework']
            )
            ->willReturnOnConsecutiveCalls(
                $config,
                $scopeMatcher,
                $requestStack,
                $framework
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(true);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn('databaseName');

        $request = new Request();

        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(true);

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $instance = new stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\BackendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in Frontend mode.
     */
    public function testCreateUserServiceForFrontend(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock();
        $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class);

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.routing.scope_matcher'],
                ['request_stack'],
                ['contao.framework']
            )
            ->willReturnOnConsecutiveCalls(
                $config,
                $scopeMatcher,
                $requestStack,
                $framework
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(true);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn('databaseName');

        $request = new Request();

        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(false);

        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn(true);

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $instance = new stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\FrontendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in CLI mode.
     */
    public function testCreateUserServiceForCliWithoutRequest(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock();
        $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class);

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.routing.scope_matcher'],
                ['request_stack'],
                ['contao.framework']
            )
            ->willReturnOnConsecutiveCalls(
                $config,
                $scopeMatcher,
                $requestStack,
                $framework
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(true);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn('databaseName');

        $scopeMatcher
            ->expects($this->never())
            ->method('isBackendRequest')
            ->willReturn(false);

        $scopeMatcher
            ->expects($this->never())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $instance = new stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\BackendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in Frontend mode.
     */
    public function testCreateUserServiceForUnkownRequest(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder(stdClass::class)->addMethods(['getCurrentRequest'])->getMock();
        $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class);

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_config'],
                ['contao.routing.scope_matcher'],
                ['request_stack'],
                ['contao.framework']
            )
            ->willReturnOnConsecutiveCalls(
                $config,
                $scopeMatcher,
                $requestStack,
                $framework
            );
        $container
            ->method('hasParameter')
            ->withConsecutive(['database_host'])
            ->willReturnOnConsecutiveCalls(true);

        $config
            ->expects($this->once())
            ->method('get')
            ->with('dbDatabase')
            ->willReturn('databaseName');

        $request = new Request();

        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(false);

        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn(false);

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $instance = new stdClass();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown TL_MODE encountered');

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createDatabaseConnectionService method in Backend mode.
     */
    public function testCreateDatabaseConnectionService(): void
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $user = $this->getMockBuilder(stdClass::class)->getMock();
        $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class);

        $container
            ->method('get')
            ->withConsecutive(
                ['cca.legacy_dic.contao_user'],
                ['contao.framework']
            )
            ->willReturnOnConsecutiveCalls(
                $user,
                $framework
            );

        $instance = new stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\Database')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createDatabaseConnectionService());
    }

    /**
     * Test the createPageProviderService() method.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testCreatePageProviderService(): void
    {
        $factory = new ServiceFactory(
            $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $this->assertInstanceOf(PageProvider::class, $factory->createPageProviderService());

        $this->assertEquals(
            0,
            array_search([PageProvider::class, 'setPage'], array_values($GLOBALS['TL_HOOKS']['getPageLayout'])),
            'PageProvider::setPage() is not the first hook in TL_HOOKS::getPageLayout!'
        );
    }

    /**
     * Test the createPageProviderService() method.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testCreatePageProviderServiceWithPreExistingHook(): void
    {
        $GLOBALS['TL_HOOKS'] = [
            'getPageLayout' => [
                ['Another', 'hook']
            ],
        ];

        $factory = new ServiceFactory(
            $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $this->assertInstanceOf(PageProvider::class, $factory->createPageProviderService());

        $this->assertEquals(
            0,
            array_search([PageProvider::class, 'setPage'], array_values($GLOBALS['TL_HOOKS']['getPageLayout'])),
            'PageProvider::setPage() is not the first hook in TL_HOOKS::getPageLayout!'
        );
    }
}
