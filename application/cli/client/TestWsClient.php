<?php
namespace app\cli\client;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class TestWsClient extends Command
{

    /** @var  WsClient $wsClient */
    protected $wsClient;
    protected $host = '127.0.0.1';
    protected $port = 9502;
    //protected $host = '47.114.91.240';
    //protected $port = 21280;

    protected static $c = 0;

    protected function configure()
    {
        $this->setName('test_ws_client')->setDescription('测试');
    }

    /**
     * @param Input  $input
     * @param Output $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $server = new \Swoole\Websocket\Server('127.0.0.1', 9502);

        $server->on('start', function ($server) {
            echo "TCP Server is started at tcp://127.0.0.1:9503\n";
        });
        
        $server->on('connect', function ($server, $fd){
            echo "connection open: {$fd}\n";
        });
        
        $server->on('receive', function ($server, $fd, $reactor_id, $data) {
            $server->send($fd, "Swoole: {$data}");
        });
        
        $server->on('close', function ($server, $fd) {
            echo "connection close: {$fd}\n";
        });
        
        $server->start();
    }

}
