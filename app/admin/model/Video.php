<?php

namespace app\admin\model;

use think\facade\Cache;
use think\Model;

/**
 * Video
 */
class Video extends Model
{
    // 表名
    protected $name = 'video';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getTotalConversionRateAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function videoCategory(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(
            \app\admin\model\video\Category::class,
            'video_category_rel',
            'category_id',
            'video_id'
        );
    }

    public function getTotalViewsAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':view',0);
    }
    public function getTodayViewsAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':view',0);
    }

    public function getTotalClicksAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':click',0);
    }
    public function getTodayClicksAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':click',0);
    }

    public function getTotalPurchasesAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':purchases',0);
    }
    public function getTodayPurchasesAttr()
    {
        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':purchases',0);
    }

}