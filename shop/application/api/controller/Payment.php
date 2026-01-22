<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\service\PaymentService;

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
        output_log('info', [
            'title' => '支付回调通知',
            'pay_type' => $payType,
            'params' => $data,
            'raw_input' => file_get_contents('php://input')
        ]);
        
        // 使用统一支付服务处理回调
        $result = false;
        try {
            $result = PaymentService::processNotify($data, $payType);
            
        } catch (\Exception $e) {
            output_log('error', [
                'title' => '支付回调处理异常',
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
     * 获取收款渠道列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/payment/channelList)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'channels':[]}})
     */
    public function channelList()
    {
        $channels = \think\Db::name('payment_channel')
            ->where('status', 1)
            ->order('sort DESC, id ASC')
            ->select();
        
        // 处理二维码图片地址
        foreach ($channels as &$channel) {
            if (!empty($channel['qrcode_image'])) {
                $channel['qrcode_image'] = cdnurl($channel['qrcode_image'], true);
            }
        }
        
        $this->success('获取成功', ['channels' => $channels]);
    }

    /**
     * 上传支付凭证（商品订单）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/payment/uploadScreenshot)
     * @ApiParams (name="order_sn", type="string", required=true, description="订单号")
     * @ApiParams (name="channel_id", type="integer", required=true, description="支付渠道ID")
     * @ApiParams (name="screenshot", type="string", required=true, description="支付凭证")
     * @ApiReturn ({'code':'1','msg':'上传成功','data':{}})
     */
    public function uploadScreenshot()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderSn = $this->request->param('order_sn', '');
        $channelId = $this->request->param('channel_id/d', 0);
        $screenshot = $this->request->param('screenshot', '');

        if (!$orderSn) {
            $this->error('订单号不能为空');
        }

        if (!$channelId) {
            $this->error('请选择支付渠道');
        }

        if (!$screenshot) {
            $this->error('请上传支付凭证');
        }

        // 查询订单
        $order = \think\Db::name('cus_order')
            ->where('order_sn', $orderSn)
            ->where('user_id', $user->id)
            ->find();

        if (!$order) {
            $this->error('订单不存在');
        }

        if ($order['status'] != 'unpaid') {
            $this->error('订单状态异常');
        }

        // 更新订单信息
        \think\Db::name('cus_order')
            ->where('id', $order['id'])
            ->update([
                'payment_channel_id' => $channelId,
                'payment_screenshot' => $screenshot,
                'screenshot_status' => 0, // 待审核
                'updatetime' => time()
            ]);

        output_log('info', [
            'title' => '用户上传支付凭证',
            'user_id' => $user->id,
            'order_sn' => $orderSn,
            'channel_id' => $channelId
        ]);

        $this->success('上传成功，等待审核');
    }

    /**
     * 上传支付凭证（金卡订单）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/payment/uploadCardScreenshot)
     * @ApiParams (name="order_no", type="string", required=true, description="订单号")
     * @ApiParams (name="channel_id", type="integer", required=true, description="支付渠道ID")
     * @ApiParams (name="screenshot", type="string", required=true, description="支付凭证")
     * @ApiReturn ({'code':'1','msg':'上传成功','data':{}})
     */
    public function uploadCardScreenshot()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderNo = $this->request->param('order_no', '');
        $channelId = $this->request->param('channel_id/d', 0);
        $screenshot = $this->request->param('screenshot', '');

        if (!$orderNo) {
            $this->error('订单号不能为空');
        }

        if (!$channelId) {
            $this->error('请选择支付渠道');
        }

        if (!$screenshot) {
            $this->error('请上传支付凭证');
        }

        // 查询金卡订单
        $order = \think\Db::name('card_order')
            ->where('order_no', $orderNo)
            ->where('user_id', $user->id)
            ->find();

        if (!$order) {
            $this->error('订单不存在');
        }

        if ($order['pay_status'] == 1) {
            $this->error('订单已支付');
        }

        // 更新订单信息
        \think\Db::name('card_order')
            ->where('id', $order['id'])
            ->update([
                'payment_channel_id' => $channelId,
                'payment_screenshot' => $screenshot,
                'screenshot_status' => 0, // 待审核
                'updatetime' => time()
            ]);

        output_log('info', [
            'title' => '用户上传支付凭证（金卡）',
            'user_id' => $user->id,
            'order_no' => $orderNo,
            'channel_id' => $channelId
        ]);

        $this->success('上传成功，等待审核');
    }

    /**
     * 查询支付凭证审核状态
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/payment/screenshotStatus)
     * @ApiParams (name="order_sn", type="string", required=true, description="订单号")
     * @ApiParams (name="order_type", type="string", required=false, description="订单类型:goods=商品订单,card=金卡订单")
     * @ApiReturn ({'code':'1','msg':'查询成功','data':{'status':0,'remark':''}})
     */
    public function screenshotStatus()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $orderSn = $this->request->param('order_sn', '');
        $orderType = $this->request->param('order_type', 'goods');

        if (!$orderSn) {
            $this->error('订单号不能为空');
        }

        if ($orderType === 'card') {
            // 查询金卡订单
            $order = \think\Db::name('card_order')
                ->where('order_no', $orderSn)
                ->where('user_id', $user->id)
                ->find();
        } else {
            // 查询商品订单
            $order = \think\Db::name('cus_order')
                ->where('order_sn', $orderSn)
                ->where('user_id', $user->id)
                ->find();
        }

        if (!$order) {
            $this->error('订单不存在');
        }

        $data = [
            'screenshot_status' => $order['screenshot_status'] ?? 0,
            'screenshot_audit_remark' => $order['screenshot_audit_remark'] ?? '',
            'screenshot_audit_time' => $order['screenshot_audit_time'] ?? 0,
            'payment_screenshot' => !empty($order['payment_screenshot']) ? cdnurl($order['payment_screenshot'], true) : ''
        ];

        $this->success('查询成功', $data);
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

