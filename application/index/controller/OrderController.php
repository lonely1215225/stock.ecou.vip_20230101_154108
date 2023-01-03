<?php
namespace app\index\controller;

use app\cli\sims\logic\OrderBuy;
use app\cli\sims\logic\OrderCancel;
use app\cli\sims\logic\OrderCancelMsg;
use app\cli\sims\logic\OrderSell;

use app\common\model\Order;
use util\BasicData;
use util\RedisUtil;
use util\TradingUtil;

use util\QuotationRedis;

class OrderController extends BaseController
{

    /**
     * 委托单列表接口
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = Order::where('user_id', $this->userId)
            ->where('trading_date', TradingUtil::currentTradingDate())
            ->field('id,market,direction,stock_code,price,price_type,volume,volume_success,state,cancel_state,create_time,order_sn,is_system,is_monthly,is_cash_coupon')
            ->order('create_time desc,id DESC')
            ->select()
            ->toArray();

        $showList = [];
        if (count($list)) {
            $showList = array_map(function ($item) {
                // 获取股票基础数据
                $basicData = RedisUtil::getStockData($item['stock_code'], $item['market']);

                $show['id']             = $item['id'];
                $show['market']         = $item['market'];
                $show['security_type']  = $basicData['security_type'];
                $show['stock_code']     = $item['stock_code'];
                $show['stock_name']     = $basicData['stock_name'];
                $show['direction']      = BasicData::TRADE_DIRECTION_LIST[$item['direction']];
                $show['time']           = date('H:i:s', strtotime($item['create_time']));
                $show['price']          = $item['price_type'] == PRICE_TYPE_MARKET ? '市价' : $item['price'];
                $show['volume']         = $item['volume'];
                $show['volume_success'] = $item['volume_success'];
                $show['state']          = $item['cancel_state'] == CANCEL_NONE ? BasicData::ORDER_STATE_LIST[$item['state']] : BasicData::CANCEL_STATE_LIST[$item['cancel_state']];
                $show['is_monthly']     = $item['is_monthly'];
                $show['is_cash_coupon'] = $item['is_cash_coupon'];

                // 是否可撤
                $show['cancel'] = 'no';
                if (in_array($item['state'], [ORDER_SUBMITTED, ORDER_PART_TRADED]) && $item['cancel_state'] == CANCEL_NONE && $item['is_system'] == false) {
                    $show['cancel'] = 'yes';
                }

                return $show;
            }, $list);
        }

        return $this->message(1, '', $showList);
    }
    /*委托买入*/
    public function buy()
    {
        $data = [
            'Token' => input('post.token', ''),
            'Data'  => [
                "market"        => input('post.market', '', ['trim', FILTER_SANITIZE_STRING]),
        	    "stock_code"    => input('post.stock_code', '', ['trim', FILTER_SANITIZE_STRING]),
        	    "price"         => input('post.price', '', ['trim', FILTER_SANITIZE_STRING]),
        	    "volume"        => input('post.volume', '', FILTER_SANITIZE_NUMBER_INT),
        	    "agreement"     => input('post.agreement', '', ['trim', FILTER_SANITIZE_STRING]),
        	    "is_monthly"    => input('post.is_monthly', '', ['trim', FILTER_SANITIZE_STRING]),
        	    'is_cash_coupon'=> input('post.is_cash_coupon', '', ['trim', FILTER_SANITIZE_STRING]),
            ],
        ];
        
        $result = OrderBuy::execute($data);
        if (is_array($result)) {
            // 成交
            list($orderID, $stockID, $market, $stockCode, $volume) = $result;
            $direction = TRADE_DIRECTION_BUY;
            $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            return $this->message(1, "委托买入成功", '');
        } else {
            // 系统处理失败
            return $this->message(0, $result, '');
        }
    }
    /*委托卖出*/
    public function sell()
    {
        $data = [
            'Token' => input('post.token', ''),
            'Data'  => [
                "position_id"   => input('post.position_id', '', FILTER_SANITIZE_NUMBER_INT),
        	    "price"         => input('post.price', '', ['trim', FILTER_SANITIZE_STRING]),
        	    "volume"        => input('post.volume', '', FILTER_SANITIZE_NUMBER_INT),
            ],
        ];
        
        $result = OrderSell::execute($data);
        if (is_array($result)) {
            // 成交
            list($orderID, $stockID, $market, $stockCode, $volume) = $result;
            $direction = TRADE_DIRECTION_SELL;
            $this->addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode);
            return $this->message(1, "委托卖出成功", '');
        } else {
            // 系统处理失败
            return $this->message(0, $result, '');
        }
    }
    /*撤销委托*/
    public function cancel()
    {
        $data = [
            'Token' => input('post.token', ''),
            'Data'  => [
                "order_id" => input('post.order_id', '', FILTER_SANITIZE_NUMBER_INT),
        	    "user_id"  => $this->userId,
            ],
        ];
        
        $result = OrderCancel::execute($data);
        if (is_array($result)) {
            list ($orderID) = $result;
            $cancelRet = OrderCancelMsg::execute($orderID);
            if (is_array($cancelRet)) {
                list ($userID, $code, $msg) = $cancelRet;
                return $this->message(1, $msg,'');
            }
            return $this->message(0, $result, '');
        } else {
            // 系统处理失败
            return $this->message(0, $result, '');
        }
    }
    /*加入待成交列表*/
    public function addToWaitingDealList($orderID, $volume, $direction, $market, $stockCode, $delay = true)
    {
        // 加入持仓订阅列表
        QuotationRedis::addPositionSubscribe($market, $stockCode);
        // 加入待成交列表
        $key   = 'waiting_deal_' . $market . $stockCode;
        $value = implode(',', [$orderID, $volume, $direction, $market, $stockCode]);
        RedisUtil::redis()->rPush($key, $value);
        RedisUtil::redis()->expireAt($key, RedisUtil::midnight());
    }
    /**
     * 历史委托单
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function history()
    {
        $startDate = input('start_date', '', 'filter_date');
        $endDate   = input('end_date', '', 'filter_date');

        $map[] = ['user_id', '=', $this->userId];
        if (!empty($startDate)) {
            $map[] = ['trading_date', '>=', $startDate];
        }
        if (empty($endDate)) {
            $map[] = ['trading_date', '<', TradingUtil::currentTradingDate()];
        } else {
            $map[] = ['trading_date', '<=', $endDate];
        }

        $list = Order::where($map)
            ->field('id,market,stock_code,direction,price,price_type,volume,volume_success,state,cancel_state,create_time,order_sn,is_monthly')
            ->order('id', 'DESC')
            ->paginate();

        foreach ($list as $key => $item) {
            // 获取股票基础数据
            $basicData = RedisUtil::getStockData($item['stock_code'], $item['market']);

            $list[$key]['id']             = $item['id'];
            $list[$key]['market']         = $item['market'];
            $list[$key]['security_type']  = $basicData['security_type'];
            $list[$key]['stock_code']     = $item['stock_code'];
            $list[$key]['stock_name']     = $basicData['stock_name'];
            $list[$key]['direction']      = BasicData::TRADE_DIRECTION_LIST[$item['direction']];
            $list[$key]['time']           = $item['create_time'];
            $list[$key]['price']          = $item['price_type'] == PRICE_TYPE_MARKET ? '市价' : $item['price'];
            $list[$key]['volume']         = $item['volume'];
            $list[$key]['volume_success'] = $item['volume_success'];
            $list[$key]['state']          = $item['cancel_state'] == CANCEL_NONE ? BasicData::ORDER_STATE_LIST[$item['state']] : BasicData::CANCEL_STATE_LIST[$item['cancel_state']];
        }

        return $this->message(1, '', $list);
    }

}
