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

/**
 * Hooks
 */
if (version_compare(VERSION, '3.1', '>=')) {
	$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('DependencyInjection\ContainerInitializer', 'init');
}
else {
	$GLOBALS['TL_HOOKS']['loadLanguageFile']['dependency-container'] = array('DependencyInjection\ContainerInitializer', 'init');
}

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['system']['services'] = array(
	'callback' => 'DependencyInjection\ServicesBackend',
	'icon'     => 'system/modules/dependency-container/assets/images/services.png',
);
