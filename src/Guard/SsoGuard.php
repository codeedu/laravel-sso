<?php


namespace CodeEdu\LaravelSso\Guard;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Jasny\SSO\Broker;

class SsoGuard implements Guard
{
    use GuardHelpers;
    private $model;
    /**
     * @var Broker
     */
    private $broker;

    /**
     * SsoGuard constructor.
     */
    public function __construct(Broker $broker, $model)
    {
        $this->model = $model;
        $this->broker = $broker;
    }

    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }
        try {
            $this->_setUser($this->broker->getUserInfo());
            return $this->user;
        } catch (\Exception $e) {
            if (config('sso.log_erros')) {
                \Log::error($e->getMessage(), ['exception' => $e]);
            }
            return null;
        }
    }

    public function validate(array $credentials = [])
    {
        return (bool)$this->attempt($credentials, false);
    }

    public function attempt(array $credentials = [])
    {
        try {
            $this->_setUser($this->broker->login($credentials['email'], $credentials['password']));
            return true;
        } catch (\Exception $e) {
            if (config('sso.log_erros')) {
                \Log::error($e->getMessage(), ['exception' => $e]);
            }
            return false;
        }
    }

    protected function _setUser($user){
        $this->user = new $this->model($user['user']);
    }

    public function logout()
    {
        try {
            $this->broker->logout();
        } catch (\Exception $e) {
            if (config('sso.log_erros')) {
                \Log::error($e->getMessage(), ['exception' => $e]);
            }
        }

    }

    public function setUser(Authenticatable $user)
    {
        throw new \Exception('Not implemented');
    }
}
