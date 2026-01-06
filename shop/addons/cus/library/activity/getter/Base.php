<?php

namespace addons\cus\library\activity\getter;

use addons\cus\library\activity\contract\ActivityGetterInterface;
use addons\cus\library\activity\Activity as ActivityManager;

abstract class Base implements ActivityGetterInterface
{
    protected $manager = null;
    protected $model = null;

    public function __construct(ActivityManager $activityManager) 
    {
        $this->manager = $activityManager;

        $this->model = $activityManager->model;
        $this->redis = $activityManager->redis;
    }
}