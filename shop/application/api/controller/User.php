<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Validate;

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
     * 获取邀请信息
     * 
     * @ApiMethod (GET)
     */
    public function getInviteInfo()
    {
        $user = $this->auth->getUserinfo();
        
        // 从数据库获取完整用户信息
        $userModel = model('app\common\model\User');
        $userInfo = $userModel->where('id', $user['id'])->find();
        
        // 获取邀请码和链接
        $inviteCode = $userInfo['invite_code'] ?? '';
        $inviteUrl = request()->domain() . '/pages/auth/register?invite_code=' . $inviteCode;
        
        // 获取用户统计信息
        $statistics = model('app\common\model\fuka\FukaUserStatistics')
            ->where('user_id', $user['id'])
            ->find();
        
        if (!$statistics) {
            // 创建统计记录
            $statistics = model('app\common\model\fuka\FukaUserStatistics')->create([
                'user_id' => $user['id'],
                'createtime' => time(),
                'updatetime' => time(),
            ]);
        }
        
        // 获取各层级邀请人数
        $shareModel = model('app\common\model\CusShare');
        $level1Count = $shareModel->where('share_id', $user['id'])->where('level', 1)->count();
        $level2Count = $shareModel->where('share_id', $user['id'])->where('level', 2)->count();
        $level3Count = $shareModel->where('share_id', $user['id'])->where('level', 3)->count();
        $totalCount = $level1Count + $level2Count + $level3Count;
        
        // 获取会员等级配置
        $memberLevelModel = model('app\common\model\fuka\FukaMemberLevel');
        $memberLevels = $memberLevelModel->order('level', 'asc')->select();
        
        // 格式化会员等级数据
        $levelList = [];
        foreach ($memberLevels as $level) {
            $levelList[] = [
                'level' => (int)$level['level'],
                'name' => $level['name'],
                'desc' => $this->formatLevelDesc($level),
                'inviteCount' => (int)$level['invite_count'],
                'icon' => $this->getLevelIcon($level['level']),
                'color' => $this->getLevelColor($level['level']),
            ];
        }
        
        $data = [
            'inviteCode' => $inviteCode,
            'inviteUrl' => $inviteUrl,
            'userLevel' => isset($userInfo['member_level']) ? (int)$userInfo['member_level'] : 0,
            'stats' => [
                'level1' => $level1Count,
                'level2' => $level2Count,
                'level3' => $level3Count,
                'total' => $totalCount,
            ],
            'memberLevels' => $levelList,
        ];
        
        $this->success('获取成功', $data);
    }
    
    /**
     * 格式化等级描述
     */
    private function formatLevelDesc($level)
    {
        $desc = "邀请{$level['invite_count']}位实名认证，可领取财富金卡";
        if ($level['dividend_money'] > 0) {
            $desc .= "\n每月可领取支付宝分红{$level['dividend_money']}万";
        }
        return $desc;
    }
    
    /**
     * 获取等级图标
     */
    private function getLevelIcon($level)
    {
        $icons = [
            0 => 'person',
            1 => 'medal-filled',
            2 => 'star-filled',
            3 => 'fire-filled',
            4 => 'flag-filled',
            5 => 'gift-filled',
        ];
        return $icons[$level] ?? 'medal-filled';
    }
    
    /**
     * 获取等级颜色
     */
    private function getLevelColor($level)
    {
        $colors = [
            0 => 'linear-gradient(135deg, #D1D5DB, #9CA3AF)',
            1 => 'linear-gradient(135deg, #9CA3AF, #6B7280)',
            2 => 'linear-gradient(135deg, #FBBF24, #F59E0B)',
            3 => 'linear-gradient(135deg, #60A5FA, #3B82F6)',
            4 => 'linear-gradient(135deg, #4B5563, #1F2937)',
            5 => 'linear-gradient(135deg, #A855F7, #9333EA)',
        ];
        return $colors[$level] ?? 'linear-gradient(135deg, #9CA3AF, #6B7280)';
    }

    /**
     * 获取团队信息
     * 
     * @ApiMethod (GET)
     */
    public function getTeamInfo()
    {
        $user = $this->auth->getUserinfo();
        
        // 从数据库获取完整用户信息
        $userModel = model('app\common\model\User');
        $userInfo = $userModel->where('id', $user['id'])->find();
        
        // 获取各层级邀请人数
        $shareModel = model('app\common\model\CusShare');
        $level1Count = $shareModel->where('share_id', $user['id'])->where('level', 1)->count();
        $level2Count = $shareModel->where('share_id', $user['id'])->where('level', 2)->count();
        $level3Count = $shareModel->where('share_id', $user['id'])->where('level', 3)->count();
        $totalCount = $level1Count + $level2Count + $level3Count;
        
        // 获取会员等级配置
        $memberLevelModel = model('app\common\model\fuka\FukaMemberLevel');
        $memberLevels = $memberLevelModel->order('level', 'asc')->select();
        
        // 格式化会员等级数据
        $levelList = [];
        foreach ($memberLevels as $level) {
            $levelList[] = [
                'level' => (int)$level['level'],
                'name' => $level['name'],
                'inviteCount' => (int)$level['invite_count'],
                'dividendMoney' => (float)$level['dividend_money'],
            ];
        }
        
        // 获取当前等级配置
        $currentLevel = isset($userInfo['member_level']) ? (int)$userInfo['member_level'] : 0;
        $currentLevelConfig = $memberLevelModel->where('level', $currentLevel)->find();
        
        $data = [
            'userLevel' => $currentLevel,
            'stats' => [
                'level1' => $level1Count,
                'level2' => $level2Count,
                'level3' => $level3Count,
                'total' => $totalCount,
            ],
            'memberLevels' => $levelList,
            'currentLevelConfig' => $currentLevelConfig ? [
                'level' => (int)$currentLevelConfig['level'],
                'name' => $currentLevelConfig['name'],
                'inviteCount' => (int)$currentLevelConfig['invite_count'],
                'dividendMoney' => (float)$currentLevelConfig['dividend_money'],
            ] : null,
        ];
        
        $this->success('获取成功', $data);
    }

    /**
     * 获取团队成员列表
     * 
     * @ApiMethod (GET)
     * @ApiParams (name="level", type="integer", required=false, description="层级筛选:0=全部,1=1级,2=2级,3=3级")
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     */
    public function getTeamMembers()
    {
        $user = $this->auth->getUserinfo();
        $level = $this->request->get('level/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 10);
        
        // 获取团队成员
        $shareModel = model('app\common\model\CusShare');
        $userModel = model('app\common\model\User');
        
        $where = ['share_id' => $user['id']];
        if ($level > 0) {
            $where['level'] = $level;
        }
        
        $shares = $shareModel->where($where)
            ->order('createtime', 'desc')
            ->page($page, $limit)
            ->select();
        
        $members = [];
        foreach ($shares as $share) {
            $member = $userModel->where('id', $share['user_id'])->find();
            if ($member) {
                $members[] = [
                    'name' => $member['nickname'] ?: $member['mobile'],
                    'avatar' => $member['avatar'] ?: '',
                    'level' => (int)$share['level'],
                    'isRealname' => (int)$member['is_realname'] === 1,
                    'createTime' => date('Y-m-d H:i', $share['createtime']),
                ];
            }
        }
        
        $total = $shareModel->where($where)->count();
        
        $data = [
            'members' => $members,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
        
        $this->success('获取成功', $data);
    }
}
