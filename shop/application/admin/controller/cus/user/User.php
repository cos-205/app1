<?php

namespace app\admin\controller\cus\user;

use think\Db;
use app\admin\controller\cus\Common;
use addons\cus\service\Wallet as WalletService;
use app\admin\model\cus\user\User as UserModel;
use app\admin\model\cus\user\Coupon as UserCouponModel;
use addons\cus\service\commission\Agent as AgentService;
use app\admin\model\cus\commission\Log as LogModel;
use app\admin\model\cus\Share as ShareModel;

class User extends Common
{
    protected $model = null;

    protected $noNeedRight = ['select'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserModel;
    }

    /**
     * 用户列表
     */
    public function index()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $data);
    }

    /**
     * 用户详情
     *
     * @param  $id
     */
    public function detail($id)
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $user = $this->model->with(['third_oauth', 'parent_user'])->where('id', $id)->find();
        if (!$user) {
            $this->error(__('No Results were found'));
        }

        $this->success('获取成功', null, $user);
    }

    /**
     * 更新用户
     *
     * @param  $id
     * @return \think\Response
     */
    public function edit($id = null)
    {
        $params = $this->request->only([
            'username', 'nickname', 'mobile', 'password', 
            'avatar', 'gender', 'email', 'status', 'parent_user_id'
        ]);

        if (empty($params['password'])) unset($params['password']);
        if (empty($params['username'])) unset($params['username']);

        $params['id'] = $id;
        $this->svalidate($params, '.edit');
        unset($params['id']);

        $user = $this->model->where('id', $id)->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        // 处理推荐关系修改
        $oldParentUserId = $user->parent_user_id;
        $newParentUserId = isset($params['parent_user_id']) ? intval($params['parent_user_id']) : $oldParentUserId;

        // 如果推荐关系发生变化，进行验证和更新
        if ($oldParentUserId != $newParentUserId) {
            $this->changeParentUser($user, $newParentUserId);
        }

        // 更新其他字段
        unset($params['parent_user_id']); // 已单独处理，从更新参数中移除
        if (!empty($params)) {
            $user->save($params);
        }

        $this->success('更新成功', null, $user);
    }

    /**
     * 删除用户(支持批量)
     *
     * @param  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (empty($id)) {
            $this->error(__('Parameter %s can not be empty', 'id'));
        }

        $id = explode(',', $id);
        $list = $this->model->where('id', 'in', $id)->select();
        $result = Db::transaction(function () use ($list) {
            $count = 0;
            foreach ($list as $item) {
                $count += $item->delete();
            }

            return $count;
        });

        if ($result) {
            $this->success('删除成功', null, $result);
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    public function recharge()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $params = $this->request->only(['id', 'type', 'amount', 'memo']);
        if (!in_array($params['type'], ['money', 'score'])) {
            error_stop('参数错误');
        }

        $result = Db::transaction(function () use ($params) {
            return WalletService::change($params['id'], $params['type'], $params['amount'], 'admin_recharge', [], $params['memo']);
        });
        if ($result) {
            $this->success('充值成功');
        }
        $this->error('充值失败');
    }


    /**
     * 用户优惠券列表
     */
    public function coupon($id)
    {
        $userCoupons = UserCouponModel::sheepFilter()->with('coupon')->where('user_id', $id)
            ->order('id', 'desc')->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $userCoupons);
    }


    public function select()
    {
        if (!$this->request->isAjax()) {
            return $this->view->fetch();
        }

        $data = $this->model->sheepFilter()->paginate($this->request->param('list_rows', 10));

        $this->success('获取成功', null, $data);
    }

    /**
     * 修改用户推荐关系
     * 
     * @param UserModel $user 用户对象
     * @param int $newParentUserId 新的推荐人ID（0表示清除推荐关系）
     * @return void
     */
    protected function changeParentUser($user, $newParentUserId)
    {
        // 验证新的推荐人
        if ($newParentUserId > 0) {
            // 检查推荐人是否存在
            $parentUser = UserModel::find($newParentUserId);
            if (!$parentUser) {
                $this->error('推荐人不存在');
            }

            // 防止循环推荐
            if (!$this->checkChangeParentAgent($user->id, $newParentUserId)) {
                $this->error('不能绑定该上级，会导致循环推荐');
            }

            // 检查推荐人是否是分销商（可选，根据业务需求决定是否启用）
            // $parentAgent = new AgentService($newParentUserId);
            // if (!$parentAgent->isAgentAvaliable()) {
            //     $this->error('选中用户暂未成为分销商,不能成为推荐人');
            // }
        }

        // 记录原推荐人ID
        $oldParentUserId = $user->parent_user_id;

        // 更新用户推荐关系
        $user->parent_user_id = $newParentUserId > 0 ? $newParentUserId : null;
        $user->save();

        // 更新推荐关系表
        $this->updateShareRelation($user->id, $oldParentUserId, $newParentUserId);

        // 记录操作日志
        if ($newParentUserId > 0) {
            LogModel::add($newParentUserId, 'share', ['user' => $user]);
        }

        // 触发分销商升级检查（如果用户是分销商）
        $userAgent = new AgentService($user->id);
        if ($userAgent->isAgentAvaliable()) {
            if ($oldParentUserId > 0) {
                $userAgent->createAsyncAgentUpgrade($oldParentUserId);
            }
            if ($newParentUserId > 0) {
                $userAgent->createAsyncAgentUpgrade($newParentUserId);
            }
        }
    }

    /**
     * 更新推荐关系表
     * 
     * @param int $userId 用户ID
     * @param int $oldParentUserId 原推荐人ID
     * @param int $newParentUserId 新推荐人ID
     * @return void
     */
    protected function updateShareRelation($userId, $oldParentUserId, $newParentUserId)
    {
        // 如果新推荐人为0，删除推荐关系（清除推荐关系时删除）
        if ($newParentUserId == 0) {
            ShareModel::where('user_id', $userId)->delete();
            return;
        }

        // 优先查找对应原推荐人的记录，如果没有则查找该用户的任意推荐关系记录
        $share = null;
        if ($oldParentUserId > 0) {
            $share = ShareModel::where('user_id', $userId)
                ->where('share_id', $oldParentUserId)
                ->find();
        }
        
        // 如果没找到对应原推荐人的记录，查找该用户的任意推荐关系记录
        if (!$share) {
            $share = ShareModel::where('user_id', $userId)->find();
        }

        if ($share) {
            // 更新现有记录（在原有记录上修改）
            $share->share_id = $newParentUserId;
            
            // 查询父级推荐链
            $parentShare = ShareModel::where('user_id', $newParentUserId)->find();
            
            if ($parentShare && !empty($parentShare->parent_ids)) {
                // 继承父级的推荐链并添加父级ID
                $share->parent_ids = $parentShare->parent_ids . ',' . $newParentUserId;
            } else {
                // 父级是根节点
                $share->parent_ids = (string)$newParentUserId;
            }
            
            // 如果记录是新创建的，设置创建时间
            if (empty($share->createtime)) {
                $share->createtime = time();
            }
            $share->updatetime = time();
            $share->save();
        } else {
            // 不存在记录，创建新的推荐关系
            $share = new ShareModel();
            $share->user_id = $userId;
            $share->share_id = $newParentUserId;
            
            // 查询父级推荐链
            $parentShare = ShareModel::where('user_id', $newParentUserId)->find();
            
            if ($parentShare && !empty($parentShare->parent_ids)) {
                // 继承父级的推荐链并添加父级ID
                $share->parent_ids = $parentShare->parent_ids . ',' . $newParentUserId;
            } else {
                // 父级是根节点
                $share->parent_ids = (string)$newParentUserId;
            }
            
            $share->createtime = time();
            $share->updatetime = time();
            $share->save();
        }

        // 更新该用户所有下级的推荐链（递归更新）
        $this->updateChildrenShareRelation($userId);
    }

    /**
     * 递归更新下级用户的推荐链
     * 
     * @param int $userId 用户ID
     * @return void
     */
    protected function updateChildrenShareRelation($userId)
    {
        // 获取当前用户的推荐链
        $currentShare = ShareModel::where('user_id', $userId)->find();
        $currentParentIds = $currentShare ? $currentShare->parent_ids : '';

        // 查找所有直接下级
        $children = UserModel::where('parent_user_id', $userId)->select();
        
        foreach ($children as $child) {
            // 更新下级的推荐关系
            $childShare = ShareModel::where('user_id', $child->id)
                ->where('share_id', $userId)
                ->find();

            if ($childShare) {
                // 重新计算推荐链
                if (!empty($currentParentIds)) {
                    $childShare->parent_ids = $currentParentIds . ',' . $userId;
                } else {
                    $childShare->parent_ids = (string)$userId;
                }
                $childShare->updatetime = time();
                $childShare->save();
            }

            // 递归更新下级的下级
            $this->updateChildrenShareRelation($child->id);
        }
    }

    /**
     * 递归检查推荐关系，防止出现循环推荐
     * 
     * @param int $userId 用户ID
     * @param int $parentUserId 推荐人ID
     * @return bool
     */
    protected function checkChangeParentAgent($userId, $parentUserId)
    {
        // 不能设置自己为推荐人
        if ($userId == $parentUserId) {
            return false;
        }

        // 如果推荐人为0，允许清除推荐关系
        if ($parentUserId == 0) {
            return true;
        }

        // 获取推荐人信息
        $parentUser = UserModel::find($parentUserId);
        if (!$parentUser) {
            return false;
        }

        // 检查推荐人的上级是否是当前用户（防止循环）
        if ($parentUser->parent_user_id == $userId) {
            return false;
        }

        // 如果推荐人没有上级，允许设置
        if ($parentUser->parent_user_id == 0 || $parentUser->parent_user_id === null) {
            return true;
        }

        // 递归检查推荐人的上级链
        return $this->checkChangeParentAgent($userId, $parentUser->parent_user_id);
    }
}
