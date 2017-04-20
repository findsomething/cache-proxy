<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:55
 */
namespace FSth\CacheProxy\Solid;

class SqlSolid
{
    const SELECT = "SELECT * FROM %s";
    const ADD_WHERE = "%s = ?";
    const LIMIT = " LIMIT 1";

    public static function analysis($cacheKey)
    {
        $wheres = array();
        $parseResult = KeySolid::parseCacheKey($cacheKey);
        $select = sprintf(self::SELECT, $parseResult['tableName']);
        foreach ($parseResult['keys'] as $key) {
            $wheres[] = sprintf(self::ADD_WHERE, $key);
        }
        return array($select . " WHERE " . implode(" AND ", $wheres) . self::LIMIT, $parseResult['values']);
    }
}