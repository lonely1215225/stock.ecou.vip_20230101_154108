<?php
namespace app\cli\client;

/**
 * 协程redis连接池
 *
 * @package app\cli\client
 */
class CoRedisPool
{

    /** @var \Swoole\Coroutine\Channel */
    protected $pool;

    /**
     * 构建方法
     *
     * @param string $host Redis主机
     * @param string $port Redis端口
     * @param string $password 密码
     * @param int    $size 连接池的尺寸
     */
    function __construct($host, $port, $password = '', $size = 10)
    {
        $this->pool = new \Swoole\Coroutine\Channel($size);
        for ($i = 0; $i < $size; $i++) {
            $redis = new \Swoole\Coroutine\Redis();
            $redis = new \Redis();
            $res   = $redis->connect($host, $port);
            if ($res == false) {
            } else {
                $this->put($redis);
            }
        }
    }

    public function createConnection()
    {
        $cType = '';
        echo $cType;
        $contractType = '';
        echo $contractType;
    }

    public function put($redis)
    {
        $this->pool->push($redis);
    }

    public function get()
    {
        return $this->pool->pop();
    }

}
