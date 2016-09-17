<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/18
 * Time: 上午1:18
 */
namespace NB\Loggers;

interface LoggerInterface
{
    const LEVEL_DEBUG = 10;
    const LEVEL_INFO = 20;
    const LEVEL_NOTICE = 30;
    const LEVEL_WARNING = 40;
    const LEVEL_ERROR = 50;
    const LEVEL_ALERT = 60;
    const LEVEL_EMERGENCY = 70;
    const LEVEL_NAMES = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_NOTICE => 'NOTICE',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_ALERT => 'ALERT',
        self::LEVEL_EMERGENCY => 'EMERGENCY',
    ];

    public function debug(string $message, ...$args);
    public function error(string $message, ...$args);
    public function info(string $message, ...$args);
    public function notice(string $message, ...$args);
    public function warning(string $message, ...$args);
    public function alert(string $message, ...$args);
    public function emergency(string $message, ...$args);
}