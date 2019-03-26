<?php


namespace Eureka;


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

    public function getAppUri($appName, $instances = null)
    {
        if (empty($instances)) {
            $instances = $this->apps;
        }
        if (empty($instances[$appName])) {
            $instances = $this->getApps($appName);
        }
        $app = $this->client->getConfig()->getDiscoveryStrategy()->getInstance($instances[$appName]);
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

    public function post($appName, $uri, $data = null, $header = null)
    {
        return $this->request($appName, 'POST', $uri, $data, $header);
    }

    public function get($appName, $uri, $header = null)
    {
        return $this->request($appName, 'GET', $uri, null, $header);
    }

    public function put($appName, $uri, $data = null, $header = null)
    {
        return $this->request($appName, 'PUT', $uri, $data, $header);
    }

    public function delete($appName, $uri, $header = null)
    {
        return $this->request($appName, 'DELETE', $uri, null, $header);
    }

    public function request($appName, $httpMethod, $uri, $data = null, $header = [])
    {
        $baseUri = $this->getAppUri($appName);
        $client = Saber::create([
            'base_uri' => $baseUri,
            'headers' => [
                    'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
                    'Content-Type' => ContentType::JSON,
                    'Accept' => ContentType::JSON,
                    'DNT' => '1',
                    'User-Agent' => 'saber'
                ] + $header
        ]);

        $options['uri'] = $uri;
        $options['method'] = $httpMethod;

        if ($data !== null) {
            $options['data'] = $data;
        }

        $data = $client->request($options);
        return $data;
    }
}