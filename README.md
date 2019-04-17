## About

This library provides integration between Laravel Framework and SSO (Single Sign-On) [https://en.wikipedia.org/wiki/Single_sign-on](https://en.wikipedia.org/wiki/Single_sign-on).

## Get started

### Instalation
Execute in shell:
```sh
composer require codeedu/laravel-sso:0.0.1
```
Publish the sso configuration file:
```sh
php artisan vendor:publish --provider="CodeEdu\LaravelSso\SSOServiceProvider"
```
#### Broker Configuration
Set environment variables:
```
SSO_SERVER=http://server.address
SSO_BROKER_ID=BROKER ID
SSO_BROKER_SECRET=BROKER SECRET
```
Register middleware **CodeEdu\LaravelSso\Middleware\AttachBroker** in **routes** key in **Kernel.php**.

Register **sso** in **config/auth.php**:
```php
'sso' => [
    'driver' => 'sso',
    'model' => \App\User::class
]
```

#### Server Configuration

Set environment variable:
```
SSO_TYPE=server
```
