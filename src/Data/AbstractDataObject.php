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
use Foldy\Utils;
use Pixie\QueryBuilder;

abstract class AbstractDataObject
{
    /**
     * @var DIContainer $di
     */
    static $di;

    public static function dbTableName():string
    {
        return Utils::camelcaseToUnderlineJoined(Utils::getClassBaseName(static::class));
    }

    public static function dbConfigName():string
    {
        return DB::DEFAULT_DB;
    }

    public static function dbIdentifierField():string
    {
        return "id";
    }

    public static function creationArgsForDBFetch():array
    {
        return [];
    }

    public static function qb():QueryBuilder\QueryBuilderHandler
    {
        $db_class = DB::getSharedDI()->get(Constants::DI_KEY_CLASS_DB) ?? DB::class;
        /**
         * @var QueryBuilder\QueryBuilderHandler $result
         */
        $result = new $db_class(self::dbConfigName());
        /** @noinspection PhpParamsInspection */
        $result = $result->table(static::dbTableName());
        $result = $result->asObject(static::class, static::creationArgsForDBFetch());
        return $result;
    }

    /**
     * @param $id_value
     * @return null|static
     */
    public static function find($id_value)
    {
        return static::qb()->find($id_value, static::dbIdentifierField());
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
        return static::qb()->insert($data);
    }

    public static function insertIgnore($data)
    {
        return static::qb()->insertIgnore($data);
    }

    public static function whereRaw($sql, array $args = []):QueryBuilder\QueryBuilderHandler
    {
        $qb = static::qb();
        return $qb->where($qb->raw($sql, $args));
    }
}