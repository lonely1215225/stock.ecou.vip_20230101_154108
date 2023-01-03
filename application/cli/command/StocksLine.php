<?php
namespace app\cli\command;

use app\cli\client\CoRedisPool;
use app\cli\client\WsClient;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use util\BasicData;
use util\RedisUtil;
use util\QuotationRedis;
use util\TradingRedis;
use util\TradingUtil;
use function Swoole\Coroutine\run;
use \co\Http\Client;
/**
 * 获取所有股票的行情数据
 * -- 用于休市后更新所有股票的行情
 */
class StocksLine extends Command
{
    protected $stockClient;
    protected $stockHost     = LOCAL_STOCK_HOST;
    protected $stockPort     = LOCAL_STOCK_PORT;
    protected $stockPath     = '/api/market/getstocks';
    // 行情计数器
    protected $allStocks;
    /** @var CoRedisPool $redisPool Redis连接池 */
    protected $redisPool;

    // 是否正在运行
    protected $running = false;

    protected function configure()
    {
        $this->setName('stocksline')->setDescription('更新所有股票行情');
    }

    /**
     * 入口
     *
     * @param Input  $input
     * @param Output $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        
        $this->startClient();
    }
    /*启动行情WS客户端*/
    protected function startClient()
    {
        swoole_timer_tick(LOCAL_STOCK_TIMES*1000, function () {
            //if($this->checkTradeTime() == false) return false;
            $list = QuotationRedis::getActiveStockList();
            //print_r($list);return;
            if(empty($list)) return;
            foreach($list as $key => $item){
                if(!$item) continue;
                //$this->getStocks($this->toStrCodes($item));
                /***********************备用********************/
                $this->getMarkets($this->toStrCodes($item));
                /***********************备用********************/
            }  
        });
        \Swoole\Event::wait();
    }
    /***********************备用********************/
    protected function getMarkets($codes)
    {
        $recData = TencentMarkets($codes);
        foreach ($recData as $k => $item) {
            go(function () use ($item) {
                $this->cacheStockData($item);
            });
        }
    }
    /***********************备用********************/
    
    /*组合代码*/
    protected function toStrCodes($item)
    {
        $num = '';
        foreach ($item as $k => $v) {
            $data = explode("_", $v);
            $dian = count($item) == ($k+1) ? "" : ",";
            $num .= strtolower($data[0]) . $data[1] . $dian;
        }
        //print_r($num);return;
        return $num;
    }
    /*获取行情*/
    protected function getStocks($codes)
    {
        $this->stockClient = new Client($this->stockHost, $this->stockPort);
        $this->stockClient->setHeaders([
            'User-Agent'      => 'Chrome/49.0.2587.3',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
            'Access-Token'    => LOCAL_TRADING_TOKEN,
            'Client-Host'     => RedisUtil::getClientHost(),
        ]);
        $this->stockClient->post($this->stockPath,array('codes'=>$codes));
        $this->onMessage($this->stockClient->body);
        $this->stockClient->close();
    }
    /**
     * 处理行情客户端接收到的消息
     *
     * @param string $data
     */
    protected function onMessage($data)
    {
        //print_r($data);return;
        if($data){
            $recData = json_decode($data, true);
            $stockData = isset($recData['data']) ? $recData['data'] : [];
            foreach ($stockData as $k => $item) {
                //print_r($item[0]);return;
                go(function () use ($item) {
                    $this->cacheStockData($item);
                });
                //$this->cacheStockData($json);
            } 
            
        }
        
    }
    /**
     * 缓存行情
     *
     * @param $stockData
     *
     * @return bool
     */
    protected function cacheStockData($stockData)
    {
        // 每次创建一个连接
        $redis = new \Swoole\Coroutine\Redis();
        $redis->connect(REDIS_SERVER_IP, REDIS_SERVER_PORT);
        $key   = 'stock_hq_' . strtoupper($stockData[0]) . $stockData[2];
        $redis->hMSet($key, $stockData);
        $this->output->writeln(date('Y-m-d H:i:s').$key.'行情更新完毕');
        //return true;
        // 使用连接池
        /*$redis = $this->redisPool->get();
        $key   = 'stock_hq_' . $stockData['market'] . $stockData['code'];
        if ($redis->exists($key)) $redis->del($key);
        $redis->hMSet($key, $stockData);
        $this->redisPool->put($redis);
        $this->output->writeln(date('Y-m-d H:i:s').$key.'更新行情完毕');
        return true;*/
    }
    /**
     * 检查是否在交易时间内
     * @return bool
     */
    public function checkTradeTime(){
        //检测交易日
        $tradingDate = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($tradingDate)) return false;
        //检测交易时间
        $tradingTime = TradingUtil::isInTradingTime();
        if(!$tradingTime) return false;
        // 仅在 09:26 ~ 11:30 及 13:00 ~ 15:00 缓存行情
        //$nowHi = intval(date('Hi'));
        //if (!(($nowHi >= 929 && $nowHi <= 1130) || ($nowHi >= 1300 && $nowHi <= 1500))) return false;
        return true;
    }
}

