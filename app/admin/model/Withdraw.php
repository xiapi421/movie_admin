<?php

namespace app\admin\model;

use think\Model;

/**
 * Withdraw
 */
class Withdraw extends Model
{
    // 表名
    protected $name = 'withdraw';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function setHandleTimeAttr($value): ?string
    {
        return $value ? date('H:i:s', strtotime($value)) : $value;
    }

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\User::class, 'user_id', 'id');
    }
}