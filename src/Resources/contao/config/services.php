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
 * @copyright  2013-2017 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

use DependencyInjection\Container\PimpleGate;

// Contao 4 code.
/** @var PimpleGate $container */
$container->provideSymfonyService('config', 'contao.config');
$container->provideSymfonyService('environment', 'contao.environment');
$container->provideSymfonyService('user', 'contao.user');
$container->provideSymfonyService('database.connection', 'contao.database.connection');
$container->provideSymfonyService('input', 'contao.input');
$container->provideSymfonyService('session', 'contao.session');
$container->provideSymfonyService('page-provider', 'contao.page-provider');
