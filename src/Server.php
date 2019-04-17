<?php


namespace CodeEdu\LaravelSso;


use Illuminate\Cache\CacheManager;
use Jasny\SSO\Server as JasnyServer;

abstract class Server extends JasnyServer
{

    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct(CacheManager $cache, array $options = [])
    {
        parent::__construct($options);
        $this->options = $options + $this->options;
        $cacheDriver = $options['cache_driver'] ?? config('cache.default');
        $this->cache = $cache->driver($cacheDriver);
    }

    protected function startUserSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo()
    {
        $this->startBrokerSession();
        $user = null;

        $username = $this->getSessionData('sso_user');
        $user = $this->getUserInfo($username);
        if (!$user) {
            return $this->fail("Unauthenticated", 401);
        }

        return response()->json($user);
    }

    protected function getSessionData($key)
    {
        return $key === 'id' ? session_id() : \Session::get($key);
    }

    public function login()
    {
        $this->startBrokerSession();

        $validation = \Validator::make(\Request::all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            $errors = json_encode((array)$validation->errors());
            $this->fail($errors, 400);
        }

        $data = $validation->validated();
        $validation = $this->authenticate($data['username'], $data['password']);

        if ($validation->failed()) {
            return $this->fail($validation->getError(), 400);
        }

        $this->setSessionData('sso_user', $_POST['username']);
        return $this->userInfo();
    }

    public function logout()
    {
        $this->startBrokerSession();
        $this->setSessionData('sso_user', null);

        return response()->json([], 204);
    }

    protected function setSessionData($key, $value)
    {
        !isset($value) ? \Session::forget($key) : \Session::put($key, $value);
        \Session::save();
    }

    public function startBrokerSession()
    {
        if (isset($this->brokerId)) {
            return;
        }

        $sid = $this->getBrokerSessionID();

        if ($sid === false) {
            $this->fail("Broker didn't send a session key", 400);
            return;
        }

        $linkedId = $this->cache->get($sid);

        if (!$linkedId) {
            $this->fail("The broker session id isn't attached to a user session", 403);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($linkedId !== session_id()) throw new \Exception("Session has already started", 400);
            return;
        }

        $this->setSessionId($linkedId);
        $this->brokerId = $this->validateBrokerSessionId($sid);
    }

    public function attach()
    {
        $this->detectReturnType();

        if (empty($_REQUEST['broker'])) {
            return $this->fail("No broker specified", 400);
        }
        if (empty($_REQUEST['token'])) {
            return $this->fail("No token specified", 400);
        }

        if (!$this->returnType) {
            return $this->fail("No return url specified", 400);
        }

        $checksum = $this->generateAttachChecksum($_REQUEST['broker'], $_REQUEST['token']);

        if (empty($_REQUEST['checksum']) || $checksum != $_REQUEST['checksum']) {
            return $this->fail("Invalid checksum", 400);
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($_REQUEST['broker'], $_REQUEST['token']);

        $sessionId = $this->getSessionData('id');
        $this->cache->put($sid, $sessionId);
        $this->setSessionId($sessionId);
        $this->outputAttachSuccess();
    }

    public function setSessionId($id){
        \Session::setId($id);
        \Session::start();
        \Session::save();
    }
}
