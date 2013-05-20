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

namespace DependencyInjection;

/**
 * Class ContainerInitializer
 */
class ContainerInitializer
{
	/**
	 * Init the global dependency container.
	 */
	public function init()
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
