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


    public function getLastdayMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function getTodaySellAttr()
    {
//        return Order::where('user_id',$this->id)->where('status',1)->where('create_time','>',strtotime(date('Ymd')))->count();
        return Cache::store('redis')->get('agent:'.$this->id.':'.date('Ymd').':total_sell', 0);
    }

//    public function orders()
//    {
//        return $this->hasMany(Order::class,'user_id','id');
//    }
}