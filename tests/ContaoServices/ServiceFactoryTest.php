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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ServiceFactoryTest extends TestCase
{
    /**
     * Provider method for plain singletons.
     *
     * @return array
     */
    public function plainSingletonProvider()
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
     * @return void
     * @dataProvider plainSingletonProvider()
     */
    public function testPlainSingletonServiceCreator($singleton, $method)
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );

        $container
            ->expects($this->once())
            ->method('get')
            ->with('contao.framework')
            ->willReturn($framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class));

        $instance = new \stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with($singleton)
            ->willReturn($instance);

        $this->assertSame($instance, $factory->$method());
    }

    /**
     * Test the createUserService method in Backend mode.
     *
     * @return void
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Contao Database is not properly configured
     */
    public function testCreateUserServiceWithoutDbParameter()
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
                $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock(),
                $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class),
                $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock()
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

        $factory->createUserService();
    }

    /**
     * Test the createUserService method in Backend mode.
     *
     * @return void
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Contao Database is not properly configured
     */
    public function testCreateUserServiceWithoutDb()
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
                $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock(),
                $framework = $this->getMockForAbstractClass(ContaoFrameworkInterface::class),
                $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock()
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

        $factory->createUserService();
    }

    /**
     * Test the createUserService method in Backend mode.
     *
     * @return void
     */
    public function testCreateUserServiceForBackend()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock();
        $scopeMatcher = $this->getMockBuilder(ScopeMatcher::class)->disableOriginalConstructor()->setMethods(['isBackendRequest'])->getMock();
        $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock();
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

        $instance = new \stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\BackendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in Frontend mode.
     *
     * @return void
     */
    public function testCreateUserServiceForFrontend()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock();
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

        $instance = new \stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\FrontendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in CLI mode.
     *
     * @return void
     */
    public function testCreateUserServiceForCliWithoutRequest()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock();
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

        $instance = new \stdClass();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with('Contao\BackendUser')
            ->willReturn($instance);

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createUserService method in Frontend mode.
     *
     * @return void
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown TL_MODE encountered
     */
    public function testCreateUserServiceForUnkownRequest()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $config = $this->getMockBuilder('stdClass')->setMethods(['get'])->getMock();
        $scopeMatcher = $this
            ->getMockBuilder(ScopeMatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['isBackendRequest', 'isFrontendRequest'])
            ->getMock();
        $requestStack = $this->getMockBuilder('stdClass')->setMethods(['getCurrentRequest'])->getMock();
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

        $instance = new \stdClass();

        $this->assertSame($instance, $factory->createUserService());
    }

    /**
     * Test the createDatabaseConnectionService method in Backend mode.
     *
     * @return void
     */
    public function testCreateDatabaseConnectionService()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $user = $this->getMockBuilder('stdClass')->getMock();
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

        $instance = new \stdClass();

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
     * @return void
     */
    public function testCreatePageProviderService()
    {
        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $this->assertInstanceOf(PageProvider::class, $factory->createPageProviderService());

        $this->assertEquals(
            0,
            array_search([PageProvider::class, 'setPage'], $GLOBALS['TL_HOOKS']['getPageLayout']),
            'PageProvider::setPage() is not the first hook in TL_HOOKS::getPageLayout!'
        );
    }

    /**
     * Test the createPageProviderService() method.
     *
     * @return void
     */
    public function testCreatePageProviderServiceWithPreExistingHook()
    {
        $GLOBALS['TL_HOOKS'] = array(
            'getPageLayout' => array(
                array('Another', 'hook')
            ),
        );

        $factory = new ServiceFactory(
            $container = $this->getMockForAbstractClass(ContainerInterface::class)
        );
        $this->assertInstanceOf(PageProvider::class, $factory->createPageProviderService());

        $this->assertEquals(
            0,
            array_search([PageProvider::class, 'setPage'], $GLOBALS['TL_HOOKS']['getPageLayout']),
            'PageProvider::setPage() is not the first hook in TL_HOOKS::getPageLayout!'
        );
    }
}
