<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/18
 * Time: 下午6:42
 */
namespace Foldy\Data;

use Foldy\Constants;
use Foldy\DIContainer;
use Foldy\Loggers\LoggerInterface;
use Pixie\QueryBuilder;

class DB extends QueryBuilder\QueryBuilderHandler
{
    const DEFAULT_DB = Constants::DI_KEY_DEFAULT_DB;
    const LOG_LEVEL = "info";
    const LOG_FORMAT = "DB statement: %s, args: %s <%dms>";
    static $di;

    protected $name;

    public static function getSharedDI():DIContainer
    {
        if (!self::$di) {
            self::$di = new DIContainer();
        }
        return self::$di;
    }

    public static function setSharedDI(DIContainer $di)
    {
        self::$di = $di;
    }

    /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection
     * @param string $name
     * @return \Pixie\Connection
     */
    public static function getDBConnection(string $name):\Pixie\Connection
    {
        static $connectionCache = [];
        if (isset($connectionCache[$name])) {
            return $connectionCache[$name];
        } else {

            /**
             * @var array $config
             *  $config = array(
             *      'driver'    => 'mysql', // Db driver
             *      'host'      => 'localhost',
             *      'database'  => 'your-database',
             *      'username'  => 'root',
             *      'password'  => 'your-password',
             *      'charset'   => 'utf8', // Optional
             *      'collation' => 'utf8_unicode_ci', // Optional
             *      'prefix'    => 'cb_', // Table prefix, optional
             *  );
             */
            $config = self::getSharedDI()->get($name);
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            return $connectionCache[$name] = new \Pixie\Connection($config['driver'],
                $config);
        }
    }

    /**
     * DB constructor.
     * @param null|\Pixie\Connection|string $connection
     */
    public function __construct($connection = self::DEFAULT_DB)
    {
        parent::__construct(is_string($connection) ? self::getDBConnection($connection) : $connection);
        $this->name = $connection;
    }

    public function statement($sql, $bindings = array())
    {
        list($pdoStatement, $time_ms) = parent::statement($sql, $bindings);
        $logger = self::getSharedDI()->get(Constants::DI_KEY_LOGGER);
        if ($logger) {
            /**
             * @var LoggerInterface $logger
             */
            $logger->{static::LOG_LEVEL}(static::LOG_FORMAT, json_encode($sql), json_encode($bindings), $time_ms * 1000);
        }
        return [$pdoStatement, $time_ms];
    }
}