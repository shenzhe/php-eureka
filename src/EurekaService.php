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
        $this->client->sync();
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

    public function post($baseUri, $uri, $data = null, $header = null)
    {
        return $this->request($baseUri, 'POST', $uri, $data, $header);
    }

    public function get($baseUri, $uri, $header = null)
    {
        return $this->request($baseUri, 'GET', $uri, null, $header);
    }

    public function put($baseUri, $uri, $data = null, $header = null)
    {
        return $this->request($baseUri, 'PUT', $uri, $data, $header);
    }

    public function delete($baseUri, $uri, $header = null)
    {
        return $this->request($baseUri, 'DELETE', $uri, null, $header);
    }

    public function request($baseUri, $httpMethod, $uri, $data = null, $header = [])
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
            'base_uri' => $baseUri,
            'headers' => $headers
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