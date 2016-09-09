<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:28
 */
namespace FSth\CacheProxy\Facade;

class KeyFacade
{
    /*
     * $keys => array( 'key1', 'key2')
     */
    public static function getCacheKeyTemplate($name, array $keys)
    {
        $fills = array_fill(0, count($keys), "%s");
        return $name . ":" . self::getUniKey($keys) . ":" . implode("_", $fills);
    }

    public static function getCacheKey($cacheKeyTemplate, array $values)
    {
        return vsprintf($cacheKeyTemplate, $values);
    }

    public static function getUniKey(array $keys)
    {
        return implode("_", $keys);
    }

    public static function parseCacheKey($cacheKey)
    {
        list($table, $keyString, $keyValues) = explode(":", $cacheKey);
        return array($table, explode("_", $keyString), explode("_", $keyValues));
    }

    public static function parseUniKey($uniKey)
    {
        return explode("_", $uniKey);
    }
}