<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\service\PaymentService;
use think\Log;

/**
 * 支付回调接口
 * 
 * @ApiTitle    (支付回调系统)
 * @ApiSummary  (处理第三方支付回调)
 * @ApiSector   (支付)
 */
class Payment extends Api
{
    // 不需要登录验证
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * 统一支付回调接口
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/payment/notify)
     * @ApiParams (name="pay_type", type="string", required=true, description="支付类型:wechat/alipay/unionpay")
     * @ApiParams (name="order_no", type="string", required=true, description="商户订单号")
     * @ApiReturn ({'code':'SUCCESS','message':'处理成功'})
     */
    public function notify()
    {
        // 获取支付类型
        $payType = $this->request->param('pay_type', '');
        
        // 获取所有回调数据
        $data = $this->request->param();
        
        // 记录回调日志
        Log::info('支付回调通知', [
            'pay_type' => $payType,
            'params' => $data,
            'raw_input' => file_get_contents('php://input')
        ]);
        
        // 使用统一支付服务处理回调
        $result = false;
        try {
            $result = PaymentService::processNotify($data, $payType);
            
        } catch (\Exception $e) {
            Log::error('支付回调处理异常', [
                'pay_type' => $payType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // error 必须在 try-catch 外面
            return $this->error('FAIL');
        }
        
        // success/error 必须在 try-catch 外面，否则抛出的异常会被catch捕获
        if ($result) {
            return $this->success('SUCCESS');
        } else {
            return $this->error('处理失败', 'FAIL');
        }
    }

    /**
     * 重写success方法以返回纯文本响应
     */
    protected function success($msg = '', $data = [], $code = 1, $type = 'json', array $header = [])
    {
        // 对于支付回调，返回简单的SUCCESS字符串
        if ($msg === 'SUCCESS') {
            echo 'SUCCESS';
            exit;
        }
        
        return parent::success($msg, $data, $code, $type, $header);
    }

    /**
     * 重写error方法以返回纯文本响应
     */
    protected function error($msg = '', $data = [], $code = 0, $type = 'json', array $header = [])
    {
        // 对于支付回调，返回简单的FAIL字符串
        if ($msg === 'FAIL' || $data === 'FAIL') {
            echo 'FAIL';
            exit;
        }
        
        return parent::error($msg, $data, $code, $type, $header);
    }
}

