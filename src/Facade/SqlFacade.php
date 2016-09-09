<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:55
 */
namespace FSth\CacheProxy\Facade;

class SqlFacade
{
    const SELECT = "SELECT * FROM %s";
    const ADD_WHERE = "%s = ?";
    const LIMIT = " LIMIT 1";

    public static function toSql($cacheKey)
    {
        $wheres = array();
        list($table, $keys, $values) = KeyFacade::parseCacheKey($cacheKey);
        $select = sprintf(self::SELECT, $table);
        foreach ($keys as $key) {
            $wheres[] = sprintf(self::ADD_WHERE, $key);
        }
        return array($select . " WHERE " . implode(" AND ", $wheres) . self::LIMIT, $values);
    }
}