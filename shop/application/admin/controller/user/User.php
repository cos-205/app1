<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use addons\cus\service\commission\Agent as AgentService;
use app\admin\model\cus\commission\Log as LogModel;
use app\admin\model\cus\Share as ShareModel;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
            
            // 获取提交的参数
            $params = $this->request->post("row/a");
            if (!$params) {
                $this->error(__('Parameter %s can not be empty', ''));
            }
            
            $row = $this->model->get($ids);
            $this->modelValidate = true;
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            
            // 处理推荐关系修改（在父类保存之前处理）
            $oldParentUserId = $row->parent_user_id;
            $newParentUserId = isset($params['parent_user_id']) ? intval($params['parent_user_id']) : $oldParentUserId;
            
            // 如果推荐关系发生变化，进行验证和更新
            if ($oldParentUserId != $newParentUserId) {
                $this->changeParentUser($row, $newParentUserId);
            }
            
            // 移除 parent_user_id，避免父类重复处理
            unset($params['parent_user_id']);
            $this->request->post(['row' => $params]);
        }
        
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    /**
     * 修改用户推荐关系
     * 
     * @param \app\admin\model\User $user 用户对象
     * @param int $newParentUserId 新的推荐人ID（0表示清除推荐关系）
     * @return void
     */
    protected function changeParentUser($user, $newParentUserId)
    {
        // 验证新的推荐人
        if ($newParentUserId > 0) {
            // 检查推荐人是否存在
            $parentUser = $this->model->find($newParentUserId);
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
        $children = $this->model->where('parent_user_id', $userId)->select();
        
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
        $parentUser = $this->model->find($parentUserId);
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
