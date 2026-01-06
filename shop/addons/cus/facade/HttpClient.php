<?php

namespace addons\cus\facade;

use addons\cus\library\HttpClient as HttpClientManager;

/**
 * @see HttpClientManager
 * 
 */
class HttpClient extends Base
{
    public static function getFacadeClass() 
    {
        if (!isset($GLOBALS['SPHTTPCLIENT'])) {
            $GLOBALS['SPHTTPCLIENT'] = new HttpClientManager();
        }

        return $GLOBALS['SPHTTPCLIENT'];
    }
}
