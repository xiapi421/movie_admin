<?php

namespace app\admin\model;

use think\Model;

/**
 * Code
 */
class Code extends Model
{
    // 表名
    protected $name = 'code';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

}