<?php

namespace Owl;

use Psr\Log\LoggerInterface;
use Throwable;

class Logger
{
    private static $logger;

    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public static function unsetLogger()
    {
        self::$logger = null;
    }

    /**
     * @return LoggerInterface|null
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    public static function log($level, $message, array $context = [])
    {
        if ($logger = self::$logger) {
            $logger->log($level, $message, $context);
        }
    }

    public static function logException(Throwable $exception, array $context)
    {
        if (!$logger = self::$logger) {
            return;
        }

        $e = $exception;
        while ($prev = $exception->getPrevious()) {
            $e = $prev;
        }

        $message = sprintf('%s(%d): %s', get_class($e), $e->getCode(), $e->getMessage());
        $logger->error($message, $context);

        $traces = explode("\n", $e->getTraceAsString());
        foreach ($traces as $trace) {
            $logger->error($trace);
        }
    }
}
