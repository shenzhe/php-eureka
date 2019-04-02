<?php

namespace Eureka;

use Eureka\Exceptions\DeRegisterFailureException;
use Eureka\Exceptions\InstanceFailureException;
use Eureka\Exceptions\RegisterFailureException;
use Exception;
use Swlib\Saber;

class EurekaClient
{

    /**
     * @var EurekaConfig
     */
    private $config;
    private static $instances;
    private $client;

    // constructor
    public function __construct($config)
    {
        $this->config = new EurekaConfig($config);
        $this->client = Saber::create([
            'base_uri' => $this->config->getEurekaDefaultUrl(),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ]);
    }

    // getter
    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = new EurekaConfig($config);
    }

    // register with eureka
    public function register()
    {
        $config = $this->config->getRegistrationConfig();
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " Registering...");

        $response = $this->client->post('/eureka/apps/' . $this->config->getAppName(),
            $config
        );
        if ($response->getStatusCode() != 204) {
            throw new RegisterFailureException("Could not register with Eureka.");
        }
    }

    // de-register from eureka
    public function deRegister()
    {
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " De-registering...");

        $response = $this->client->delete('/eureka/apps/' . $this->config->getAppName() . '/' . $this->config->getInstanceId());

        if ($response->getStatusCode() != 200) {
            throw new DeRegisterFailureException("Cloud not de-register from Eureka.");
        }
    }

    public function syncDeRegister($timeOut = 3)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'DELETE',
                'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n",
                'timeout' => $timeOut
            )));
        $url = $this->getConfig()->getEurekaDefaultUrl() . '/apps/' . $this->getConfig()->getAppName() . '/' . $this->getConfig()->getInstanceId();
        $result = file_get_contents($url, false, $context);
        $headers = explode(' ', $http_response_header[0]);
        if ($headers[1] != 200) {
            throw new DeRegisterFailureException("Cloud not de-register from Eureka.");
        }
        return $result;

    }

    // send heartbeat to eureka
    public function heartbeat()
    {
        $this->output("[" . date("Y-m-d H:i:s") . "]" . " Sending heartbeat...");

        try {
            $response = $this->client->put('/eureka/apps/' . $this->config->getAppName() . '/' . $this->config->getInstanceId());

            if ($response->getStatusCode() != 200) {
                $this->output("[" . date("Y-m-d H:i:s") . "]" . " Heartbeat failed... (code: " . $response->getStatusCode() . ")");
            }
        } catch (Exception $e) {
            $this->output("[" . date("Y-m-d H:i:s") . "]" . "Heartbeat failed because of connection error... (code: " . $e->getCode() . ")");
        }
    }

    // register and send heartbeats periodically
    public function start()
    {
        $this->register();

        //定时心跳
        swoole_timer_tick($this->config->getHeartbeatInterval(), function () {
            $this->heartbeat();
        });

        return 0;
    }

    public function fetchInstance($appName)
    {
        $instances = $this->fetchInstances($appName);

        return $this->config->getDiscoveryStrategy()->getInstance($instances);
    }

    public function fetchInstances($appName, $reload = false)
    {
        if (!$reload && !empty(self::$instances[$appName])) {
            return self::$instances[$appName];
        }
        $provider = $this->getConfig()->getInstanceProvider();

        try {
            $response = $this->client->get('/eureka/apps/' . $appName);

            if ($response->getStatusCode() != 200) {
                if (!empty($provider)) {
                    return $provider->getInstances($appName);
                }

                throw new InstanceFailureException("Could not get instances from Eureka.");
            }

            $body = json_decode($response->getBody()->__toString(), true);
            if (!isset($body['application']['instance'])) {
                if (!empty($provider)) {
                    return $provider->getInstances($appName);
                }

                throw new InstanceFailureException("No instance found for '" . $appName . "'.");
            }

            self::$instances[$appName] = $body['application']['instance'];

            return self::$instances[$appName];
        } catch (RequestException $e) {
            if (!empty($provider)) {
                return $provider->getInstances($appName);
            }

            throw new InstanceFailureException("No instance found for '" . $appName . "'.");
        }
    }

    public function clearInstances()
    {
        self::$instances = null;
    }

    public function getAllInstances()
    {
        return self::$instances;
    }

    public function sync()
    {
        if (!empty(self::$instances)) {
            foreach (self::$instances as $appName => $app) {
                $this->fetchInstances($appName, true);
            }
        }
    }

    private function output($message)
    {
        if (php_sapi_name() !== 'cli')
            return;

        echo $message . PHP_EOL;
    }
}