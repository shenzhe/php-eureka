<?php


namespace Eureka;

use Swlib\Http\ContentType;
use Swlib\Saber;

class FeignClient
{

    /**
     * @var FeignConfig
     */
    private $config;

    public function __construct(FeignConfig $config)
    {
        $this->config = $config;
    }

    public function setConfig($config)
    {
        $this->config->init($config);
    }

    /**
     * @return FeignConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed|Saber\Request|Saber\Response
     * @throws \Throwable
     */
    public function request($header = null)
    {
        $headers = [
            'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
            'Content-Type' => ContentType::JSON,
            'Accept' => ContentType::JSON,
            'DNT' => '1',
            'User-Agent' => 'saber'
        ];
        if (!empty($header)) {
            $headers += $header;
        }
        $client = Saber::create([
            'base_uri' => $this->config->getBaseUri(),
            'headers' => $headers,
            'timeout' => $this->config->getTimeOut(),
            'use_pool' => $this->config->getUsePool(),
            'retry_time' => $this->config->getRetryTime(),
        ]);

        $options['uri'] = $this->config->getUri();
        $options['method'] = $this->config->getHttpMethod();
        $data = $this->config->getData();
        if ($data !== null) {
            $encode = $this->config->getEncode();
            if (!empty($encode) && is_callable($encode)) {
                $data = call_user_func($encode, $data);
            }
            $options['data'] = $data;
        }
        try {
            $ret = $client->request($options);
            $decode = $this->config->getDecode();
            if (!empty($decode) && is_callable($decode)) {
                $ret = call_user_func($decode, $ret);
            }
            return $ret;
        } catch (\Throwable $e) {
            $fallback = $this->config->getFallback();
            if ($fallback && is_callable($fallback)) {
                $ret = call_user_func($fallback, $e);
                return $ret;
            }

            throw $e;
        }
    }
}
