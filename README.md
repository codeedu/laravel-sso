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
Disable cookie session encryption. Override **handle** method in **EncryptCookies** middleware:
```php
public function handle($request, Closure $next)
{
    $this->except[] = env('SESSION_COOKIE');
    return parent::handle($request, $next);
}
```
Change **cookie** key in **config/session.php** to:
```php
'cookie' => env('SESSION_COOKIE'),
```
Disable session regenerate after login success. Override **sendLoginResponse** in **LoginController**:
```php
protected function sendLoginResponse(Request $request)
{
    //$request->session()->regenerate();

    $this->clearLoginAttempts($request);

    return $this->authenticated($request, $this->guard()->user())
            ?: redirect()->intended($this->redirectPath());
}
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

Start session in api middleware. Override **api** key in **Kernel.php**

```php
'api' => [
    'throttle:60,1',
    'bindings',
    \Illuminate\Session\Middleware\StartSession::class,
],
```
