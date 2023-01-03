<?php
namespace app\index\controller;

use app\common\model\Condition;
use app\common\model\OrderPosition;
use util\BasicData;
use util\ConditionRedis;
use util\QuotationRedis;
use util\RedisUtil;
use util\TradingRedis;
use util\TradingUtil;

class ConditionController extends BaseController
{

    /**
     * 条件单列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $listType = input('type', 'ing', FILTER_SANITIZE_STRING);
        $listType = $listType != 'ing' ? 'history' : 'ing';
        $field    = 'id,order_position_id,state,create_time,trigger_compare,trigger_price,price,price_type,volume,order_id,direction,trigger_time,market,stock_code,trading_date,remark';

        if ($listType == 'ing') {
            $list = Condition::where('user_id', $this->userId)
                ->where('state', CONDITION_STATE_ING)
                ->field($field)
                ->order('id', 'DESC')
                ->limit(20)
                ->select()
                ->toArray();
        } else {
            $list = Condition::where('user_id', $this->userId)
                ->where('state', '<>', CONDITION_STATE_ING)
                ->order('id', 'DESC')
                ->field($field)
                ->order('id', 'DESC')
                ->limit(20)
                ->select()
                ->toArray();
        }

        if (count($list)) {
            foreach ($list as $id => $item) {
                // 行情
                $quotation = RedisUtil::getStockData($item['stock_code'], $item['market']);
                // 股票名称
                $list[$id]['stock_name'] = $quotation['stock_name'];
                // 状态
                $list[$id]['state'] = BasicData::CONDITION_STATE_LIST[$item['state']];
                // 买卖
                $list[$id]['direction'] = BasicData::TRADE_DIRECTION_LIST[$item['direction']];
                // 价格类型
                $list[$id]['price_type'] = BasicData::PRICE_TYPE_LIST[$item['price_type']];
                // 比较条件
                $list[$id]['trigger_compare'] = BasicData::CONDITION_COMPARE_LIST[$item['trigger_compare']];
            }
        }

        return $this->message(1, '', $list);
    }

    /**
     * 创建条件单记录
     *
     * @return \think\response\Json
     */
    public function create()
    {
        // 获取表单提交数据
        $data['stock_code']        = input('stock_code', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['market']            = input('market', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['trigger_compare']   = input('trigger_compare', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['trigger_price']     = input('trigger_price', 0, ['filter_float', 'abs']);
        $data['direction']         = input('direction', '', [FILTER_SANITIZE_STRING, 'trim']);
        $data['volume']            = input('volume', 0, FILTER_SANITIZE_NUMBER_INT);
        $data['price']             = 0;
        $data['price_type']        = PRICE_TYPE_MARKET;
        $data['state']             = CONDITION_STATE_ING;
        $data['user_id']           = $this->userId;
        $data['order_position_id'] = input('order_position_id', 0, FILTER_SANITIZE_NUMBER_INT);

        // 提交数据校验
        $result = $this->validate($data, 'Condition.create');
        if ($result !== true) return $this->message(0, $result);

        // 从缓存中获取股票id
        $stockInfo        = RedisUtil::getStockData($data['stock_code'], $data['market']);
        $data['stock_id'] = isset($stockInfo) ? $stockInfo['stock_id'] : 0;

        // 检测交易日
        $data['trading_date'] = TradingUtil::currentTradingDate();
        if (!TradingRedis::isTradingDate($data['trading_date'])) return $this->message(0, '当前不是交易日');

        // 买入：触发价，不能超过涨跌停禁买线；持仓ID设置为0
        if ($data['direction'] == TRADE_DIRECTION_BUY) {
            $quotation = RedisUtil::getQuotationData($data['stock_code'], $data['market']);
            if (TradingUtil::isOverBuyLimitLine($data['trigger_price'], $quotation['Close'])) return $this->message(0, '触发价超出涨跌幅禁买线');

            $data['order_position_id'] = 0;
        }

        // 卖出：触发价，不能超过涨停跌停
        if ($data['direction'] == TRADE_DIRECTION_SELL) {
            $limitRet = TradingUtil::checkLimitUpDown($data['trigger_price'], $data['market'], $data['stock_code']);
            if ($limitRet !== true) return $this->message(0, $limitRet);
        }

        // 市价单：委托价为0
        if ($data['price_type'] == PRICE_TYPE_MARKET) {
            $data['price'] = 0;
        }

        // 限价单：委托价合法性检测
        if ($data['price_type'] == PRICE_TYPE_LIMIT) {
            // 委托价必须大于0
            if ($data['price'] <= 0) return $this->message(0, '委托价必须大于0');

            // 买入/卖出：检测【委托价】是否超过【涨停】或【跌停】
            $limitRet = TradingUtil::checkLimitUpDown($data['price'], $data['market'], $data['stock_code']);
            if ($limitRet !== true) return $this->message(0, $limitRet);
        }

        // 当前交易日最多5个未触发的条件单
        $ingCount = Condition::where('user_id', $this->userId)->where('state', CONDITION_STATE_ING)->count();
        if ($ingCount >= 5) return $this->message(0, '当前交易日，最多只能添加五个未触发的条件单');

        // 卖出方向必须有持仓
        if ($data['direction'] == TRADE_DIRECTION_SELL) {
            if ($data['order_position_id'] <= 0) return $this->message(0, '没有对应持仓');

            // 检测有无持仓
            $positionCount = OrderPosition::where('id', $data['order_position_id'])->where('user_id', $data['user_id'])->count();
            if (!$positionCount) return $this->message(0, '没有对应持仓');

            // 当日一个持仓只允许一个未触发条件单
            $isExist = Condition::where('order_position_id', $data['order_position_id'])
                ->where('trading_date', $data['trading_date'])
                ->where('state', CONDITION_STATE_ING)
                ->where('direction', TRADE_DIRECTION_SELL)
                ->count();
            if ($isExist) return $this->message(0, '当前交易日，该持仓最多可以添加一个未触发的条件单');
        }

        //var_dump($data);
        // 创建委托单
        $condition = Condition::create($data);

        if ($condition) {
            // 加入持仓股票订阅列表
            QuotationRedis::addPositionSubscribe($data['market'], $data['stock_code']);
            // 缓存条件单信息
            ConditionRedis::cacheCondition($condition['id']);
        }

        return $condition ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

    /**
     * 删除条件单
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \Exception
     */
    public function delete()
    {
        $id = input('post.id', 0, FILTER_SANITIZE_NUMBER_INT);
        if ($id <= 0) return $this->message(0, '参数无效');

        $condition = Condition::where('id', $id)->where('user_id', $this->userId)->field('id,market,stock_code')->find();
        if (!$condition) return $this->message(0, '没有对应条件单');

        $market    = $condition['market'];
        $stockCode = $condition['stock_code'];
        $ret       = $condition->delete();

        if ($ret) {
            // 删除缓存，防止被触发
            ConditionRedis::delConditionCache($id, $market, $stockCode);
        }

        return $ret ? $this->message(1, '删除成功') : $this->message(1, '删除失败');
    }

}
