<?php
namespace app\index\controller;

use app\common\model\OrderTraded;
use util\BasicData;
use util\RedisUtil;
use util\TradingUtil;

class OrderTradedController extends BaseController
{

    /**
     * 今日成交单
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = OrderTraded::where('user_id', $this->userId)
            ->where('trading_date', TradingUtil::currentTradingDate())
            ->field('direction,market,stock_code,trading_time,price,volume,traded_value,total_fee,service_fee,stamp_tax,transfer_fee,is_monthly')
            ->order('create_time desc,id DESC')
            ->select()
            ->toArray();

        foreach ($list as $key => $item) {
            $list[$key]['stock_name'] = RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] ?? '';
            $list[$key]['direction']  = BasicData::TRADE_DIRECTION_LIST[$item['direction']];
        }

        return $this->message(1, '', $list);
    }

    /**
     * 历史成交单
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

        $list = OrderTraded::where($map)
            ->field('direction,market,stock_code,trading_date,trading_time,price,volume,traded_value,total_fee,service_fee,stamp_tax,transfer_fee,is_monthly')
            ->order('id', 'DESC')
            ->paginate();

        foreach ($list as $key => $item) {
            $list[$key]['stock_name'] = RedisUtil::getStockData($item['stock_code'], $item['market'])['stock_name'] ?? '';
            $list[$key]['direction']  = BasicData::TRADE_DIRECTION_LIST[$item['direction']];
        }

        return $this->message(1, '', $list);
    }

}
