<?php

namespace app\admin\model;

use think\Model;

/**
 * Notice
 */
class Notice extends Model
{
    // 表名
    protected $name = 'notice';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getContenetAttr($value): string
    {
        return !$value ? '' : htmlspecialchars_decode($value);
    }
}