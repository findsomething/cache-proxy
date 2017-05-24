<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:20
 */
namespace FSth\CacheProxy\Business;

use FSth\CacheProxy\Exception\RuntimeException;
use FSth\CacheProxy\Solid\KeySolid;
use FSth\CacheProxy\Solid\SqlSolid;

class CacheEntry
{
    private $db;
    private $redis;
    private $name;
    private $mainKey;

    private $expire = 86400;

    private $cacheKeyTemplates = array();
    private $hashTable = true;
    private $prefix;

    /**
     * CacheEntry constructor.
     * @param $name
     * @param string $mainKey
     * @param bool $hashTable the dataType store in redis
     * @param string $prefix
     */
    public function __construct($name, $mainKey = 'id', $hashTable = true, $prefix = '')
    {
        $this->name = $name;
        $this->mainKey = $mainKey;
        $this->prefix = $prefix;
        $this->hashTable = $hashTable;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setDb($db)
    {
        $this->db = $db;
        return $this;
    }

    public function setRedis($redis)
    {
        $this->redis = $redis;
        return $this;
    }

    public function setExpire($expire)
    {
        $this->expire = $expire;
        return $this;
    }

    public function bindKeys($keyArrays)
    {
        $this->bindKeyToTemplates(array($this->mainKey));
        foreach ($keyArrays as $keyArr) {
            $this->bindKeyToTemplates($keyArr);
        }
        return $this;
    }

    public function get($keys, $values)
    {
        $cacheKey = $this->getCacheKey($keys, $values);
        $resources = $this->getFromRedis($cacheKey);
        if (!empty($resources)) {
            return $resources;
        }
        $resources = $this->getFromDb($cacheKey);
        $this->updateRedis($cacheKey, $resources);
        return $resources;
    }

    public function clear($keys, $values)
    {
        $resource = $this->get($keys, $values);
        if (!empty($resource)) {
            $this->clearWithResource($resource);
        }
    }

    public function getCacheKey($keys, $values)
    {
        $uniKey = KeySolid::getTemplateIndex($keys);
        if (empty($this->cacheKeyTemplates[$uniKey])) {
            throw new RuntimeException("没有相对应的缓存Key" . var_export($keys));
        }
        return KeySolid::getCacheKey($this->cacheKeyTemplates[$uniKey], $values);
    }

    protected function clearWithResource($resource)
    {
        foreach ($this->cacheKeyTemplates as $uniKey => $cacheKeyTemplate) {
            try {
                $values = $this->getValuesByUniKey($uniKey, $resource);
                $cacheKey = KeySolid::getCacheKey($cacheKeyTemplate, $values);
                if ($this->redis->exists($cacheKey)) {
                    $this->redis->del($cacheKey);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    protected function getValuesByUniKey($uniKey, $resource)
    {
        $values = array();
        $keys = KeySolid::parseUniKey($uniKey);
        foreach ($keys as $key) {
            if (!array_key_exists($key, $resource)) {
                throw new RuntimeException("解析uniKey{$uniKey}失败");
            }
            $values[] = $resource[$key];
        }
        return $values;
    }

    protected function getFromRedis($cacheKey)
    {
        if ($this->hashTable) {
            return $this->redis->hGetAll($cacheKey);
        }
        return json_decode($this->redis->get($cacheKey), true);
    }

    protected function updateRedis($cacheKey, $resource)
    {
        if (!(!empty($resource) && is_array($resource))) {
            return false;
        }
        if (!$this->hashTable) {
            return $this->redis->setex($cacheKey, $this->expire, json_encode($resource));
        }
        $this->redis->hMset($cacheKey, $resource);
        $this->redis->expire($cacheKey, $this->expire);
        return true;
    }

    protected function getFromDb($cacheKey)
    {
        list($sql, $values) = SqlSolid::analysis($cacheKey);
        $resource = $this->db->fetchAssoc($sql, $values);
        if (empty($resource)) {
            return array();
        }
        return $resource;
    }

    protected function bindKeyToTemplates(array $keys)
    {
        $templateIndex = KeySolid::getTemplateIndex($keys);
        $templateValue = KeySolid::getTemplateValue($this->name, $keys, $this->prefix);
        $this->cacheKeyTemplates[$templateIndex] = $templateValue;
    }
}