<?php


namespace service;


use Eureka\Exceptions\InstanceFailureException;
use Swlib\Http\ContentType;
use Swlib\Saber;

class EurekaService
{

    public $apps = [];

    public $client;

    public function __construct($config)
    {
        $this->client = new EurekaClient($config);
    }

    public function appSync()
    {
        if (!empty($this->apps)) {
            foreach ($this->apps as $name => $app) {
                $this->getApps($name);
            }
        }
    }

    public function getAppUri($appName)
    {
        if (empty($this->apps[$appName])) {
            $this->getApps($appName);
        }
        $app = $this->client->getConfig()->getDiscoveryStrategy()->getInstance($this->apps[$appName]);
        if ('true' == $app['port']['@enabled']) {
            return 'http://' . $app['ipAddr'] . ':' . $app['port']['$'];
        }

        if ('true' == $app['securePort']['@enabled']) {
            return 'https://' . $app['ipAddr'] . ':' . $app['port']['$'];
        }

        throw new InstanceFailureException("$appName no use");
    }

    public function getApps($appName)
    {
        $apps = $this->client->fetchInstances($appName);
        $this->apps[$appName] = [];
        foreach ($apps as $app) {
            if ('UP' == $app['status']) {
                $this->apps[$appName][] = [
                    'instanceId' => $app['instanceId'],
                    'port' => $app['port'],
                    'ipAddr' => $app['ipAddr'],
                    'securePort' => $app['securePort'],
                    'status' => $app['status'],
                ];
            }
        }
        return $this->apps;
    }

    public function post($appName, $uri, $data = null)
    {
        return $this->request($appName, 'POST', $uri, $data);
    }

    public function get($appName, $uri)
    {
        return $this->request($appName, 'GET', $uri);
    }

    public function put($appName, $uri, $data = null)
    {
        return $this->request($appName, 'PUT', $uri, $data);
    }

    public function delete($appName, $uri)
    {
        return $this->request($appName, 'DELETE', $uri);
    }

    public function request($appName, $httpMethod, $uri, $header = [], $data = null)
    {
        $client = Saber::create([
            'base_uri' => $this->getAppUri($appName),
            'headers' => [
                    'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
                    'Content-Type' => ContentType::JSON,
                    'DNT' => '1',
                    'User-Agent' => 'saber'
                ] + $header
        ]);

        $options['uri'] = $uri;
        $options['method'] = $httpMethod;

        if ($data !== null) {
            $options['data'] = $data;
        }

        return $client->request($options);
    }
}