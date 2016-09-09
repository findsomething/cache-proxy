<?php
/**
 * Created by PhpStorm.
 * User: li
 * Date: 16-9-9
 * Time: 下午3:48
 */
namespace FSth\CacheProxy\Test\Business;

use FSth\CacheProxy\Business\CacheEntry;
use FSth\CacheProxy\Tests\Kit\StorageKit;

class CacheEntryTest extends \PHPUnit_Framework_TestCase
{
    private $cacheEntry;
    private $db;
    private $redis;
    private $config;
    private $tableName;
    private $resources;
    private $keys;

    public function setUp()
    {
        $this->config = include dirname(__DIR__) . "/../app/config.php";
        $this->db = StorageKit::getDb($this->config['database']);
        $this->redis = StorageKit::getRedis($this->config['redis']);
        $this->tableName = "test";
        $this->resources = array(
            array('value1' => '1', 'value2' => '2', 'value3' => '3'),
        );
        $this->keys = array(
            array('value1'),
            array('value2', 'value3'),
        );
        $this->initData();
        $this->cacheEntry = new CacheEntry($this->tableName);
        $this->cacheEntry->setDb($this->db)
            ->setRedis($this->redis)
            ->setKeys($this->keys);
    }

    public function tearDown()
    {
        $this->truncate();
    }

    public function testGet()
    {
        $value = $this->cacheEntry->get(array('value1'), array(1));
        $this->assertNotEmpty($value);
        $this->assertEquals($value['value1'], 1);
        $this->assertTrue($this->redis->exists($this->cacheEntry->getCacheKey(array('value1'), array(1))));
        $value = $this->cacheEntry->get(array('value2', 'value3'), array(2, 3));
        $this->assertNotEmpty($value);
        $this->assertEquals($value['value2'], 2);
        $this->assertTrue($this->redis->exists($this->cacheEntry->getCacheKey(array('value2', 'value3'), array(2, 3))));
        $this->cacheEntry->clear(array('value1'), array(1));
        $this->assertFalse($this->redis->exists($this->cacheEntry->getCacheKey(array('value1'), array(1))));
        $this->assertFalse($this->redis->exists($this->cacheEntry->getCacheKey(array('value2', 'value3'), array(2, 3))));
    }

    private function initData()
    {
        foreach ($this->resources as $resource) {
            $this->db->insert($this->tableName, $resource);
        }
    }

    private function truncate()
    {
        $sql = "TRUNCATE {$this->tableName}";
        $this->db->executeQuery($sql);
    }
}