<?php

/**
 * This file is part of contao-community-alliance/dependency-container.
 *
 * (c) 2013-2016 Contao Community Alliance <https://c-c-a.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/dependency-container
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @copyright  2013-2016 Contao Community Alliance <https://c-c-a.org>
 * @license    https://github.com/contao-community-alliance/dependency-container/blob/master/LICENSE LGPL-3.0
 * @link       https://github.com/contao-community-alliance/dependency-container
 * @filesource
 */

namespace DependencyInjection\Container\Test\Mocks\Contao;

/**
 * This mocks the config class.
 */
class Config
{
    /**
     * @var array
     */
    public static $values;

    /**
     * @var Config
     */
    public static $instance;

    /**
     * Create a new instance.
     *
     * @param array $values The values.
     */
    public function __construct($values = [])
    {
        self::$values   = $values;
        self::$instance = $this;
    }

    /**
     * Retrieve the instance.
     *
     * @return Config
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public static function get($key)
    {
        if (isset(self::$values[$key]))
        {
            return self::$values[$key];
        }

        return null;
    }
}
