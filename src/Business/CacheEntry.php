<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午2:20
 */
namespace FSth\CacheProxy\Business;

use Doctrine\DBAL\Connection;
use FSth\CacheProxy\Exception\RuntimeException;
use FSth\CacheProxy\Facade\KeyFacade;
use FSth\CacheProxy\Facade\SqlFacade;

class CacheEntry
{
    private $db;
    private $redis;
    private $name;
    private $mainKey;
    private $logger;

    private $cacheKeyTemplates = array();

    public function __construct($name, $mainKey = 'id')
    {
        $this->name = $name;
        $this->mainKey = $mainKey;
        $this->cacheKeyTemplates[] = KeyFacade::getCacheKeyTemplate($this->name, array($mainKey));
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDb(Connection $db)
    {
        $this->db = $db;
        return $this;
    }

    public function setRedis(\Redis $redis)
    {
        $this->redis = $redis;
        return $this;
    }

    public function setKeys($keys)
    {
        foreach ($keys as $keyArr) {
            $this->cacheKeyTemplates[KeyFacade::getUniKey($keyArr)] = KeyFacade::getCacheKeyTemplate($this->name, $keyArr);
        }
        return $this;
    }

    public function get($keys, $values)
    {
        $cacheKey = $this->getCacheKey($keys, $values);
        if ($this->redis->exists($cacheKey)) {
            return $this->getFromRedis($cacheKey);
        }
        return $this->getFromDb($cacheKey);
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
        $uniKey = KeyFacade::getUniKey($keys);
        if (empty($this->cacheKeyTemplates[$uniKey])) {
            throw new RuntimeException("没有相对应的缓存Key" . var_export($keys));
        }
        return KeyFacade::getCacheKey($this->cacheKeyTemplates[$uniKey], $values);
    }

    protected function clearWithResource($resource)
    {
        foreach ($this->cacheKeyTemplates as $uniKey => $cacheKeyTemplate) {
            try {
                $values = $this->getValuesByUniKey($uniKey, $resource);
                $cacheKey = KeyFacade::getCacheKey($cacheKeyTemplate, $values);
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
        $keys = KeyFacade::parseUniKey($uniKey);
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
        return $this->redis->hGetAll($cacheKey);
    }

    protected function getFromDb($cacheKey)
    {
        list($sql, $values) = SqlFacade::toSql($cacheKey);
        $resource = $this->db->fetchAssoc($sql, $values) ?: null;
        if (!empty($resource)) {
            $this->redis->hMset($cacheKey, $resource);
        }
        return $resource;
    }
}