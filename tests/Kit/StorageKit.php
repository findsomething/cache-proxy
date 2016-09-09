<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午3:51
 */
namespace FSth\CacheProxy\Tests\Kit;

use Doctrine\DBAL\DriverManager;

class StorageKit
{
    public static function getDb($config)
    {
        return DriverManager::getConnection(array(
            'dbname' => $config['name'],
            'user' => $config['user'],
            'password' => $config['password'],
            'host' => $config['host'],
            'port' => $config['port'],
            'driver' => $config['driver'],
            'charset' => $config['charset'],
        ));
    }

    public static function getRedis($config)
    {
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], $config['timeout']);
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        return $redis;
    }
}