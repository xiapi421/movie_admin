<?php

namespace app\admin\model;

use think\facade\Cache;
use think\Model;

/**
 * User
 */
class User extends Model
{
    // 表名
    protected $name = 'user';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    protected $append=[
        'login_url',
    ];

    public function getLastdayMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function getTodaySellAttr()
    {
        return Cache::store('redis')->get('agent:'.$this->id.':'.date('Ymd').':total_sell', 0);
    }


    public function getLoginUrlAttr()
    {
        $url = get_sys_config('loginDomain');
        if ($this->group_id==1) return $url.'?secret='.$this->password;
        return str_replace('agent','general',$url).'?secret='.$this->password;
    }


}