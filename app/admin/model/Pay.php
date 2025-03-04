<?php

namespace app\admin\model;

use think\Model;

/**
 * Pay
 */
class Pay extends Model
{
    // 表名
    protected $name = 'pay';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    protected static function onAfterInsert($model): void
    {
        if (is_null($model->weigh)) {
            $pk = $model->getPk();
            if (strlen($model[$pk]) >= 19) {
                $model->where($pk, $model[$pk])->update(['weigh' => $model->count()]);
            } else {
                $model->where($pk, $model[$pk])->update(['weigh' => $model[$pk]]);
            }
        }
    }

    public function getTotalMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function getTodayMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }

    public function getLastdayMoneyAttr($value): ?float
    {
        return is_null($value) ? null : (float)$value;
    }
}