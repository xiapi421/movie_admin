<?php

namespace app\admin\model;

use think\Model;

/**
 * Agent
 */
class Agent extends Model
{
    // 表名
    protected $name = 'agent';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}