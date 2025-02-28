<?php

namespace app\admin\model\user\login;

use think\Model;

/**
 * Log
 */
class Log extends Model
{
    // 表名
    protected $name = 'user_login_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;


    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\User::class, 'user_id', 'id');
    }
}