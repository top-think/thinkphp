<?php

class redisTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped(
                'redis扩展不可用，跳过测试'
            );
        }
    }

    function testSet()
    {
        $key = time();
        S($key, $key);
        $this->assertEquals($key, S($key));

        $cache = \Think\Cache::getInstance('redis');
        $this->assertEquals($key, $cache->get($key));

        $cache = \Think\Cache::getInstance('redisd');
        $this->assertEquals($key, $cache->get($key));

        return $key;
    }

    /**
     * @depends testSet
     * @return int
     */
    function testDel($key)
    {
        S($key, NULL);

        $cache = \Think\Cache::getInstance('redis');
        $this->assertFalse($cache->get($key));

        $cache = \Think\Cache::getInstance('redisd');
        $this->assertFalse($cache->get($key));

        return $key;
    }

    /**
     * @depends testSet
     */
    function testRedisd($key)
    {
        $cache = \Think\Cache::getInstance('redisd');
        $cache->set($key, $key);
        $this->assertEquals($key, $cache->get($key));
        $cache->rm($key);
        $this->assertFalse($cache->get($key));

        $cache->lpush($key, $key);
        $this->assertEquals($key, $cache->rpop($key));
    }

    function testGzcompress()
    {
        $data = 'cat u gzcompress me?';
        $gzed = gzcompress($data);

        S("testGzcompress", $gzed);

        $this->assertEquals($gzed, S("testGzcompress"));
        $this->assertEquals($data, gzuncompress($gzed));
    }

    function testModelCache()
    {
        $mysql = (new \Think\Model('mysql.user'))->find();
        $cache = 'phpunit_'.__FUNCTION__;
        S($cache, NULL);

        //从缓存中读取结果
        $cache1 = (new \Think\Model('mysql.user'))->cache($cache, 1)->find();
        $cache2 = (new \Think\Model('mysql.user'))->cache($cache, 1)->find();

        $this->assertEquals($mysql, $cache1);
        $this->assertEquals($cache1, $cache2);
    }
}