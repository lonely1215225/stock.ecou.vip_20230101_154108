<?php
namespace app\cli\client;

use think\console\Output;

/**
 * WebSocket 客户端
 * -- 基于Swoole
 * -- 仅限CLI
 *
 * @package app\cli\client
 */
class WsClient
{

    /** @var \Swoole\Http\Client $client 客户端实例 */
    protected $client;

    // 主机地址
    protected $host;

    // 主机端口号
    protected $port;

    // 客户端名名称
    protected $name;

    // 连接状态，默认未连接
    protected $isConnected = false;

    // 是否输出连接状态信息，默认不输出
    protected $linkDebug = false;

    // 是否自动重连，默认不自动重连
    protected $reconnect = false;

    // 自动重连延时（毫秒）
    protected $reconnectDelay = 1000;

    // 自动重连锁
    protected $lockReconnect = false;

    // 心跳包
    protected $heartbeat = [];

    // 心跳包时间间隔，毫秒，默认55000（55秒）
    protected $heartbeatTime = 55000;

    // WebSocket回调函数列表
    protected $callbacks = [];

    // 定时器列表
    protected $interval = [];

    /** @var Output $output 命令行输出 */
    protected $output;

    /**
     * 构造函数
     *
     * @param string $host 服务器地址
     * @param int    $port 端口号
     * @param string $name 客户端名名称
     * @param bool   $linkDebug 是否输出连接状态信息，默认不输出
     */
    public function __construct($host, $port, $name = '', $linkDebug = false)
    {
        $this->host      = $host;
        $this->port      = $port;
        $this->name      = empty($name) ? uniqid() : $name;
        $this->linkDebug = $linkDebug;
        $this->output    = new Output();
    }

    /**
     * 定时心跳配置
     *
     * @param array $heartbeat 心跳包内容
     * @param int   $ms 心跳间隔，毫秒，默认55000
     */
    public function setHeartbeat($heartbeat, $ms = 55000)
    {
        $this->heartbeat     = $heartbeat;
        $this->heartbeatTime = $ms;
    }

    /**
     * 重连配置
     *
     * @param bool $reconnect 是否自动重连
     * @param int  $ms 重连延时时间
     */
    public function setReconnect($reconnect = true, $ms = 1000)
    {
        $this->reconnect      = $reconnect;
        $this->reconnectDelay = $ms;
    }

    /**
     * 连接WebSocket客户端
     *
     * @throws \Exception
     */
    public function connect()
    {
        $this->client = new \Swoole\Http\Client($this->host, $this->port);

        // 用户没有设置onMessage
        if ($this->getCallback('message') === false) {
            throw new \Exception('no message event callback.');
        }

        // 注册回调函数
        $this->client->on('connect', [$this, 'onOpen']);
        $this->client->on('message', [$this, 'onMessage']);
        //$this->client->on('error', [$this, 'onError']);
        $this->client->on('close', [$this, 'onClose']);

        // 升级连接为WebSocket连接
        $this->client->upgrade('/', function (\Swoole\Http\Client $client) {
        });
    }

    /**
     * 重新连接WebSocket客户端
     */
    public function reConnect()
    {
        if ($this->reconnect && $this->lockReconnect == false) {
            // 重连中
            $this->lockReconnect = true;
            // 连接调试信息
            $this->linkDebug && $this->writeLinkDebug($this->reconnectDelay / 1000 . '秒后重连');

            $this->timeAfter($this->reconnectDelay, function () {
                // 解锁重连
                $this->lockReconnect = false;

                // 连接调试信息
                $this->linkDebug && $this->writeLinkDebug('执行重连');

                // 执行重连
                $this->connect();
            });
        }
    }

    /**
     * 向上游发送消息
     *
     * @param string $data
     */
    public function send($data)
    {
        try {
            if ($this->isConnected) {
                $this->client->push($data);
            }
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    /**
     * 设置客户端的回调函数
     *
     * @param string   $event
     * @param callable $callable
     */
    public function on($event, $callable)
    {
        if (in_array($event, ['open', 'message', 'close', 'error'])) {
            $this->callbacks[$event] = $callable;
        }
    }

    /**
     * 获取回调函数
     *
     * @param $event
     *
     * @return callable|false
     */
    public function getCallback($event)
    {
        return isset($this->callbacks[$event]) ? $this->callbacks[$event] : false;
    }

    /**
     * WebSocket onOpen
     *
     * @param \Swoole\Http\Client $client
     */
    public function onOpen(\Swoole\Http\Client $client)
    {
        try {
            $this->isConnected = true;

            // 连接调试信息
            $this->linkDebug && $this->writeLinkDebug('连接成功');

            // 发送心跳
            if (count($this->heartbeat)) {
                $this->timeTick($this->heartbeatTime, function () {
                    $this->client->push(json_encode($this->heartbeat));
                });
            }

            if ($callable = $this->getCallback('open')) {
                call_user_func($callable, $client);
            }
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    /**
     * WebSocket onMessage
     *
     * @param \Swoole\Http\Client     $client
     * @param \swoole_websocket_frame $frame
     */
    public function onMessage(\Swoole\Http\Client $client, \swoole_websocket_frame $frame)
    {
        try {
            if ($callable = $this->getCallback('message')) {
                call_user_func_array($callable, [$client, $frame]);
            }
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    /**
     * WebSocket onClose
     *
     * @param \Swoole\Http\Client $client
     */
    public function onClose(\Swoole\Http\Client $client)
    {
        try {
            // 连接调试信息
            $this->linkDebug && $this->writeLinkDebug('连接关闭');

            // 回调
            if ($callable = $this->getCallback('close')) {
                call_user_func($callable, $client);
            }

            // 关闭当前连接
            $this->close();

        } catch (\Exception $e) {
            $this->writeTrace($e);
        } finally {
            // 重连
            $this->reconnect();
        }
    }

    /**
     * WebSocket onError
     *
     * @param \Swoole\Http\Client $client
     */
    public function onError(\Swoole\Http\Client $client)
    {
        try {
            // 连接调试信息
            $this->linkDebug && $this->writeLinkDebug('连接错误(' . $this->client->errCode . ')');

            // 回调
            if ($callable = $this->getCallback('error')) {
                call_user_func($callable, $client);
            }

            // 关闭当前连接
            $this->close();

        } catch (\Exception $e) {
            $this->writeTrace($e);
        } finally {
            // 重连
            $this->reconnect();
        }
    }

    /**
     * 间隔时钟定时器
     *
     * @param int      $ms 毫秒
     * @param callable $callable 回调函数
     */
    public function timeTick($ms, $callable)
    {
        try {
            $this->interval[] = swoole_timer_tick($ms, $callable);
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    /**
     * 一次性定时器
     *
     * @param int      $ms
     * @param callable $callable
     */
    public function timeAfter($ms, $callable)
    {
        try {
            swoole_timer_after($ms, $callable);
        } catch (\Exception $e) {
            $this->writeTrace($e);
        }
    }

    /**
     * 清除所有定时器
     */
    public function clearAllTimer()
    {
        if (count($this->interval)) {
            foreach ($this->interval as $int) {
                try {
                    swoole_timer_clear($int);
                } catch (\Exception $e) {
                    $this->writeTrace($e);
                }
            }
            $this->interval = [];
        }
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        try {
            if ($this->isConnected) {
                $this->isConnected = false;
                $this->clearAllTimer();
                if ($this->client instanceof \Swoole\Http\Client) {
                    $this->client->close();
                }
                $this->client = null;
            }
        } catch (\Exception $e) {
            $this->writeTrace($e);
        } catch (\Throwable $e) {
        }
    }

    /**
     * 输出异常Trace信息
     *
     * @param \Exception $e
     */
    public function writeTrace(\Exception $e)
    {
        $this->output->writeln($e->getFile());
        $this->output->writeln($e->getLine());
        $this->output->writeln($e->getMessage());
        $this->output->writeln($e->getTraceAsString());
    }

    /**
     * 输出连接调试信息
     *
     * @param $msg
     */
    public function writeLinkDebug($msg)
    {
        $msg = date('Y-m-d H:i:s') . ' <' . $this->name . '> ' . $msg;
        $this->linkDebug && $this->output->writeln($msg);
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        $this->close();
    }

}