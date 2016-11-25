# Upgrading to Contao 4 HOWTO.

This guide will explain you how to upgrade your extensions to Contao 4.

If you want to support Contao 4 only, simply skip this extension at all and
only use the dependency injection container provided by Symfony.

If you happen to have to support Contao 3 and 4 likewise, and until Contao 3.5
is out of LTS you definitely want to support both versions, you will have to do
some work.

## Registering services the Contao 4 way.

In Contao 4 you register services as for any Symfony bundle using configuration.
See the symfony docs for details:
http://symfony.com/doc/current/service_container.html
http://symfony.com/doc/current/components/dependency_injection.html

## Keeping compatibility with Contao 3.

To keep compatibility with Contao 3, you need to register the exact same
services also in the Pimple based container to allow existing extensions
to keep using your service.

*Sidenote:* Initially we thought we could simply register anything in symfony
and delegate all requests for services not in Pimple to there but sadly this
did not work out due to naming collisions (service names in use in either dic).

Let's discuss the migration with an example.
We assume you are registering two services with the following `services.php`
file:
```php
$container['base-service'] = $container->share(
    function ($container) {
        return new \Demo\BaseService();
    }
);
$container['consuming-service'] = $container->share(
    function ($container) {
        return new \Demo\ConsumingService($container['base-service']);
    }
);
```

The resulting `services.yml` for symfony will be:
```yaml
services:
    vendor.base_service:
        class:        Demo\BaseService
services:
    vendor.consuming_service:
        class:        Demo\BaseService
        arguments:    ['@vendor.base_service']
```

We now have the same definition in Contao 3 and 4 but - oh my! - we now have
created two instances of the same service.

To fetch the service from the symfony DIC, we provide a gateway

We will now alter the Contao 3 registration to use the symfony container if
possible and fall back to the legacy instantiation then.
```diff
+ // Check for Contao 4 dependency injection container.
+ if ($symfony = $container['contao4']) {
+     $container['base-service'] = $container->share(
+          function ($container) { return $symfony->get('vendor.base_service'); }
+     );
+     $container['consuming-service'] = $container->share(
+          function ($container) { return $symfony->get('vendor.consuming_service'); }
+     );
+
+     return;
+ }
+
+ // Legacy initialization for Contao 3 here.
 $container['base-service'] = $container->share(
     function ($container) {
         return new \Demo\BaseService();
     }
 );
 $container['consuming-service'] = $container->share(
     function ($container) {
         return new \Demo\ConsumingService($container['base-service']);
     }
 );
```

That is a hell of code duplication but hey, at least we are compatible with
both versions of Contao.

## But what if you are consuming other services not yet in symfony DIC?

For this we have a Pimple gateway which you can use as service factory for
internal services.

Assuming you need a service named `super-service`, you put the following in
your `services.yml`:
```yaml
services:
    # This is your service.
    vendor.consuming_service:
        class:        Demo\BaseService
        arguments:    ['@vendor.dependency']

    # This is a virtual dependency on the service provided by another extension.
    # This is needed only temporarily until the extension provides a proper symfony service name.
    vendor.dependency:
        class:        AnotherVendor\SuperService
        factory:      ['@contao_community_alliance.legacy_dic', getService]
        arguments:    ['super-service']

```
