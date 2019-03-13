<?php
namespace Small\server\http;

class Cookie {

    public $name;
    public $value;
    public $time;
    public $path;
    public $domain;
    public $secure;
    public $httponly;

    public function __construct(string $name, string $value, $time = 3600, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->setName($name)
            ->setValue($value)
            ->setTime($time)
            ->setPath($path)
            ->setDomain($domain)
            ->setSecure($secure)
            ->setHttponly($httponly);
    }

    public function setName(string $name){
        $this->name = $name;
        return $this;
    }

    public function setValue(string $value){
        $this->value = $value;
        return $this;
    }

    public function setTime(int $time = 3600){
        $this->time = time() + $time;
        return $this;
    }

    public function setPath($path = null){
        $this->path = $path;
        return $this;
    }

    public function setDomain($domain = null){
        $this->domain = $domain;
        return $this;
    }

    public function setSecure($secure = null){
        $this->secure = $secure;
        return $this;
    }

    public function setHttponly($httponly = null){
        $this->httponly = $httponly;
        return $this;
    }
}