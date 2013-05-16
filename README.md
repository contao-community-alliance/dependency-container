Dependency Container for Contao Open Source CMS
===============================================

This DI Container based on the [Symfony2 Dependency Injection Component](http://symfony.com/doc/2.1/components/dependency_injection/index.html).

Register parameters and services
--------------------------------

system/modules/X/config/services.php
```php
$container['myservice.param'] = 'value';
$container['myservice'] = function($container) {
	return new MyServiceClassName();
}
```

Access parameters and services
------------------------------

```php
class MyClass
{
	function myFunction()
	{
		global $container;

		$parameter = $container['myservice.param'];
		$service = $container['myservice'];
	}
}
```
