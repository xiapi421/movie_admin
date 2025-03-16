<?php

namespace app\admin\model;

use think\Model;
use think\facade\Cache;
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


    public function setStatusAttr($value)
    {
        if($value == '0'){
            Cache::store('redis')->delete('code:'.$this->code);
        }
        if($value == '1'){
            Cache::store('redis')->tag('code')->set('code:'.$this->code,json_encode(['user_id'=>$this->user_id,'status'=>1],JSON_UNESCAPED_UNICODE),0);
        }
    }

}