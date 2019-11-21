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

Create App\Sso\Server.php:
```php
<?php


namespace App\Sso;


use CodeEdu\LaravelSso\Server as SsoServer;
use Jasny\ValidationResult;
use App\Models\User;

class Server extends SsoServer
{

    private $brokers = [
        '1' => 'secret1',
        '2' => 'secret2'
    ];


    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return \Jasny\ValidationResult
     */
    protected function authenticate($username, $password)
    {
        if (!\Auth::guard('web')->validate(['email' => $username, 'password' => $password])) {
            return ValidationResult::error(trans('auth.failed'));
        }

        return ValidationResult::success();
    }

    /**
     * Get the secret key and other info of a broker
     *
     * @param string $brokerId
     * @return array
     */
    protected function getBrokerInfo($brokerId)
    {
        return !array_key_exists($brokerId, $this->brokers) ? null : [
            'id' => $brokerId,
            'secret' => $this->brokers[$brokerId]
        ];
    }

    /**
     * Get the information about a user
     *
     * @param string $username
     * @return array|object
     */
    protected function getUserInfo($username)
    {
        $user = User::whereEmail($username)->first();
        return !$user ? null : [
            'user' => $user,
        ];
    }
}
```
This file will describe how Server SSO identity and authenticate the brokers. More details see: [https://github.com/jasny/sso](https://github.com/jasny/sso)
