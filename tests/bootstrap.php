<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2016 Contao Community Alliance
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @copyright  2013-2016 Contao Community Alliance
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0+
 * @link       http://c-c-a.org
 * @filesource
 */

error_reporting(E_ALL);

function includeIfExists($file)
{
    return file_exists($file) ? include $file : false;
}

if (
    // Locally installed dependencies
    (!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php'))
    // We are within an composer install.
    && (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL;
    exit(1);
}

// This is the hack to mimic the Contao auto loader.
spl_autoload_register(
    function ($class) {
        if (substr($class, 0, 7) === 'Contao\\') {
            return null;
        }
        $result = class_exists('Contao\\' . $class);

        if ($result) {
            class_alias('Contao\\' . $class, $class);
        }

        return $result;
    }
);

$GLOBALS['TL_HOOKS'] = array(
    'getPageLayout' => array(
        array('Another', 'hook')
    ),
);
