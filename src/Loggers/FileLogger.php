<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/18
 * Time: 上午1:25
 */
namespace NB\Loggers;

class FileLogger implements LoggerInterface
{
    public $path;
    public $level;

    protected function __construct(string $path, int $level = self::LEVEL_DEBUG)
    {
        $this->path = $path;
        $this->level = $level;
    }

    public static function get(string $path, int $level = self::LEVEL_DEBUG):FileLogger
    {
        static $fileLoggers = [];
        $key = $level . ":" . $path;
        if (!isset($fileLoggers[$key])) {
            $fileLoggers[$key] = new FileLogger($path, $level);
        }

        return $fileLoggers[$key];
    }

    protected function log(int $level, string &$message, array &$args)
    {
        if ($level < $this->level) {
            return;
        }
        file_put_contents(
            $this->path,
            vsprintf(date("Y-m-d H:i:s") . " [" . self::LEVEL_NAMES[$level] . "] " . $message . "\r\n", $args),
            FILE_APPEND);
    }

    public function debug(string $message, ...$args)
    {
        $this->log(self::LEVEL_DEBUG, $message, $args);
    }

    public function error(string $message, ...$args)
    {
        $this->log(self::LEVEL_ERROR, $message, $args);
    }

    public function info(string $message, ...$args)
    {
        $this->log(self::LEVEL_INFO, $message, $args);
    }

    public function notice(string $message, ...$args)
    {
        $this->log(self::LEVEL_NOTICE, $message, $args);
    }

    public function warning(string $message, ...$args)
    {
        $this->log(self::LEVEL_WARNING, $message, $args);
    }

    public function alert(string $message, ...$args)
    {
        $this->log(self::LEVEL_ALERT, $message, $args);
    }

    public function emergency(string $message, ...$args)
    {
        $this->log(self::LEVEL_EMERGENCY, $message, $args);
    }
}