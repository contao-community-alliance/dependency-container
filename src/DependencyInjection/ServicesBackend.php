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

class ServicesBackend extends \TwigBackendModule
{
	protected $strTemplate = 'be_services';

	/**
	 * Compile the current element
	 */
	protected function compile()
	{
		$this->loadLanguageFile('dependency-container');

		/** @var \DependencyInjection\Container $container */
		global $container;

		$services = array();

		$keys = $container->keys();

		foreach ($keys as $key) {
			$parts = explode('.', $key);
			$group = $parts[0];

			try {
				$value = $this->describeValue($container[$key], true);
			}
			catch (\Exception $e) {
				$value = sprintf(
					'<span style="color:red">[%s] %s</span>',
					get_class($e),
					$e->getMessage()
				);
			}

			$services[$group][$key] = $value;
			ksort($services[$group]);
		}

		$this->Template->lang     = $GLOBALS['TL_LANG']['dependency-container'];
		$this->Template->services = $services;
	}

	protected function describeValue($value, $recursive = false)
	{
		if (is_callable($value)) {
			$function = new \ReflectionFunction($value);

			$name = $function->getName();
			if ($name == '{closure}') {
				$name = 'function';
			}
			else {
				$name = 'function ' . $name;
			}

			$parameters = array();
			foreach ($function->getParameters() as $parameter) {
				$synopsis = '';
				if ($parameter->isArray()) {
					$synopsis .= 'array&nbsp;';
				}
				else if ($parameter->getClass()) {
					$synopsis .= $parameter->getClass() . '&nbsp;';
				}
				if ($parameter->isPassedByReference()) {
					$synopsis .= '&';
				}
				$synopsis .= '$' . $parameter->getName();
				if ($parameter->isOptional()) {
					$synopsis .= '&nbsp;=&nbsp;' . var_export($parameter->getDefaultValue(), true);
				}
				$parameters[] = $synopsis;
			}

			return sprintf(
				'(callable) %s(<br> &nbsp; &nbsp;%s<br>)',
				$name,
				implode(',<br> &nbsp; &nbsp;', $parameters)
			);
		}
		else if (is_object($value)) {
			if ($value instanceof \ArrayObject) {
				if ($recursive) {
					$values = array_map(
						array($this, 'describeValue'),
						$value->getArrayCopy()
					);
					return sprintf(
						'(object) %s(<br> &nbsp; &nbsp;%s<br>)',
						get_class($value),
						implode(',<br> &nbsp; &nbsp;', $values)
					);
				}
				else {
					return sprintf(
						'(object) %s(&hellip;)',
						get_class($value)
					);
				}
			}
			else {
				return sprintf(
					'(object) %s',
					get_class($value)
				);
			}
		}
		else if (is_array($value)) {
			if ($recursive) {
				$values = array_map(
					array($this, 'describeValue'),
					$value
				);
				return sprintf(
					'(array) [%s]',
					implode(', ', $values)
				);
			}
			else {
				return sprintf(
					'(array) [&hellip;]'
				);
			}
		}
		else {
			return sprintf(
				'(%s) %s',
				gettype($value),
				var_export($value, true)
			);
		}
	}
}
