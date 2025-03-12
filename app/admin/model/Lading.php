<?php

namespace app\admin\model;

use think\Model;

/**
 * Lading
 */
class Lading extends Model
{
    // 表名
    protected $name = 'lading';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

}