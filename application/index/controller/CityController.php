<?php
namespace app\index\controller;

use app\common\model\City;

class CityController extends BaseController
{

    /**
     * 省市联动JSON Tree
     *
     * @return \think\response\Json
     */
    public function tree()
    {
        $list = City::where('area_level', '<', 3)->where('is_show', true)->order('area_id', 'ASC')->column('p_area_id,area_name,area_level', 'area_id');

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
     * 省市联动JSON Tree
     * 根据省获取所有的城市
     * @param  $provinceID
     * @return \think\response\Json
     */
    public function cities($provinceID = '')
    {
        $province = input('get.proviceId', 0, FILTER_SANITIZE_NUMBER_INT);
        $province = $province ? $province : $provinceID;
        $where = [
            'p_area_id' => $province,
            'is_show' => true
        ];
        $list = City::where($where)->order('area_id', 'ASC')->column('p_area_id,area_name,area_level', 'area_id');
        $tree = [];
        if ($list) {
            foreach ($list as $key => $value) {
                $tree['cities'][] = ['id' => $value['area_id'], 'name' => $value['area_name']];
            }
        }

        return $this->message(1, '', $tree);
    }

}
