<?php

namespace Owl;

use Owl\Context\Session;
use Owl\Context\Cookie;
use Owl\Context\Redis;
use Owl\Parameter\Validator;
use UnexpectedValueException;

abstract class Context
{
    protected $config;

    abstract public function set($key, $val);

    abstract public function get($key = null);

    abstract public function has($key);

    abstract public function remove($key);

    abstract public function clear();

    public function __construct(array $config)
    {
        (new Validator())->execute($config, [
            'token' => ['type' => 'string'],
        ]);

        $this->config = $config;
    }

    public function setConfig($key, $val)
    {
        $this->config[$key] = $val;
    }

    public function getConfig($key = null)
    {
        return ($key === null)
             ? $this->config
             : ($this->config[$key] ?? null);
    }

    public function getToken()
    {
        return $this->getConfig('token');
    }

    /**
     * 保存上下文数据，根据需要重载.
     *
     * @return mixed|void
     */
    public function save()
    {
    }

    /**
     * @param string $type
     * @param array $config
     *
     * @return Cookie|Redis|Session
     * @throws
     */
    public static function factory($type, array $config)
    {
        switch (strtolower($type)) {
            case 'session': return new Session($config);
            case 'cookie': return new Cookie($config);
            case 'redis': return new Redis($config);
            default:
                throw new UnexpectedValueException('Unknown context handler type: ' . $type);
        }
    }
}
