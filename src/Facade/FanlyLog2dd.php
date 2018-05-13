<?php
namespace Fanly\Log2dingding\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午8:28
 */

class FanlyLog2dd extends Facade {

    protected static function getFacadeAccessor() {
        return 'log2dd';
    }
}