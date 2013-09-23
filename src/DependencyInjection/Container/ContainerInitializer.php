<?php

/**
 * Dependency Container for Contao Open Source CMS
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota
 * @license    LGPL-3.0+#
 * @filesource
 */

namespace DependencyInjection\Container;

/**
 * Class ContainerInitializer
 */
class ContainerInitializer
{
	/**
	 * Lazy initialize
	 */
	static public function lazyInit()
	{
		spl_autoload_register(
			'DependencyInjection\Container\ContainerInitializer::autoload',
			true,
			true
		);
		if (version_compare(VERSION, '3', '<')) {
			spl_autoload_register('__autoload');
		}
	}

	/**
	 * Initialize
	 * @param $className
	 *
	 * @return bool
	 */
	static public function autoload($className)
	{
		if ($className == 'RequestToken') {
			static::init();
			spl_autoload_unregister('DependencyInjection\Container\ContainerInitializer::autoload');
		}
		return false;
	}

	/**
	 * Init the global dependency container.
	 */
	static public function init()
	{
		global $container;

		if (!isset($container)) {
			$container = new \Pimple();
		}

		$config = \Config::getInstance();

		// include the module services configurations
		foreach ($config->getActiveModules() as $module)
		{
			$file = TL_ROOT . '/system/modules/' . $module . '/config/services.php';

			if (file_exists($file)) {
				include $file;
			}
		}

		// include the local services configuration
		$file = TL_ROOT . '/system/config/services.php';

		if (file_exists($file)) {
			include $file;
		}

		unset($GLOBALS['TL_HOOKS']['loadLanguageFile']['dependency-container']);
	}
}
