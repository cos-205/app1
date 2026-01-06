<?php

namespace addons\cus\facade;

use addons\cus\library\Redis as RedisManager;

/**
 * @see RedisManager
 * 
 */
class Redis extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPREDIS'])) {
            $GLOBALS['SPREDIS'] = (new RedisManager())->getRedis();
        }

        return $GLOBALS['SPREDIS'];
    }
}
