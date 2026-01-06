<?php

namespace addons\cus\facade;

use addons\cus\library\activity\Activity as ActivityManager;
use app\admin\model\cus\activity\Activity as ActivityModel;

/**
 * @see RedisManager
 * 
 */
class Activity extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPACTIVITY'])) {
            $GLOBALS['SPACTIVITY'] = new ActivityManager(ActivityModel::class);
        }

        return $GLOBALS['SPACTIVITY'];
    }
}
