<?php
namespace app\cli\command;

use app\cli\client\CoRedisPool;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use \Swoole\WebSocket\Frame;
use \co\Http\Client;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CliTest extends Command
{
    protected $localWsClient;
    protected $localWsHost      = '127.0.0.1';
    protected $localWsPort      = 30200;
    protected $localWsName      = '本地客户端';
    protected $localWsLinkDebug = true;
    
    protected function configure()
    {
        $this->setName('cli_test')->setDescription('测试');
    }

    protected function execute(Input $input, Output $output)
    {
        run(function () {
            $client = new Client($this->localWsHost, $this->localWsPort);
            $ret = $client->upgrade('/');
            $pingFrame = new Frame;
            $pingFrame->opcode = WEBSOCKET_OPCODE_PING;
            if ($ret) {
                $client->push($pingFrame);
                $pongFrame = $client->recv();
                var_dump($pongFrame->opcode === WEBSOCKET_OPCODE_PONG);
            }
        });
    }
    /**
     * 启动本地WS客户端
     *
     * @throws \Exception
     */
    public function startLocalClient()
    {
        // 本地客户端，自动重连，定时心跳
        $this->localWsClient = new WsClient($this->localWsHost, $this->localWsPort, $this->localWsName, $this->localWsLinkDebug);
        $this->localWsClient->setReconnect();
        $this->localWsClient->setHeartbeat(['Key' => 'Heartbeat']);

        // onMessage
        $this->localWsClient->on('message', function (\Swoole\Http\Client $client, \swoole_websocket_frame $frame) {
        });

        $this->localWsClient->connect();
    }
    protected function redisPool()
    {
        go(function () {
            $redisPool = new CoRedisPool('127.0.0.1', 6379);
            $redis     = $redisPool->get();
            $redis->set('abc', uniqid());
            $x = $redis->get('abc');
            $redisPool->put($redis);
            dump($x);
        });
    }
    protected function getMarket()
    {
       run(function() {
            $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
            $post_data   = array(
                'username' => self::$username,
                'password' => self::$password,
            );
            $postdata = http_build_query($post_data);
            $ch       = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, self::$url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);//返回相应的标识
            curl_close($ch);
            var_dump($result);
            return $result;
        });
    }
}

