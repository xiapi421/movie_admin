<?php

namespace app\admin\model;

use think\Model;

/**
 * Bucket
 */
class Bucket extends Model
{
    // 表名
    protected $name = 'bucket';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}