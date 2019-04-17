<?php


namespace CodeEdu\LaravelSso\Sso;


use Jasny\SSO\Broker as SsoBroker;
use Jasny\SSO\Exception;
use Jasny\SSO\NotAttachedException;

class Broker extends SsoBroker
{

    public function __construct($url, $broker, $secret, $cookie_lifetime = 3600)
    {
        if (!$url) {
            throw new \InvalidArgumentException("SSO server URL not specified");
        }
        if (!$broker) {
            throw new \InvalidArgumentException("SSO broker id not specified");
        }
        if (!$secret) {
            throw new \InvalidArgumentException("SSO broker secret not specified");
        }

        $this->url = $url;
        $this->broker = $broker;
        $this->secret = $secret;
        $this->cookie_lifetime = $cookie_lifetime;

        $cookieToken = \Request::cookie($this->getCookieName());
        if (isset($cookieToken)) {
            $this->token = $cookieToken;
        }
    }

    /**
     * Generate session token
     */
    public function generateToken()
    {
        if (isset($this->token)) {
            return;
        }

        $this->token = base_convert(md5(uniqid(rand(), true)), 16, 36);
        \Cookie::queue(
            \Cookie::make(
                $this->getCookieName(),
                $this->token,
                time() + $this->cookie_lifetime,
                '/'
            )
        );
    }

    public function attach($returnUrl = null)
    {
        if ($this->isAttached()) {
            return;
        }

        if ($returnUrl === true) {
            $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $returnUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        $params = ['return_url' => $returnUrl];
        $url = $this->getAttachUrl($params);

        return redirect($url, 307);
    }

    public function logout()
    {
        $this->request('POST', 'logout', 'logout');
        $this->clearToken();
    }

    protected function request($method, $command, $data = null)
    {
        if (!$this->isAttached()) {
            throw new NotAttachedException('No token');
        }
        $url = $this->getRequestUrl($command, !$data || $method === 'POST' ? [] : $data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            ['Accept: application/json', 'Authorization: Bearer ' . $this->getSessionID()]
        );

        if ($method === 'POST' && !empty($data)) {
            $post = is_string($data) ? $data : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $message = 'Server request failed: ' . curl_error($ch);
            throw new Exception($message);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        list($contentType) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        if ($contentType != 'application/json' and $httpCode != 204) {
            $message = 'Expected application/json response, got ' . $contentType;
            throw new Exception($message);
        }

        $data = json_decode($response, true);
        if ($httpCode == 403) {
            $this->clearToken();
            throw new NotAttachedException($data['error'] ?: $response, $httpCode);
        }
        if ($httpCode >= 400) {
            throw new Exception($data['error'] ?: $response, $httpCode);
        }

        return $data;
    }

    /**
     * Clears session token
     */
    public function clearToken()
    {
        \Cookie::queue(
            \Cookie::forget($this->getCookieName())
        );
        $this->token = null;
    }
}
