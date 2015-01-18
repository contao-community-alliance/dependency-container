Dependency Injection Container for Contao Open Source CMS
===============================================

This DI Container based on [Pimple](http://pimple.sensiolabs.org).

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
