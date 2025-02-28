<?php

namespace app\admin\model;

use think\Model;

/**
 * Order
 */
class Order extends Model
{
    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function setNotifyTimeAttr($value): ?string
    {
        return $value ? date('H:i:s', strtotime($value)) : $value;
    }

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\User::class, 'user_id', 'id');
    }

    public function video()
    {
        return $this->belongsTo(\app\admin\model\Video::class, 'video_id', 'id');
    }
}