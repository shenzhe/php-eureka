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
    private $descover = null;

    // constructor
    public function __construct($config)
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

        if (!empty($this->descover) && is_callable($this->descover)) {
            return call_user_func($this->descover, $this->name);
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
}