<?php

namespace Owl\Service;

use Owl\Logger;
use Owl\Service;
use Predis\Client;
use Predis\Response\Error;

// https://github.com/nrk/predis
if (!class_exists('\Predis\Client')) {
    throw new \Exception('Require Predis library');
}

/**
 * @mixin Client
 *
 * @example
 * $parameters = [
 *     'scheme' => 'tcp',
 *     'host' => '127.0.0.1',
 *     'port' => 6379,
 *     'database' => 1,
 *     'persistent' => true,
 *     'timeout' => 3.0,
 * ];
 *
 * $options = [
 *     'exception' => true,
 * ];
 *
 * $redis = new \Owl\Service\Predis($parameters, $options);
 */
class Predis extends Service
{
    protected $client;

    protected $command_alias = [
        'settimeout' => 'expire',
        'delete' => 'del',
    ];

    public function __call(string $method, array $args)
    {
        $client = $this->connect();

        $command = strtolower($method);
        if (isset($this->command_alias[$command])) {
            $method = $this->command_alias[$command];
        }

        Logger::log('debug', 'redis execute', [
            'command' => $command,
            'arguments' => $args,
        ]);

        $result = $args ? call_user_func_array([$client, $method], $args) : $client->$method();
        if ($result instanceof Error) {
            throw new \Owl\Service\Exception($result->getMessage());
        }

        return $result;
    }

    /**
     * @return Client|null
     */
    public function connect()
    {
        if (!$this->client || !$this->client->isConnected()) {
            $parameters = $this->getConfig('parameters');
            $options = $this->getConfig('options') ?: [];

            try {
                $this->client = new Client($parameters, $options);

                Logger::log('debug', 'redis connected', [
                    'parameters' => $parameters,
                    'options' => $options,
                ]);
            } catch (\Throwable $exception) {
                Logger::log('error', 'redis connect failed', [
                    'error' => $exception->getMessage(),
                    'parameters' => $parameters,
                    'options' => $options,
                ]);
            }
        }

        return $this->client;
    }

    public function disconnect()
    {
        if ($this->client) {
            $parameters = $this->getConfig('parameters');

            $is_persistent = isset($parameters['persistent']) && $parameters['persistent'];
            if (!$is_persistent) {
                $this->client->disconnect();
            }

            $this->client = null;

            Logger::log('debug', 'redis disconnected', [
                'parameters' => $parameters,
            ]);
        }
    }

    public function multi()
    {
        return $this->connect()->transaction();
    }

    public function hMGet($key, array $fields)
    {
        $redis = $this->connect();

        $values = $redis->hmget($key, $fields);

        $result = [];
        foreach ($values as $i => $value) {
            $key = $fields[$i];

            $result[$key] = $value;
        }

        return $result;
    }
}
