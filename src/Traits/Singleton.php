<?php

namespace Owl\Traits;

/**
 * 实现单例模式
 */
trait Singleton
{
    protected static $__instances__ = [];

    protected function __construct()
    {
    }

    public function __clone()
    {
        throw new \Exception('Cloning ' . __CLASS__ . ' is not allowed');
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        $class = get_called_class();

        if (!isset(static::$__instances__[$class])) {
            static::$__instances__[$class] = new static();
        }

        return static::$__instances__[$class];
    }

    public static function resetInstance()
    {
        $class = get_called_class();
        unset(static::$__instances__[$class]);
    }
}
