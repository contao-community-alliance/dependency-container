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
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use DependencyInjection\Container\LegacyDependencyInjectionContainer;
use PHPUnit\Framework\TestCase;

/**
 * Test the class LegacyDependencyInjectionContainer.
 */
class LegacyDependencyInjectionContainerTest extends TestCase
{
    /**
     * Test that the service is retrieved from the legacy container.
     *
     * @return void
     */
    public function testGetService()
    {
        if (!interface_exists('Contao\CoreBundle\Framework\ContaoFrameworkInterface')) {
            $this->markTestSkipped('Only available in Contao 4');
        }

        $framework = $this->getMockForAbstractClass('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('initialize');

        $legacyContainer = new LegacyDependencyInjectionContainer($framework);

        $GLOBALS['container'] = ['test-service' => 'test'];

        $this->assertSame('test', $legacyContainer->getService('test-service'));
    }
}
