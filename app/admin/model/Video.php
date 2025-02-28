<?php

namespace app\admin\model;

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

    public function videoCategory(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\video\Category::class, 'video_category_id', 'id');
    }
}