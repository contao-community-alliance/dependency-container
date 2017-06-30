Dependency Injection Container for Contao Open Source CMS
===============================================

This DI Container based on [Pimple](http://pimple.sensiolabs.org).


***NOTE on Contao 4:*** This is obsolete in Contao 4 - you should use the symfony container in Contao 4.

This extension keeps compatibility for easing migration to Contao 4 - however, you should
change your code to register your services using both registration ways.

For a howto of how to migrate to Contao 4, please refer to the [UPGRADING-TO-CONTAO4.md](UPGRADING-TO-CONTAO4.md)

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

Build-in services
-----------------

### The config object

```php
/** @var \Config $config */
$config = $container['config'];
```

### The environment object

```php
/** @var \Environment $environment */
$environment = $container['environment'];
```

### The database connection

```php
/** @var \Database $database */
$database = $container['database.connection'];
```

### The input object

```php
/** @var \Input $input */
$input = $container['input'];
```

### The backend or frontend user, depend on TL_MODE

```php
/** @var \BackendUser|\FrontendUser $user */
$user = $container['user'];
```

### The session object

```php
/** @var \Session $session */
$session = $container['session'];
```

### Lazy access to the $objPage object

```php
/** @var DependencyInjection\Container\PageProvider */
$pageProvider = $container['page-provider'];
$page         = $pageProvider->getPage();
```
