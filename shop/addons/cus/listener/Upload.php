<?php

namespace addons\cus\listener;


class Upload
{

    public function uploadAfter($params)
    {
        $attachment = $params;
        $cus_type = request()->param('cus_type');

        // simple 包含支付证书，店铺装修截图等不需要再附件管理中存在的文件
        if ($cus_type == 'simple' && isset($attachment->id) && $attachment->id) {
            // 删除附件管理记录
            $attachment->where('id', $attachment->id)->delete();
        }
    }
}
