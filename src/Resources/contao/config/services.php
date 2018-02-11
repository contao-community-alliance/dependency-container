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
 * @copyright  2013-2018 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

use DependencyInjection\Container\PimpleGate;

// Contao 4 code.
/** @var PimpleGate $container */
$container->provideSymfonyService('config', 'cca.legacy_dic.contao_config');
$container->provideSymfonyService('environment', 'cca.legacy_dic.contao_environment');
$container->provideSymfonyService('user', 'cca.legacy_dic.contao_user');
$container->provideSymfonyService('database.connection', 'cca.legacy_dic.contao_database_connection');
$container->provideSymfonyService('input', 'cca.legacy_dic.contao_input');
$container->provideSymfonyService('session', 'cca.legacy_dic.contao_session');
$container->provideSymfonyService('page-provider', 'cca.legacy_dic.contao_page_provider');
