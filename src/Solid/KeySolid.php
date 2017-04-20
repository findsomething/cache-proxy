<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:28
 */
namespace FSth\CacheProxy\Solid;

class KeySolid
{
    /**
     * @param $name
     * @param array $keys
     * @param string $prefix
     * @return string
     */
    public static function getTemplateValue($name, array $keys, $prefix = '')
    {
        $fills = array_fill(0, count($keys), "%s");
        $value = $name . ":" . self::getTemplateIndex($keys) . ":" . implode("_", $fills);
        if (!empty($prefix)) {
            $value = $prefix . ":" . $value;
        }
        return $value;
    }

    public static function getTemplateIndex(array $keys)
    {
        return implode("_", $keys);
    }
    

    public static function getCacheKey($cacheKeyTemplate, array $values)
    {
        return vsprintf($cacheKeyTemplate, $values);
    }

    public static function parseCacheKey($cacheKey)
    {
        $explodeValues = explode(":", $cacheKey);
        $prefix = "";
        if (count($explodeValues) == 4) {
            list($prefix, $tableName, $keyString, $keyValues) = $explodeValues;
        } else {
            list($tableName, $keyString, $keyValues) = $explodeValues;
        }
        return array(
            'prefix' => $prefix,
            'tableName' => $tableName,
            'keys' => explode("_", $keyString),
            'values' => explode("_", $keyValues)
        );
    }

    public static function parseUniKey($uniKey)
    {
        return explode("_", $uniKey);
    }
}