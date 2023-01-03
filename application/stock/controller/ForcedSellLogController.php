<?php
namespace app\stock\controller;

use app\common\model\ForcedSellLog;
use app\common\model\OrderPosition;
use util\BasicData;
use util\RedisUtil;

class ForcedSellLogController extends BaseController
{

    /**
     * 强平日志
     *
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $map = [];
        // 获取查询提交数据
        $data['mobile']       = input('mobile', '', FILTER_SANITIZE_NUMBER_INT);
        $data['trading_date'] = input('trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['position_id']  = input('position_id', '', FILTER_SANITIZE_NUMBER_INT);
        if ($data['mobile']) {
            $map[] = ['u.mobile', '=', $data['mobile']];
        }
        if ($data['trading_date']) {
            $map[] = ['fsl.trading_date', '=', $data['trading_date']];
        }
        if ($data['position_id']) {
            $map[] = ['fsl.position_id', '=', $data['position_id']];
        }

        $list = ForcedSellLog::alias('fsl')
            ->field(array(
                'fsl.trading_date', 'fsl.trigger_time', 'fsl.trigger_type', 'fsl.user_id',
                'fsl.strategy_balance', 'fsl.frozen', 'fsl.strategy', 'fsl.position_id',
                'fsl.price', 'fsl.additional_deposit', 'fsl.stock', 'fsl.stock_value',
                'fsl.volume_position', 'fsl.volume_for_sell', 'fsl.stop_loss_price',
                'fsl.target_position_id', 'fsl.target_stock', 'fsl.sell_volume',
                'fsl.sell_order', 'fsl.order_id', 'fsl.position_id', 'u.mobile', 'u.real_name',
            ))->where($map)->join(['__USER__' => 'u'], 'u.id=fsl.user_id')->order('fsl.trigger_time DESC')
            ->paginate(15, false, ['query' => $this->request->param()]);
        // 从缓存中获取股票详情
        $stockInfo = $targetStock = [];
        if ($list) {
            foreach ($list as &$item) {
                $market                             = substr($item['stock'], 0, 2);
                $code                               = substr($item['stock'], 2);
                $tMarket                            = substr($item['target_stock'], 0, 2);
                $tCode                              = substr($item['target_stock'], 2);
                $stockInfo[$item['stock']]          = RedisUtil::getStockData($code, $market);
                $targetStock[$item['target_stock']] = RedisUtil::getStockData($tCode, $tMarket);
                $posiotion = OrderPosition::where("id={$item['position_id']}")->field('is_monthly,monthly_expire_date')->find();
                if($posiotion) {
                    $posiotionData = $posiotion ? $posiotion->toArray() : '';
                    $item['monthly_expire_date'] = $posiotionData['is_monthly'] ? $posiotionData['monthly_expire_date'] : '';
                } else {
                    $item['monthly_expire_date'] = '';
                }
            }
        }

        return $list ? $this->message(1, '', ['list' => $list, 'stockInfo' => $stockInfo, 'targetStock' => $targetStock]) : $this->message(0, '');
    }

    /**
     * 强平类型列表
     *
     * @return \think\response\Json
     */
    public function forced_sell_type_list()
    {
        return $this->message(1, '', BasicData::FORCED_SELL_TYPE_LIST);
    }

    /**
     * 强平顺序
     *
     * @return \think\response\Json
     */
    public function forced_sell_order_list()
    {
        return $this->message(1, '', BasicData::FORCED_SELL_ORDER_LIST);
    }

}
