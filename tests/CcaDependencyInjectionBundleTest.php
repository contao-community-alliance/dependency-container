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

use DependencyInjection\Container\CcaDependencyInjectionBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Test the class CcaDependencyInjectionBundle.
 */
class CcaDependencyInjectionBundleTest extends TestCase
{
    public function testInstantiation()
    {
        $bundle = new CcaDependencyInjectionBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
}
