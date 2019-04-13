<?php
namespace Small\server\http;

/**
 * Class Cookie
 * @package Small\server\http
 */
class Cookie {

    public $name;
    public $value;
    public $expire;
    public $path;
    public $domain;
    public $secure;
    public $httponly;

    /**
     * Cookie constructor.
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param null $httponly
     */
    public function __construct(string $name, string $value, $expire = 3600, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setName($name)
            ->setValue($value)
            ->setExpire($expire)
            ->setPath($path)
            ->setDomain($domain)
            ->setSecure($secure)
            ->setHttponly($httponly);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name){
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value){
        $this->value = $value;
        return $this;
    }

    /**
     * @param int $time
     * @return $this
     */
    public function setExpire(int $time = 3600){
        $this->expire = time() + $time;
        return $this;
    }

    /**
     * @param null $path
     * @return $this
     */
    public function setPath($path = null){
        $this->path = $path;
        return $this;
    }

    /**
     * @param null $domain
     * @return $this
     */
    public function setDomain($domain = null){
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param null $secure
     * @return $this
     */
    public function setSecure($secure = null){
        $this->secure = $secure;
        return $this;
    }

    /**
     * @param null $httponly
     * @return $this
     */
    public function setHttponly($httponly = null){
        $this->httponly = $httponly;
        return $this;
    }
}