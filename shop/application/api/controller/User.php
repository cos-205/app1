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
     * @ApiReturn (name="inviteCode", type="string", description="邀请码")
     * @ApiReturn (name="inviteUrl", type="string", description="邀请链接")
     * @ApiReturn (name="userLevel", type="integer", description="用户会员等级")
     * @ApiReturn (name="stats", type="object", description="邀请统计")
     * @ApiReturn (name="memberLevels", type="array", description="会员等级列表")
     */
    public function getInviteInfo()
    {
        $user = $this->auth->getUserinfo();

        // 使用 Model 类获取用户信息
        $userInfo = \app\common\model\User::get($user['id']);
        if (!$userInfo) {
            $this->error('用户不存在');
        }

        // 获取邀请码和链接
        $inviteCode = $userInfo->invite_code ?? '';
        $inviteUrl = request()->domain() . '/pages/auth/register?invite_code=' . $inviteCode;

        // 获取或创建用户统计信息
        $statistics = \app\common\model\fuka\UserStatistics::where('user_id', $user['id'])->find();
        if (!$statistics) {
            $statistics = \app\common\model\fuka\UserStatistics::create([
                'user_id' => $user['id'],
                'team_id' => $user['id'],
                'is_team_leader' => 1,
                'total_invite_count' => 0,
                'valid_invite_count' => 0,
            ]);
        }

        // 使用循环递归方式获取各层级邀请人数
        // 1级：直接邀请的用户
        $level1Users = \app\common\model\CusShare::where('share_id', $user['id'])->column('user_id');
        $level1Count = count($level1Users);

        // 2级：1级用户邀请的用户
        $level2Count = 0;
        $level2Users = [];
        if ($level1Count > 0) {
            $level2Users = \app\common\model\CusShare::where('share_id', 'in', $level1Users)->column('user_id');
            $level2Count = count($level2Users);
        }

        // 3级：2级用户邀请的用户
        $level3Count = 0;
        if ($level2Count > 0) {
            $level3Users = \app\common\model\CusShare::where('share_id', 'in', $level2Users)->column('user_id');
            $level3Count = count($level3Users);
        }

        $totalCount = $level1Count + $level2Count + $level3Count;

        // 获取会员等级配置
        $memberLevels = \app\common\model\fuka\MemberLevel::order('level', 'asc')->select();

        // 格式化会员等级数据
        $levelList = [];
        foreach ($memberLevels as $level) {
            $levelList[] = [
                'level' => (int)$level->level,
                'name' => $level->name,
                'desc' => $this->formatLevelDesc($level),
                'inviteCount' => (int)$level->invite_count,
                'icon' => $this->getLevelIcon($level->level),
                'color' => $this->getLevelColor($level->level),
            ];
        }

        $data = [
            'inviteCode' => $inviteCode,
            'inviteUrl' => $inviteUrl,
            'userLevel' => (int)$userInfo->member_level,
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
        $desc = "邀请{$level->invite_count}位实名认证，可领取财富金卡";
        if ($level->dividend_money > 0) {
            $desc .= "\n每月可领取支付宝分红{$level->dividend_money}万";
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
     * @ApiReturn (name="userLevel", type="integer", description="用户会员等级")
     * @ApiReturn (name="stats", type="object", description="团队统计")
     * @ApiReturn (name="memberLevels", type="array", description="会员等级列表")
     * @ApiReturn (name="currentLevelConfig", type="object", description="当前等级配置")
     */
    public function getTeamInfo()
    {
        $user = $this->auth->getUserinfo();

        // 使用 Model 类获取用户信息
        $userInfo = \app\common\model\User::get($user['id']);
        if (!$userInfo) {
            $this->error('用户不存在');
        }

        // 使用查询作用域和循环递归方式获取各层级邀请人数
        // 1级：直接邀请的人
        $level1Query = \app\common\model\CusShare::directChildren($user['id']);
        $level1Users = $level1Query->column('user_id');
        $level1Count = count($level1Users);

        // 2级：1级成员邀请的人
        $level2Count = 0;
        $level2Users = [];
        if ($level1Count > 0) {
            $level2Query = \app\common\model\CusShare::where('share_id', 'in', $level1Users);
            $level2Users = $level2Query->column('user_id');
            $level2Count = count($level2Users);
        }

        // 3级：2级成员邀请的人
        $level3Count = 0;
        if ($level2Count > 0) {
            $level3Query = \app\common\model\CusShare::where('share_id', 'in', $level2Users);
            $level3Users = $level3Query->column('user_id');
            $level3Count = count($level3Users);
        }

        $totalCount = $level1Count + $level2Count + $level3Count;

        // 使用 Model 类获取会员等级配置
        $memberLevels = \app\common\model\fuka\MemberLevel::order('level', 'asc')->select();

        // 格式化会员等级数据
        $levelList = [];
        foreach ($memberLevels as $level) {
            $levelList[] = [
                'level' => (int)$level->level,
                'name' => $level->name,
                'inviteCount' => (int)$level->invite_count,
                'dividendMoney' => (float)$level->dividend_money,
            ];
        }

        // 获取当前等级配置
        $currentLevel = (int)$userInfo->member_level;
        $currentLevelConfig = \app\common\model\fuka\MemberLevel::where('level', $currentLevel)->find();

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
                'level' => (int)$currentLevelConfig->level,
                'name' => $currentLevelConfig->name,
                'inviteCount' => (int)$currentLevelConfig->invite_count,
                'dividendMoney' => (float)$currentLevelConfig->dividend_money,
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
     * @ApiReturn (name="members", type="array", description="成员列表")
     * @ApiReturn (name="total", type="integer", description="总数")
     * @ApiReturn (name="page", type="integer", description="当前页码")
     * @ApiReturn (name="limit", type="integer", description="每页数量")
     */
    public function getTeamMembers()
    {
        $user = $this->auth->getUserinfo();

        // 使用参数类型转换，符合安全规范
        $params = $this->request->only(['level', 'page', 'limit']);
        $level = isset($params['level']) ? (int)$params['level'] : 0;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

        // 参数验证
        if ($level < 0 || $level > 3) {
            $this->error('层级参数错误');
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        // 使用 Model 类和查询作用域获取团队成员
        $targetUserIds = [];
        $memberLevel = []; // 记录每个用户的层级

        // 获取1级成员
        $level1Users = [];
        if ($level == 1 || $level == 0) {
            $level1Query = \app\common\model\CusShare::directChildren($user['id']);
            $level1Users = $level1Query->column('user_id');
            foreach ($level1Users as $userId) {
                $targetUserIds[] = $userId;
                $memberLevel[$userId] = 1;
            }
        } else {
            // 如果要查询2级或3级，也需要先获取1级
            $level1Query = \app\common\model\CusShare::directChildren($user['id']);
            $level1Users = $level1Query->column('user_id');
        }

        // 获取2级成员
        if (($level == 2 || $level == 0) && !empty($level1Users)) {
            $level2Query = \app\common\model\CusShare::where('share_id', 'in', $level1Users);
            $level2Users = $level2Query->column('user_id');
            foreach ($level2Users as $userId) {
                $targetUserIds[] = $userId;
                $memberLevel[$userId] = 2;
            }

            // 获取3级成员
            if ($level == 3 || $level == 0) {
                if (!empty($level2Users)) {
                    $level3Query = \app\common\model\CusShare::where('share_id', 'in', $level2Users);
                    $level3Users = $level3Query->column('user_id');
                    foreach ($level3Users as $userId) {
                        $targetUserIds[] = $userId;
                        $memberLevel[$userId] = 3;
                    }
                }
            }
        } elseif ($level == 3 && !empty($level1Users)) {
            // 只查询3级
            $level2Query = \app\common\model\CusShare::where('share_id', 'in', $level1Users);
            $level2Users = $level2Query->column('user_id');
            if (!empty($level2Users)) {
                $level3Query = \app\common\model\CusShare::where('share_id', 'in', $level2Users);
                $level3Users = $level3Query->column('user_id');
                foreach ($level3Users as $userId) {
                    $targetUserIds[] = $userId;
                    $memberLevel[$userId] = 3;
                }
            }
        }

        // 去重
        $targetUserIds = array_unique($targetUserIds);
        $total = count($targetUserIds);

        // 分页
        $offset = ($page - 1) * $limit;
        $pagedUserIds = array_slice($targetUserIds, $offset, $limit);

        // 使用 Model 类获取成员详细信息
        $members = [];
        if (!empty($pagedUserIds)) {
            // 使用 ORM 查询用户信息
            $users = \app\common\model\User::where('id', 'in', $pagedUserIds)->select();

            // 获取推荐关系创建时间
            $shareQuery = \app\common\model\CusShare::where('user_id', 'in', $pagedUserIds);
            $shareInfos = $shareQuery->column('createtime', 'user_id');

            foreach ($users as $member) {
                $members[] = [
                    'id' => (int)$member->id,
                    'name' => $member->nickname ?: $member->mobile,
                    'avatar' => $member->avatar ?: '',
                    'mobile' => $member->mobile ?: '',
                    'level' => $memberLevel[$member->id] ?? 0,
                    'isRealname' => (int)$member->is_realname === 1,
                    'createTime' => isset($shareInfos[$member->id])
                        ? date('Y-m-d H:i', $shareInfos[$member->id])
                        : '',
                ];
            }
        }

        $data = [
            'members' => $members,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];

        $this->success('获取成功', $data);
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
        $user->save();

        // 将实名认证后续处理推送到队列（异步处理）
        \think\Queue::push('app\api\job\Common@userRealnameAfter', ['user_id' => $user->id], 'common');

        $this->success('实名认证成功', [
            'realname' => $user->realname,
            'is_realname' => $user->is_realname,
        ]);
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
