<?php

namespace Eureka;


class FeignConfig
{

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_DELETE = 'DELETE';

    /**
     * @var string
     * @desc server name
     */
    private $name;
    private $baseUri;
    private $uri;
    private $httpMethod = self::HTTP_POST;
    private $data = null;
    private $headers = null;
    private $fallback = null;
    private $encode = null;
    private $decode = null;
    private $discovery = null;

    private $timeOut = 0.5; //超时时间

    // constructor
    public function __construct($config)
    {
        $this->init($config);
    }

    public function init($config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

    }

    public function getBaseUri()
    {
        if (!empty($this->baseUri)) {
            return $this->baseUri;
        }

        if (!empty($this->discovery) && is_callable($this->discovery)) {
            return call_user_func($this->discovery, $this->name);
        }
    }

    public function getUri()
    {
        return $this->uri;
    }


    public function getName()
    {
        return $this->name;
    }

    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getFallback()
    {
        return $this->fallback;
    }

    public function getEncode()
    {
        return $this->encode;
    }

    public function getDecode()
    {
        return $this->decode;
    }

    public function getTimeOut()
    {
        return $this->timeOut;
    }
}