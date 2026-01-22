<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Validate;
use think\Db;
use app\admin\model\cus\user\Address as UserAddressModel;
use app\admin\model\cus\data\Area;
use app\common\model\User as UserModel;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="账号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $email = $this->request->post('email');
        $mobile = $this->request->post('mobile');
        $code = $this->request->post('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @ApiParams (name="avatar", type="string", required=true, description="头像地址")
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="nickname", type="string", required=true, description="昵称")
     * @ApiParams (name="bio", type="string", required=true, description="个人简介")
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="platform", type="string", required=true, description="平台名称")
     * @ApiParams (name="code", type="string", required=true, description="Code码")
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 获取团队信息（已删除福卡相关功能）
     * 
     * @ApiMethod (GET)
     * @ApiReturn (name="message", type="string", description="功能已停用")
     */
    public function getTeamInfo()
    {
        $this->error('该功能已停用');
    }

    /**
     * 获取团队成员列表（已删除福卡相关功能）
     *
     * @ApiMethod (GET)
     * @ApiReturn (name="message", type="string", description="功能已停用")
     */
    public function getTeamMembers()
    {
        $this->error('该功能已停用');
    }

    /**
     * 提交实名认证
     * 
     * @ApiMethod (POST)
     * @ApiParams (name="realname", type="string", required=true, description="真实姓名")
     * @ApiParams (name="idcard", type="string", required=true, description="身份证号码")
     */
    public function submitRealname()
    {
        $user = $this->auth->getUser();

        // 检查是否已经实名认证
        if ($user->is_realname) {
            $this->error('您已完成实名认证，无需重复认证');
        }

        $params = $this->request->only(['realname', 'idcard']);

        // 参数验证
        if (empty($params['realname'])) {
            $this->error('请输入真实姓名');
        }

        if (empty($params['idcard'])) {
            $this->error('请输入身份证号码');
        }

        // 姓名格式验证：2-20个字符，只能包含中文、英文、·
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z·]{2,20}$/u', $params['realname'])) {
            $this->error('请输入正确的姓名格式');
        }

        // 身份证格式验证：18位，前17位数字，最后一位数字或X
        $params['idcard'] = strtoupper($params['idcard']); // 转大写
        if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/', $params['idcard'])) {
            $this->error('请输入正确的18位身份证号码');
        }

        // 身份证校验码验证
        if (!$this->checkIdcardValid($params['idcard'])) {
            $this->error('身份证号码校验失败，请检查');
        }

        // 检查身份证号是否已被使用
        $existUser = \app\common\model\User::where('idcard', $params['idcard'])
            ->where('id', '<>', $user->id)
            ->find();
        if ($existUser) {
            $this->error('该身份证号已被使用');
        }

        // 更新用户信息
        $user->realname = $params['realname'];
        $user->idcard = $params['idcard'];
        $user->is_realname = 1;
        $user->realname_time = time();
        
        // 自动更新nickname为实名认证的姓名
        if (empty($user->nickname) || $user->nickname === $user->mobile) {
            // 如果nickname为空或是手机号，则更新为实名姓名
            $user->nickname = $params['realname'];
        }
        
        $user->save();

        // 将实名认证后续处理推送到队列（异步处理）
        \think\Queue::push('app\api\job\Common@userRealnameAfter', ['user_id' => $user->id], 'common');

        $this->success('实名认证成功', [
            'realname' => $user->realname,
            'is_realname' => $user->is_realname,
        ]);
    }

    /**
     * 完善信息（实名认证+收货地址）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/user/setupRequired)
     * @ApiParams (name="realname", type="string", required=true, description="真实姓名")
     * @ApiParams (name="idcard", type="string", required=true, description="身份证号码")
     * @ApiParams (name="address", type="object", required=true, description="收货地址信息")
     * @ApiReturn ({'code':'1','msg':'完善成功'})
     */
    public function setupRequired()
    {
        $user = $this->auth->getUser();
        
        // 检查是否已经完成
        $hasAddress = UserAddressModel::where('user_id', $user->id)->count() > 0;
        if ($user->is_realname && $hasAddress) {
            $this->error('您已完成信息完善');
        }
        
        $params = $this->request->only(['realname', 'idcard', 'address']);
        
        // 验证参数
        if (empty($params['realname'])) {
            $this->error('请输入真实姓名');
        }
        
        if (empty($params['idcard'])) {
            $this->error('请输入身份证号码');
        }
        
        if (empty($params['address']) || !is_array($params['address'])) {
            $this->error('请填写收货地址信息');
        }
        
        $addressData = $params['address'];
        
        // 验证收货地址必填项
        if (empty($addressData['consignee'])) {
            $this->error('请输入收货人姓名');
        }
        
        if (empty($addressData['mobile'])) {
            $this->error('请输入手机号');
        }
        
        if (empty($addressData['province_name']) || empty($addressData['city_name']) || empty($addressData['district_name'])) {
            $this->error('请选择省市区');
        }
        
        if (empty($addressData['address'])) {
            $this->error('请输入详细地址');
        }
        
        // 姓名格式验证：2-20个字符，只能包含中文、英文、·
        if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z·]{2,20}$/u', $params['realname'])) {
            $this->error('请输入正确的姓名格式');
        }
        
        // 身份证格式验证：18位，前17位数字，最后一位数字或X
        $params['idcard'] = strtoupper($params['idcard']); // 转大写
        if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/', $params['idcard'])) {
            $this->error('请输入正确的18位身份证号码');
        }
        
        // 身份证校验码验证
        if (!$this->checkIdcardValid($params['idcard'])) {
            $this->error('身份证号码校验失败，请检查');
        }
        
        // 检查身份证号是否已被使用
        $existUser = \app\common\model\User::where('idcard', $params['idcard'])
            ->where('id', '<>', $user->id)
            ->find();
        if ($existUser) {
            $this->error('该身份证号已被使用');
        }
        
        // 手机号验证
        if (!preg_match('/^1[3-9]\d{9}$/', $addressData['mobile'])) {
            $this->error('请输入正确的手机号');
        }
        
        Db::startTrans();
        try {
            // 1. 处理实名认证
            if (!$user->is_realname) {
                // 更新用户信息
                $user->realname = $params['realname'];
                $user->idcard = $params['idcard'];
                $user->is_realname = 1;
                $user->realname_time = time();
                $user->nickname = $params['realname'];
                $user->save();
                
                // 将实名认证后续处理推送到队列（异步处理）
                \think\Queue::push('app\api\job\Common@userRealnameAfter', ['user_id' => $user->id], 'common');
            }
            
            // 2. 处理收货地址
            $addressParams = [
                'user_id' => $user->id,
                'consignee' => $addressData['consignee'],
                'mobile' => $addressData['mobile'],
                'province_name' => $addressData['province_name'],
                'city_name' => $addressData['city_name'],
                'district_name' => $addressData['district_name'],
                'address' => $addressData['address'],
                'is_default' => isset($addressData['is_default']) ? (int)$addressData['is_default'] : 1,
            ];
            
            // 获取地区ID
            $addressParams = $this->getAreaIdByName($addressParams);
            
            // 创建收货地址
            $address = new UserAddressModel();
            $address->save($addressParams);
            
            // 如果设为默认，更新其他地址
            if ($addressParams['is_default']) {
                UserAddressModel::where('id', '<>', $address->id)
                    ->where('user_id', $user->id)
                    ->update(['is_default' => 0]);
            }
            
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('提交失败：' . $e->getMessage());
        }
        $this->success('信息完善成功', [
            'realname' => $user->realname,
            'is_realname' => $user->is_realname,
            'address_id' => $address->id
        ]);
        
    }
    
    /**
     * 根据地区名称获取地区ID
     * 
     * @param array $params 地址数据
     * @return array
     */
    private function getAreaIdByName($params)
    {
        $province = Area::where([
            'name' => $params['province_name'],
            'level' => 'province'
        ])->find();
        if (!$province) {
            $this->error('请选择正确的行政区');
        }
        $params['province_id'] = $province->id;

        $city = Area::where([
            'name' => $params['city_name'],
            'level' => 'city',
            'pid' => $province->id
        ])->find();
        if (!$city) {
            $this->error('请选择正确的行政区');
        }
        $params['city_id'] = $city->id;

        $district = Area::where([
            'name' => $params['district_name'],
            'level' => 'district',
            'pid' => $city->id
        ])->find();
        if (!$district) {
            $this->error('请选择正确的行政区');
        }
        $params['district_id'] = $district->id;

        return $params;
    }

    /**
     * 身份证校验码验证
     * 
     * @param string $idcard 身份证号码
     * @return bool
     */
    private function checkIdcardValid($idcard)
    {
        $Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $ValideCode = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += $Wi[$i] * intval(substr($idcard, $i, 1));
        }

        $valCodePosition = $sum % 11;
        $lastChar = strtoupper(substr($idcard, 17, 1));

        return $lastChar === $ValideCode[$valCodePosition];
    }

    /**
     * 绑定支付宝账号
     * 
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="支付宝账号（手机号或邮箱）")
     */
    public function bindAlipay()
    {
        // 获取当前登录用户
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录', null, 401);
        }

        // 获取提交的支付宝账号
        $account = $this->request->post('account', '');
        $account = trim($account);

        // 验证账号不能为空
        if (empty($account)) {
            $this->error('请输入支付宝账号');
        }

        // 验证账号格式（手机号或邮箱）
        $isMobile = preg_match('/^1[3-9]\d{9}$/', $account);
        $isEmail = preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/', $account);

        if (!$isMobile && !$isEmail) {
            $this->error('请输入正确的手机号或邮箱');
        }

        // 检查该支付宝账号是否已被其他用户绑定
        $existUser = \app\common\model\User::where('alipay_account', $account)
            ->where('id', '<>', $user->id)
            ->find();

        if ($existUser) {
            $this->error('该支付宝账号已被其他用户绑定');
        }

        $user->alipay_account = $account;
        $user->alipay_bind_time = time();
        $user->save();
        $this->success('绑定成功', [
            'alipay_account' => $account,
            'bind_time' => date('Y-m-d H:i:s', $user->alipay_bind_time)
        ]);
    }

    /**
     * 绑定微信号
     * 
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="微信号")
     */
    public function bindWechat()
    {
        // 获取当前登录用户
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录', null, 401);
        }

        // 获取提交的微信号
        $account = $this->request->post('account', '');
        $account = trim($account);

        // 验证账号不能为空
        if (empty($account)) {
            $this->error('请输入微信号');
        }

        // 验证微信号格式（6-20位，字母、数字、下划线、减号）
        if (!preg_match('/^[a-zA-Z0-9_-]{6,20}$/', $account)) {
            $this->error('微信号格式不正确（6-20位字母、数字、下划线、减号）');
        }

        // 检查该微信号是否已被其他用户绑定
        $existUser = \app\common\model\User::where('wechat_account', $account)
            ->where('id', '<>', $user->id)
            ->find();

        if ($existUser) {
            $this->error('该微信号已被其他用户绑定');
        }

        $user->wechat_account = $account;
        $user->wechat_bind_time = time();
        $user->save();
        $this->success('绑定成功', [
            'wechat_account' => $account,
            'bind_time' => date('Y-m-d H:i:s', $user->wechat_bind_time)
        ]);
    }

    /**
     * 获取绑定状态
     * 
     * @ApiMethod (GET)
     */
    public function getBindStatus()
    {
        // 获取当前登录用户
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录', null, 401);
        }

        $this->success('获取成功', [
            'alipay_account' => $user->alipay_account ?: '',
            'alipay_bind_time' => $user->alipay_bind_time ? date('Y-m-d H:i:s', $user->alipay_bind_time) : '',
            'wechat_account' => $user->wechat_account ?: '',
            'wechat_bind_time' => $user->wechat_bind_time ? date('Y-m-d H:i:s', $user->wechat_bind_time) : '',
            'is_alipay_bind' => !empty($user->alipay_account),
            'is_wechat_bind' => !empty($user->wechat_account),
        ]);
    }

}
