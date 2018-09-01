<?php

namespace tests\cases;

use tests\cases\base;

class demoTest extends base
{
    /**
     * 执行测试的前置操作
     *
     * 可以进行前置检查，或者数据填充
     */
    protected function setUp()
    {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
                'The MySQLi 扩展不可用。'
            );
        }
    }

    /**
     * 执行测试的后置操作
     *
     * 可以进行数据清理，或者资源释放
     */
    protected function tearDown()
    {

    }

    public function testTrue()
    {
        $this->assertTrue(true, '提示:这应该已经是能正常工作的');
        $this->assertArrayHasKey(1, [1=>1]);
    }

    public function testArrayPushAndPop()
    {
        $stack = array();
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }

    public function testEmpty()
    {
        $stack = array();
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * 先执行 testEmpty 的测试，并将测试结果传递给 testPush
     *
     * @depends testEmpty
     */
    public function testPush(array $stack)
    {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }

    /**
     * 先执行 testPush 的测试，并将测试结果传递给 testPop
     * @depends testPush
     */
    public function testPop(array $stack)
    {
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }
}