<?php
/**
 * Created by PhpStorm.
 * User: dashi
 * Date: 16/9/18
 * Time: 上午10:33
 */
namespace Foldy\Data;

use Foldy\Constants;
use Foldy\DIContainer;
use Foldy\Exceptions\Exception;
use Foldy\Utils;
use Pixie\QueryBuilder;

abstract class AbstractDataObject
{
    const CONFIT_DB_TABLE_NAME = 'dbTableName';
    const CONFIT_DB_CONFIG_NAME = 'dbConfigName';
    const CONFIG_DB_IDENTIFIER_FIELD_IS_AUTO_INCREMENT = 'dbIdentifierFieldIsAutoIncrement';
    const CONFIG_DB_IDENTIFIER_FIELD = 'dbIdentifierField';
    const CONFIG_CREATION_ARGS_FOR_DB_FETCH = 'creationArgsForDBFetch';
    /**
     * @var DIContainer $di
     */
    static $di;

    public static function getConfig(string $key)
    {
        switch ($key) {
            case self::CONFIT_DB_TABLE_NAME:
                return Utils::camelcaseToUnderlineJoined(Utils::getClassBaseName(static::class));
            case self::CONFIT_DB_CONFIG_NAME:
                return DB::DEFAULT_DB;
            case self::CONFIG_DB_IDENTIFIER_FIELD_IS_AUTO_INCREMENT:
                return true;
            case self::CONFIG_DB_IDENTIFIER_FIELD:
                return "id";
            case self::CONFIG_CREATION_ARGS_FOR_DB_FETCH:
                return [];
        }
        throw new \Exception("unknown config $key");
    }

    public static function qb():QueryBuilder\QueryBuilderHandler
    {
        $db_class = DB::getSharedDI()->get(Constants::DI_KEY_CLASS_DB) ?? DB::class;
        /**
         * @var QueryBuilder\QueryBuilderHandler $result
         */
        $result = new $db_class(static::getConfig(self::CONFIT_DB_CONFIG_NAME));
        /** @noinspection PhpParamsInspection */
        $result = $result->table(static::getConfig(self::CONFIT_DB_TABLE_NAME));
        $result = $result->asObject(static::class, static::getConfig(self::CONFIG_CREATION_ARGS_FOR_DB_FETCH));
        return $result;
    }

    /**
     * @param $id_value
     * @return null|static
     */
    public static function find($id_value)
    {
        return static::qb()->find($id_value, static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD));
    }

    public static function findAll($field_name, $value):array
    {
        return static::qb()->findAll($field_name, $value);
    }

    public static function where($key, $operator = null, $value = null):QueryBuilder\QueryBuilderHandler
    {
        return static::qb()->where($key, $operator, $value);
    }

    public static function whereIn($key, $values):QueryBuilder\QueryBuilderHandler
    {
        return static::qb()->whereIn($key, $values);
    }

    public static function count():int
    {
        return static::qb()->count();
    }

    public static function insert($data)
    {
        if (is_a($data, self::class)) {
            /** @var AbstractDataObject $data */
            $data = $data->getDBData();
        }
        return static::qb()->insert($data);
    }

    public static function update(array $data, string $where_sql, array $args = []):int
    {
        /** @var \PDOStatement $pdoStatement */
        $pdoStatement = static::whereRaw($where_sql, $args)->update($data);
        return $pdoStatement->rowCount();
    }

    public static function whereRaw(string $sql, array $args = []):QueryBuilder\QueryBuilderHandler
    {
        $qb = static::qb();
        return $qb->where($qb->raw($sql, $args));
    }

    public function getDBData():array
    {
        // http://stackoverflow.com/a/26914481/286348
        $vars = call_user_func('get_object_vars', $this);
        $result = [];
        foreach ($vars as $k => $v) {
            if (preg_match('/^__/', $k)) {
                // ignore attributes that begins with __
                continue;
            }
            $result[$k] = $v;
        }
        return $result;
    }

    public function save():int
    {
        if (!static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD_IS_AUTO_INCREMENT)) {
            throw new \Exception("for non-auto-increment PK tables, use saveInsert/saveUpdate pls");
        }
        $id_field_name = static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD);
        if (empty($this->$id_field_name)) {
            return $this->saveInsert();
        } else {
            return $this->saveUpdate();
        }
    }

    public function saveInsert():int
    {
        $inserted_id = self::insert($this);

        if (static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD_IS_AUTO_INCREMENT)) {
            assert(is_numeric($inserted_id));
            $id_field_name = static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD);
            $this->$id_field_name = $inserted_id;
        }
        return 1;
    }

    public function saveUpdate():int
    {
        $id_field_name = static::getConfig(self::CONFIG_DB_IDENTIFIER_FIELD);
        if (!isset($this->$id_field_name)) {
            throw new Exception("please have $id_field_name field set before updating");
        }
        $id_value = $this->$id_field_name;
        $data = $this->getDBData();
        if (isset($data[$id_field_name])) {
            unset($data[$id_field_name]);
        }
        return static::update($data, "`$id_field_name` = ? LIMIT 1", [$id_value]);
    }
}