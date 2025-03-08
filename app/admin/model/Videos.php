<?php

namespace app\admin\model;

use think\facade\Cache;
use think\facade\Log;
use think\Model;

/**
 * Videos
 */
class Videos extends Model
{
    // 表名
    protected $name = 'videos';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 追加属性
    protected $append = [
        'videoCategory',
    ];


    public function getVideoCategoryAttr($value, $row): array
    {
        Log::write(json_encode($row), 'info');
        return [
            'name' => \app\admin\model\video\Category::whereIn('id', $row['video_category_ids'])->column('name'),
        ];
    }

    public function getVideoCategoryIdsAttr($value): array
    {
        if ($value === '' || $value === null) return [];
        if (!is_array($value)) {
            return explode(',', $value);
        }
        return $value;
    }

    public function setVideoCategoryIdsAttr($value): string
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    public function getTotalConversionRateAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

//    public function getTotalViewsAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':view',0);
//    }
//    public function getTodayViewsAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':view',0);
//    }
//
//    public function getTotalClicksAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':click',0);
//    }
//    public function getTodayClicksAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':click',0);
//    }
//
//    public function getTotalPurchasesAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':purchases',0);
//    }
//    public function getTodayPurchasesAttr()
//    {
//        return Cache::store('redis')->get('vid:'.$this->id.':'.date('Ymd').':purchases',0);
//    }
}