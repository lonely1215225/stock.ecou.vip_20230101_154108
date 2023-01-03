<?php
namespace app\stock\controller;

use app\common\model\City;

class CityController extends BaseController
{

    /**
     * 获取省市级联数组
     *
     * @return \think\response\Json
     */
    public function tree()
    {
        $list = City::where('area_level', '<', 3)->where('is_show', true)->column('p_area_id,area_name,area_level', 'area_id');

        $tree = [];
        if ($list) {
            $tree = $list;
            foreach ($list as $key => $value) {
                $tree[$key] = ['id' => $tree[$key]['area_id'], 'name' => $tree[$key]['area_name']];

                $tree[$value['p_area_id']]['cities'][] =& $tree[$key];
            }

            // 最终树
            $tree = isset($tree[0]['cities']) ? $tree[0]['cities'] : [];
        }

        return $this->message(1, '', $tree);
    }

    /**
     * 获取省份信息
     *
     * @return \think\response\Json
     */
    public function getCityInfo()
    {
        $list = City::where('is_show', true)->column('area_name', 'area_id');

        return $list ? $this->message(1, '', $list) : $this->message(0, '');
    }

}
