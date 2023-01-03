<?php
namespace app\stock\controller;

use app\common\model\TradingCalendar;
use think\Db;
use util\TradingRedis;

class NonTradingDateController extends BaseController
{

    /**
     * 获取本月及下月的交易日历
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function calendar()
    {
        // 本月第一天日期
        $startDate = date('Y-m-01');
        // 下个月最后一天
        $dateTime    = new \DateTime($startDate);
        $endDateTime = $dateTime->add(\DateInterval::createFromDateString('2 month'))
            ->sub(\DateInterval::createFromDateString('1 day'));
        $endDate     = $endDateTime->format('Y-m-d');

        // 日历标题
        $list['thisTitle'] = date('Y年m月');
        $list['nextTitle'] = $endDateTime->format('Y年m月');

        $dataKey = [
            substr($startDate, 0, 7) => 'this',
            substr($endDate, 0, 7)   => 'next',
        ];

        // 日历
        $rs = TradingCalendar::where('trading_date', '>=', $startDate)
            ->where('trading_date', '<=', $endDate)
            ->order('id', 'ASC')
            ->column('trading_date,day_of_week,is_disabled', 'id');

        // 按月分组
        foreach ($rs as $item) {
            $key          = $dataKey[substr($item['trading_date'], 0, 7)];
            $list[$key][] = $item;
        }

        // 本月星期补空(补前)
        $thisFirstDay = reset($list['this']);
        for ($i = 1; $i < $thisFirstDay['day_of_week']; $i++) {
            array_unshift($list['this'], ['trading_date' => '']);
        }
        // 本月星期补空(补后)
        $thisLastDay = end($list['this']);
        for ($i = $thisLastDay['day_of_week'] + 1; $i <= 7; $i++) {
            $list['this'][] = ['trading_date' => ''];
        }
        // 本月星期补空(补前)
        $nextFirstDay = reset($list['next']);
        for ($i = 1; $i < $nextFirstDay['day_of_week']; $i++) {
            array_unshift($list['next'], ['trading_date' => '']);
        }
        // 本月星期补空(补后)
        $nextLastDay = end($list['next']);
        for ($i = $nextLastDay['day_of_week'] + 1; $i <= 7; $i++) {
            $list['next'][] = ['trading_date' => ''];
        }

        return $this->message(1, '', $list);
    }

    /**
     * 设置或删除一个非交易日
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function setNonTradingDate()
    {
        // 获取选中日期
        $data['non_trading_date'] = input('post.non_trading_date', '', [FILTER_SANITIZE_STRING, 'trim']);

        // 提交数据校验
        $result = $this->validate($data, 'NonTradingDate.setNonTradingDate');
        if ($result !== true) {
            return $this->message(0, $result);
        }

        // 查询该日期是否已经设置
        TradingCalendar::update([
            'is_disabled' => Db::raw("NOT is_disabled"),
        ], [
            ['trading_date', '=', $data['non_trading_date']],
        ]);
        $upRows = TradingCalendar::getNumRows();

        // 更新缓存
        TradingRedis::cacheNonTradingDate();

        return $upRows ? $this->message(1, '操作成功') : $this->message(0, '操作失败');
    }

}
