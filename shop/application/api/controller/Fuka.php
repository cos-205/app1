<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\fuka\Type as FukaType;
use app\common\model\fuka\UserCard as FukaUserCard;
use app\common\model\fuka\UserStatistics as FukaUserStatistics;
use app\common\model\fuka\ChanceLog as FukaChanceLog;
use app\common\model\fuka\Rank as FukaRank;
use app\common\model\fuka\ExchangeRecord as FukaExchangeRecord;
use app\common\model\fuka\Prize as FukaPrize;
use app\common\model\fuka\WufuCard as FukaWufuCard;
use think\Db;

/**
 * 集福卡接口
 * 
 * @ApiTitle    (集福卡系统)
 * @ApiSummary  (集福卡相关接口)
 * @ApiSector   (集福卡)
 */
class Fuka extends Api
{
    protected $noNeedLogin = ['typeList', 'rankList'];
    protected $noNeedRight = '*';

    /**
     * 获取福卡类型列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/typeList)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[]}})
     */
    public function typeList()
    {
        $list = FukaType::where('status', 'normal')
            ->order('weigh desc,id asc')
            ->select();
        
        // 处理图片URL
        foreach ($list as &$item) {
            $item['image'] = $item['image_url'] ?? '';
        }
        unset($item);
        
        $this->success('获取成功', ['list' => $list]);
    }

    /**
     * 获取我的福卡列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/myCards)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'statistics':{}}})
     */
    public function myCards()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户福卡列表（未使用的）
        $list = FukaUserCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->where('status', 'normal')
            ->with(['fukaType'])
            ->order('createtime desc')
            ->select();

        // 统计各类型福卡数量
        $statistics = [];
        foreach ($list as $card) {
            $typeCode = $card->type_code;
            if (!isset($statistics[$typeCode])) {
                // 获取福卡类型信息（包含图片）
                $fukaType = $card->fukaType;
                $statistics[$typeCode] = [
                    'type_code' => $typeCode,
                    'type_name' => $card->type_name,
                    'image' => $fukaType ? ($fukaType->image_url ?? '') : '',
                    'count' => 0
                ];
            }
            $statistics[$typeCode]['count']++;
        }

        // 获取用户统计信息
        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        $fukaChance = $userStats ? $userStats->fuka_chance : 0;

        $this->success('获取成功', [
            'list' => $list,
            'statistics' => array_values($statistics),
            'fuka_chance' => $fukaChance,
            'total_count' => count($list)
        ]);
    }

    /**
     * 获取用户福卡统计信息
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/statistics)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'wufu_card_count':0,'can_combine_count':0,'set_count':0,'total_count':0,'fuka_chance':0}})
     */
    public function statistics()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户统计信息
        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        if (!$userStats) {
            $this->success('获取成功', [
                'wufu_card_count' => 0,
                'can_combine_count' => 0,
                'set_count' => 0,
                'total_count' => 0,
                'fuka_chance' => 0,
                'current_fuka_count' => 0
            ]);
            return;
        }

        // 获取实际拥有的五福卡数量
        $wufuCardCount = FukaWufuCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->count();

        // 获取用户未使用的福卡
        $userCards = FukaUserCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->where('status', 'normal')
            ->select();

        // 计算可以组成多少套五福卡（用于提示用户）
        $canCombineCount = 0;
        if (!empty($userCards) && count($userCards) > 0) {
            $canCombineCount = $this->calculateSetCount($userCards);
        }

        $this->success('获取成功', [
            'wufu_card_count' => $wufuCardCount,  // 实际拥有的五福卡数量
            'can_combine_count' => $canCombineCount,  // 可以合成的五福卡套数
            'set_count' => $wufuCardCount,  // 兼容旧版本，使用实际拥有的数量
            'total_count' => $userStats->total_fuka_count,
            'current_count' => $userStats->current_fuka_count,
            'fuka_chance' => $userStats->fuka_chance
        ]);
    }

    /**
     * 计算可以组成多少套五福卡
     * 
     * @param array $cards 用户福卡列表
     * @return int
     */
    private function calculateSetCount($cards)
    {
        // 统计各类型福卡数量
        $typeCount = [];
        foreach ($cards as $card) {
            $typeCode = $card->type_code;
            if (!isset($typeCount[$typeCode])) {
                $typeCount[$typeCode] = 0;
            }
            $typeCount[$typeCode]++;
        }

        // 检查是否有万能福
        $universalCount = isset($typeCount['wanneng']) ? $typeCount['wanneng'] : 0;

        // 五福卡需要的类型
        $requiredTypes = ['aiguo', 'youshan', 'jingye', 'hexie', 'fuqiang'];
        
        // 计算可以组成多少套
        $setCount = 0;
        $tempTypeCount = $typeCount;
        $tempUniversalCount = $universalCount;
        
        while (true) {
            $canMakeSet = true;
            $usedUniversal = 0;
            
            foreach ($requiredTypes as $type) {
                $count = isset($tempTypeCount[$type]) ? $tempTypeCount[$type] : 0;
                if ($count > 0) {
                    $tempTypeCount[$type]--;
                } else if ($tempUniversalCount > 0) {
                    $tempUniversalCount--;
                    $usedUniversal++;
                } else {
                    $canMakeSet = false;
                    break;
                }
            }
            
            if ($canMakeSet) {
                $setCount++;
            } else {
                break;
            }
        }

        return $setCount;
    }

    /**
     * 抽取福卡(前端调用draw)
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/draw)
     * @ApiParams (name="fuka_type_id", type="integer", required=false, description="指定福卡类型ID，不传则随机")
     * @ApiReturn ({'code':'1','msg':'集福成功','data':{'card':{}}})
     */
    public function draw()
    {
        return $this->useChance();
    }

    /**
     * 使用集福机会
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/useChance)
     * @ApiParams (name="fuka_type_id", type="integer", required=false, description="指定福卡类型ID，不传则随机")
     * @ApiReturn ({'code':'1','msg':'集福成功','data':{'card':{}}})
     */
    public function useChance()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 参数验证（可选参数，不强制）
        $params = $this->request->only(['fuka_type_id']);
        // 参数验证可以在这里添加

        // 获取用户统计信息（使用锁防止并发问题）
        $userStats = FukaUserStatistics::where('user_id', $user->id)->lock(true)->find();
        if (!$userStats) {
            $this->error('用户统计信息不存在');
        }
        if ($userStats->fuka_chance <= 0) {
            $this->error('集福机会不足');
        }

        $fukaTypeId = $this->request->param('fuka_type_id/d', 0);

        $card = null;
        
        Db::startTrans();
        try {
            // 扣减集福机会
            $userStats->fuka_chance -= 1;
            $userStats->save();

            // 记录机会使用日志
            $chanceLog = new FukaChanceLog();
            $chanceLog->user_id = $user->id;
            $chanceLog->change_type = 2; // 使用
            $chanceLog->change_count = -1;
            $chanceLog->before_count = $userStats->fuka_chance + 1;
            $chanceLog->after_count = $userStats->fuka_chance;
            $chanceLog->source_type = 6; // 抽奖
            $chanceLog->remark = '使用集福机会';
            $chanceLog->save();

            // 获取福卡类型
            if ($fukaTypeId > 0) {
                $fukaType = FukaType::get($fukaTypeId);
                if (!$fukaType || $fukaType->status != 'normal') {
                    throw new \Exception('福卡类型不存在');
                }
            } else {
                // 随机获取福卡类型（根据掉落概率）
                $fukaType = $this->getRandomFukaType();
            }

            // 创建用户福卡记录
            $card = new FukaUserCard();
            $card->user_id = $user->id;
            $card->fuka_type_id = $fukaType->id;
            $card->type_code = $fukaType->type_code;
            $card->type_name = $fukaType->type_name;
            $card->source_type = 6; // 抽奖
            $card->source_id = $chanceLog->id;
            $card->save();

            // 更新用户统计
            $userStats->current_fuka_count += 1;
            $userStats->total_fuka_count += 1;
            $userStats->save();

            // 更新排行榜
            \app\common\service\fuka\RankUpdate::updateUserRank($user->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('集福失败，请稍后重试');
        }
        
        // 添加图片URL到返回数据
        $cardData = $card->toArray();
        $cardData['image'] = $fukaType->image_url ?? '';
        
        $this->success('集福成功', ['card' => $cardData]);
    }

    /**
     * 根据概率随机获取福卡类型
     * 
     * @return FukaType
     */
    private function getRandomFukaType()
    {
        $types = FukaType::where('status', 'normal')
            ->where('is_universal', 0)
            ->select();
        
        if (empty($types) || count($types) == 0) {
            throw new \Exception('暂无可用福卡类型');
        }

        // 计算总概率
        $totalRate = 0;
        foreach ($types as $type) {
            $totalRate += $type->drop_rate;
        }

        // 随机数
        $random = mt_rand(1, $totalRate);
        $currentRate = 0;

        foreach ($types as $type) {
            $currentRate += $type->drop_rate;
            if ($random <= $currentRate) {
                return $type;
            }
        }

        // 默认返回第一个（防止概率计算异常）
        return $types[0];
    }

    /**
     * 合成五福卡
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/combine)
     * @ApiReturn ({'code':'1','msg':'合成成功','data':{'wufu_card':{}}})
     */
    public function combine()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户未使用的福卡（使用锁防止并发问题）
        $userCards = FukaUserCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->where('status', 'normal')
            ->lock(true)
            ->select();

        if (count($userCards) < 5) {
            $this->error('福卡数量不足，无法合成五福卡');
        }

        // 检查是否可以合成（每种至少1张，或使用万能福）
        $canCombine = $this->checkCanCombine($userCards);
        if (!$canCombine) {
            $this->error('无法合成五福卡，需要集齐5种不同福卡（可使用万能福替代）');
        }

        $wufuCard = null;
        
        Db::startTrans();
        try {
            // 选择5张福卡（优先使用对应类型，不足用万能福）
            $selectedCards = $this->selectCardsForCombine($userCards);
            
            // 验证选择的福卡数量
            if (count($selectedCards) != 5) {
                throw new \Exception('选择的福卡数量不正确，需要5张');
            }
            
            $selectedCardIds = array_column($selectedCards, 'id');
            
            // 验证ID数组
            if (count($selectedCardIds) != 5 || count(array_unique($selectedCardIds)) != 5) {
                throw new \Exception('福卡ID无效或重复');
            }

            // 再次验证这些福卡是否仍然可用（防止并发问题）
            // 注意：由于已经在事务内，且之前已经锁定了$userCards，这里不需要再次锁定
            // 但为了安全，仍然验证状态
            $validCardIds = FukaUserCard::where('id', 'in', $selectedCardIds)
                ->where('user_id', $user->id)
                ->where('is_used', 0)
                ->where('status', 'normal')
                ->column('id');
            
            if (count($validCardIds) != 5) {
                throw new \Exception('部分福卡已被使用，请重试');
            }
            
            // 确保ID匹配
            $validCardIds = array_map('intval', $validCardIds);
            $selectedCardIds = array_map('intval', $selectedCardIds);
            sort($validCardIds);
            sort($selectedCardIds);
            if ($validCardIds !== $selectedCardIds) {
                throw new \Exception('福卡状态验证失败，请重试');
            }

            // 标记这5张福卡为已使用
            $updateResult = FukaUserCard::where('id', 'in', $selectedCardIds)
                ->update([
                    'is_used' => 1,
                    'used_time' => time()
                ]);
            
            if ($updateResult === false) {
                throw new \Exception('更新福卡状态失败');
            }

            // 创建五福卡记录
            $wufuCard = new FukaWufuCard();
            $wufuCard->user_id = $user->id;
            
            // 设置fuka_ids（会进行验证）
            try {
                $wufuCard->setFukaIdsArray($selectedCardIds);
            } catch (\Exception $e) {
                throw new \Exception('设置福卡ID失败：' . $e->getMessage());
            }
            
            $wufuCard->is_used = 0;
            
            // 验证fuka_ids是否正确设置（应该是数组）
            if (empty($wufuCard->fuka_ids) || !is_array($wufuCard->fuka_ids) || count($wufuCard->fuka_ids) != 5) {
                throw new \Exception('五福卡数据验证失败：fuka_ids无效');
            }
            
            // 保存记录
            $saveResult = $wufuCard->save();
            if ($saveResult === false) {
                // ThinkPHP 5.x 中，save() 返回 false 表示保存失败
                // 获取数据库错误信息
                $error = Db::getError();
                throw new \Exception('保存五福卡记录失败：' . ($error ?: '未知错误'));
            }
            
            // 验证保存后的ID
            if (empty($wufuCard->id)) {
                throw new \Exception('保存五福卡记录失败：未生成ID');
            }

            // 更新用户统计（使用锁防止并发问题）
            $userStats = FukaUserStatistics::where('user_id', $user->id)->lock(true)->find();
            if ($userStats) {
                $userStats->current_fuka_count -= 5;
                $userStats->current_wufu_card_count += 1; // 增加五福卡数量
                $userStats->save();
            }

            // 更新排行榜
            \app\common\service\fuka\RankUpdate::updateUserRank($user->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            // 记录错误信息以便调试（不使用\think\Log）
            $errorMsg = $e->getMessage();
            // 返回更详细的错误信息（开发环境）或通用错误（生产环境）
            if (config('app_debug')) {
                $this->error('合成失败：' . $errorMsg);
            } else {
                $this->error('合成失败，请稍后重试');
            }
        }
        
        $this->success('合成成功', ['wufu_card' => $wufuCard]);
    }

    /**
     * 检查是否可以合成五福卡(内部方法)
     * 
     * @param array $cards 用户福卡列表
     * @return bool
     */
    private function checkCanCombine($cards)
    {
        // 统计各类型福卡数量
        $typeCount = [];
        foreach ($cards as $card) {
            $typeCode = $card->type_code;
            if (!isset($typeCount[$typeCode])) {
                $typeCount[$typeCode] = 0;
            }
            $typeCount[$typeCode]++;
        }

        // 检查是否有万能福
        $universalCount = isset($typeCount['wanneng']) ? $typeCount['wanneng'] : 0;

        // 五福卡需要的类型（爱国、友善、敬业、和谐、富强）
        $requiredTypes = ['aiguo', 'youshan', 'jingye', 'hexie', 'fuqiang'];
        
        // 检查每种类型是否至少有一张，或可以用万能福替代
        $needUniversal = 0;
        foreach ($requiredTypes as $type) {
            $count = isset($typeCount[$type]) ? $typeCount[$type] : 0;
            if ($count == 0) {
                $needUniversal++;
            }
        }

        return $needUniversal <= $universalCount;
    }

    /**
     * 选择用于合成的福卡(内部方法)
     * 
     * @param array $cards 用户福卡列表
     * @return array
     */
    private function selectCardsForCombine($cards)
    {
        $selectedCards = [];
        $requiredTypes = ['aiguo', 'youshan', 'jingye', 'hexie', 'fuqiang'];
        
        // 按类型分组
        $cardsByType = [];
        foreach ($cards as $card) {
            $typeCode = $card->type_code;
            if (!isset($cardsByType[$typeCode])) {
                $cardsByType[$typeCode] = [];
            }
            $cardsByType[$typeCode][] = $card;
        }

        // 为每种类型选择一张福卡
        foreach ($requiredTypes as $type) {
            // 优先使用对应类型的福卡
            if (isset($cardsByType[$type]) && count($cardsByType[$type]) > 0) {
                $selectedCards[] = array_shift($cardsByType[$type]);
            } else {
                // 使用万能福
                if (isset($cardsByType['wanneng']) && count($cardsByType['wanneng']) > 0) {
                    $selectedCards[] = array_shift($cardsByType['wanneng']);
                } else {
                    throw new \Exception('福卡不足，无法合成');
                }
            }
        }

        return $selectedCards;
    }

    /**
     * 获取我的五福卡列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/myWufuCards)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function myWufuCards()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户未使用的五福卡列表
        $list = FukaWufuCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->order('createtime desc')
            ->select();

        $total = FukaWufuCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->count();

        $this->success('获取成功', [
            'list' => $list,
            'total' => $total
        ]);
    }

    /**
     * 一键兑换
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/exchange)
     * @ApiParams (name="prize_id", type="integer", required=true, description="奖品ID")
     * @ApiReturn ({'code':'1','msg':'兑换成功','data':{'exchange_record':{}}})
     */
    public function exchange()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 参数验证
        $prizeId = $this->request->param('prize_id/d', 0);
        if (!$prizeId) {
            $this->error('请选择要兑换的奖品');
        }

        $wufuCardIds = $this->request->param('wufu_card_ids/a', []);
        if (empty($wufuCardIds) || !is_array($wufuCardIds)) {
            $this->error('请选择要使用的五福卡');
        }

        // 获取奖品信息
        $prize = FukaPrize::get($prizeId);
        if (!$prize || $prize->status != 'normal') {
            $this->error('奖品不存在');
        }

        if ($prize->stock <= 0) {
            $this->error('奖品库存不足');
        }

        // 验证五福卡数量
        if (count($wufuCardIds) != $prize->need_fuka_set) {
            $this->error('五福卡数量不正确，需要' . $prize->need_fuka_set . '个五福卡');
        }

        // 验证五福卡所有权和状态（使用锁防止并发问题）
        $wufuCards = FukaWufuCard::where('id', 'in', $wufuCardIds)
            ->where('user_id', $user->id)
            ->where('is_used', 0)
            ->lock(true)
            ->select();

        if (count($wufuCards) != $prize->need_fuka_set) {
            $this->error('五福卡不存在或已被使用');
        }
        
        // 去重检查（防止重复ID）
        $uniqueIds = array_unique($wufuCardIds);
        if (count($uniqueIds) != count($wufuCardIds)) {
            $this->error('五福卡ID不能重复');
        }

        $exchangeRecord = null;
        
        Db::startTrans();
        try {
            // 收集所有使用的福卡ID（从五福卡中）
            $allFukaIds = [];
            foreach ($wufuCards as $wufuCard) {
                $fukaIds = $wufuCard->getFukaIdsArray();
                $allFukaIds = array_merge($allFukaIds, $fukaIds);
            }

            // 创建兑换记录
            $exchangeRecord = new FukaExchangeRecord();
            $exchangeRecord->user_id = $user->id;
            $exchangeRecord->prize_id = $prize->id;
            $exchangeRecord->prize_name = $prize->prize_name;
            $exchangeRecord->prize_type = $prize->prize_type;
            $exchangeRecord->fuka_set_count = $prize->need_fuka_set;
            $exchangeRecord->fuka_ids = json_encode($allFukaIds);
            $exchangeRecord->exchange_status = 0; // 待审核
            $exchangeRecord->exchange_time = time();
            $exchangeRecord->save();

            // 标记五福卡为已使用
            FukaWufuCard::where('id', 'in', $wufuCardIds)
                ->update([
                    'is_used' => 1,
                    'used_time' => time(),
                    'exchange_id' => $exchangeRecord->id
                ]);

            // 更新奖品库存（使用条件更新防止超发）
            $updateResult = FukaPrize::where('id', $prize->id)
                ->where('stock', '>', 0)
                ->update([
                    'stock' => Db::raw('stock - 1'),
                    'exchange_count' => Db::raw('exchange_count + 1')
                ]);
            
            if ($updateResult === 0) {
                throw new \Exception('奖品库存不足');
            }

            // 更新用户统计（减少五福卡数量）
            $userStats = FukaUserStatistics::where('user_id', $user->id)->lock(true)->find();
            if ($userStats) {
                $userStats->current_wufu_card_count -= count($wufuCards);
                $userStats->save();
            }

            // 更新排行榜
            \app\common\service\fuka\RankUpdate::updateUserRank($user->id);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            // 记录错误信息（不使用\think\Log）
            // 错误信息已通过异常捕获，直接返回错误提示
            $this->error('兑换失败，请稍后重试');
        }
        
        $this->success('兑换成功', ['exchange_record' => $exchangeRecord]);
    }

    /**
     * 检查是否可以兑换(内部方法)
     * 
     * @param array $cards 用户福卡列表
     * @param int $needSets 需要的套数
     * @return bool
     */
    private function checkCanExchangeWithCards($cards, $needSets)
    {
        // 统计各类型福卡数量
        $typeCount = [];
        foreach ($cards as $card) {
            $typeCode = $card->type_code;
            if (!isset($typeCount[$typeCode])) {
                $typeCount[$typeCode] = 0;
            }
            $typeCount[$typeCode]++;
        }

        // 检查是否有万能福
        $universalCount = isset($typeCount['wanneng']) ? $typeCount['wanneng'] : 0;

        // 五福卡需要的类型（爱国、友善、敬业、和谐、富强）
        $requiredTypes = ['aiguo', 'youshan', 'jingye', 'hexie', 'fuqiang'];
        
        // 计算可以组成多少套
        $canMakeSets = 0;
        while (true) {
            $needUniversal = 0;
            foreach ($requiredTypes as $type) {
                $count = isset($typeCount[$type]) ? $typeCount[$type] : 0;
                if ($count <= $canMakeSets) {
                    $needUniversal += ($canMakeSets + 1 - $count);
                }
            }
            
            if ($needUniversal <= $universalCount) {
                $canMakeSets++;
            } else {
                break;
            }
        }

        return $canMakeSets >= $needSets;
    }

    /**
     * 选择用于兑换的福卡
     * 
     * @param array $cards 用户福卡列表
     * @param int $needSets 需要的套数
     * @return array
     */
    private function selectCardsForExchange($cards, $needSets)
    {
        $selectedCards = [];
        $requiredTypes = ['aiguo', 'youshan', 'jingye', 'hexie', 'fuqiang'];
        
        // 按类型分组
        $cardsByType = [];
        foreach ($cards as $card) {
            $typeCode = $card->type_code;
            if (!isset($cardsByType[$typeCode])) {
                $cardsByType[$typeCode] = [];
            }
            $cardsByType[$typeCode][] = $card;
        }

        // 为每套选择福卡
        for ($i = 0; $i < $needSets; $i++) {
            foreach ($requiredTypes as $type) {
                // 优先使用对应类型的福卡
                if (isset($cardsByType[$type]) && count($cardsByType[$type]) > 0) {
                    $selectedCards[] = array_shift($cardsByType[$type]);
                } else {
                    // 使用万能福
                    if (isset($cardsByType['wanneng']) && count($cardsByType['wanneng']) > 0) {
                        $selectedCards[] = array_shift($cardsByType['wanneng']);
                    } else {
                        throw new \Exception('福卡不足');
                    }
                }
            }
        }

        return $selectedCards;
    }

    /**
     * 获取集福机会数量
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/chanceCount)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'chance_count':0}})
     */
    public function chanceCount()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户统计信息
        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        $chanceCount = $userStats ? $userStats->fuka_chance : 0;

        $this->success('获取成功', ['chance_count' => $chanceCount]);
    }

    /**
     * 获取奖品列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/prizeList)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[]}})
     */
    public function prizeList()
    {
        $list = FukaPrize::where('status', 'normal')
            ->where('stock', '>', 0)
            ->order('weigh desc,id asc')
            ->select();

        $this->success('获取成功', $list);
    }

    /**
     * 检查是否可以兑换
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/checkCanExchange)
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'can_exchange':true,'set_count':0}})
     */
    public function checkCanExchange()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        // 获取用户未使用的福卡
        $userCards = FukaUserCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->where('status', 'normal')
            ->select();

        $setCount = 0;
        if (!empty($userCards) && count($userCards) > 0) {
            $setCount = $this->calculateSetCount($userCards);
        }

        $this->success('获取成功', [
            'can_exchange' => $setCount > 0,
            'set_count' => $setCount
        ]);
    }

    /**
     * 获取兑换记录列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/exchangeList)
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function exchangeList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $status = $this->request->param('status', '');
        
        $query = FukaExchangeRecord::where('user_id', $user->id);
        
        // 状态筛选
        if ($status !== '') {
            if (strpos($status, ',') !== false) {
                // 多个状态
                $statusArr = explode(',', $status);
                $query->where('exchange_status', 'in', $statusArr);
            } else {
                // 单个状态
                $query->where('exchange_status', $status);
            }
        }
        
        $list = $query->order('createtime desc')->paginate();

        $this->success('获取成功', $list);
    }

    /**
     * 获取兑换记录详情
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/exchangeDetail)
     * @ApiParams (name="id", type="integer", required=true, description="兑换记录ID")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{}})
     */
    public function exchangeDetail()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $id = $this->request->param('id/d', 0);
        if (!$id) {
            $this->error('参数错误');
        }

        $record = FukaExchangeRecord::where('id', $id)
            ->where('user_id', $user->id)
            ->find();

        if (!$record) {
            $this->error('兑换记录不存在');
        }

        $this->success('获取成功', $record);
    }

    /**
     * 获取集福排行榜
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/rankList)
     * @ApiParams (name="limit", type="integer", required=false, description="返回数量，默认5")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'my_rank':{}}})
     */
    public function rankList()
    {
        $limit = $this->request->param('limit/d', 5);

        // 获取排行榜前N名
        $list = FukaRank::where('status', 'normal')
            ->where('rank', '>', 0)
            ->order('rank asc')
            ->limit($limit)
            ->select();

        // 获取当前用户的排名
        $myRank = null;
        $user = $this->auth->getUser();
        if ($user) {
            $myRank = FukaRank::where('user_id', $user->id)
                ->where('status', 'normal')
                ->find();
            
            // 如果用户不在排行榜中，尝试更新
            if (!$myRank) {
                \app\common\service\fuka\RankUpdate::updateUserRank($user->id);
                $myRank = FukaRank::where('user_id', $user->id)
                    ->where('status', 'normal')
                    ->find();
            }
        }

        $this->success('获取成功', [
            'list' => $list,
            'my_rank' => $myRank
        ]);
    }

    /**
     * 获取取件码（付费获取）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/getPickupCode)
     * @ApiParams (name="exchange_id", type="integer", required=true, description="兑换记录ID")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'pickup_code':'','fee':0}})
     */
    public function getPickupCode()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $exchangeId = $this->request->param('exchange_id/d', 0);
        if (!$exchangeId) {
            $this->error('兑换记录ID不能为空');
        }

        // 获取兑换记录
        $exchange = FukaExchangeRecord::where('id', $exchangeId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$exchange) {
            $this->error('兑换记录不存在');
        }

        // 检查奖品类型（只有手机奖品需要取件码）
        if ($exchange->prize_type != 1) {
            $this->error('该奖品不需要取件码');
        }

        // 检查是否已获取
        if ($exchange->is_get_pickup_code == 1) {
            $this->success('获取成功', [
                'pickup_code' => $exchange->pickup_code,
                'fee' => $exchange->pickup_code_fee
            ]);
        }

        // 获取奖品信息
        $prize = FukaPrize::get($exchange->prize_id);
        if (!$prize) {
            $this->error('奖品信息不存在');
        }

        // 检查是否需要付费
        if ($prize->need_pickup_code && $prize->pickup_code_fee > 0) {
            // 需要付费，返回费用信息
            $this->success('需要付费获取', [
                'need_pay' => true,
                'fee' => $prize->pickup_code_fee,
                'pickup_code' => ''
            ]);
        } else {
            // 免费获取，直接生成取件码
            Db::startTrans();
            try {
                $pickupCode = $this->generatePickupCode();
                $exchange->pickup_code = $pickupCode;
                $exchange->is_get_pickup_code = 1;
                $exchange->pickup_code_fee = 0;
                $exchange->pay_pickup_time = time();
                $exchange->save();

                Db::commit();
                
                $this->success('获取成功', [
                    'pickup_code' => $pickupCode,
                    'fee' => 0
                ]);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('获取失败，请稍后重试');
            }
        }
    }

    /**
     * 支付取件码费用
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/payPickupCode)
     * @ApiParams (name="exchange_id", type="integer", required=true, description="兑换记录ID")
     * @ApiReturn ({'code':'1','msg':'支付成功','data':{'pickup_code':''}})
     */
    public function payPickupCode()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $exchangeId = $this->request->param('exchange_id/d', 0);
        if (!$exchangeId) {
            $this->error('兑换记录ID不能为空');
        }

        // 获取兑换记录
        $exchange = FukaExchangeRecord::where('id', $exchangeId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$exchange) {
            $this->error('兑换记录不存在');
        }

        // 检查是否已支付
        if ($exchange->is_get_pickup_code == 1) {
            $this->success('已获取', [
                'pickup_code' => $exchange->pickup_code
            ]);
        }

        // 获取奖品信息
        $prize = FukaPrize::get($exchange->prize_id);
        if (!$prize || !$prize->need_pickup_code || $prize->pickup_code_fee <= 0) {
            $this->error('该奖品不需要付费获取取件码');
        }

        // TODO: 这里应该调用支付接口，暂时直接生成取件码
        Db::startTrans();
        try {
            $pickupCode = $this->generatePickupCode();
            $exchange->pickup_code = $pickupCode;
            $exchange->is_get_pickup_code = 1;
            $exchange->pickup_code_fee = $prize->pickup_code_fee;
            $exchange->pay_pickup_time = time();
            $exchange->save();

            Db::commit();
            
            $this->success('支付成功', [
                'pickup_code' => $pickupCode,
                'fee' => $prize->pickup_code_fee
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('支付失败，请稍后重试');
        }
    }

    /**
     * 获取证件（付费获取，用于汽车奖品）
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/getCertificate)
     * @ApiParams (name="exchange_id", type="integer", required=true, description="兑换记录ID")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'certificate_no':'','fee':0}})
     */
    public function getCertificate()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $exchangeId = $this->request->param('exchange_id/d', 0);
        if (!$exchangeId) {
            $this->error('兑换记录ID不能为空');
        }

        // 获取兑换记录
        $exchange = FukaExchangeRecord::where('id', $exchangeId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$exchange) {
            $this->error('兑换记录不存在');
        }

        // 检查奖品类型（只有汽车奖品需要证件）
        if ($exchange->prize_type != 2) {
            $this->error('该奖品不需要证件');
        }

        // 检查是否已获取
        if ($exchange->is_get_certificate == 1) {
            $this->success('获取成功', [
                'certificate_no' => $exchange->certificate_no,
                'fee' => $exchange->certificate_fee
            ]);
        }

        // 获取奖品信息
        $prize = FukaPrize::get($exchange->prize_id);
        if (!$prize) {
            $this->error('奖品信息不存在');
        }

        // 检查是否需要付费
        if ($prize->need_certificate && $prize->certificate_fee > 0) {
            // 需要付费，返回费用信息
            $this->success('需要付费获取', [
                'need_pay' => true,
                'fee' => $prize->certificate_fee,
                'certificate_no' => ''
            ]);
        } else {
            // 免费获取，直接生成证件编号
            Db::startTrans();
            try {
                $certificateNo = $this->generateCertificateNo();
                $exchange->certificate_no = $certificateNo;
                $exchange->is_get_certificate = 1;
                $exchange->certificate_fee = 0;
                $exchange->pay_certificate_time = time();
                $exchange->save();

                Db::commit();
                
                $this->success('获取成功', [
                    'certificate_no' => $certificateNo,
                    'fee' => 0
                ]);
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('获取失败，请稍后重试');
            }
        }
    }

    /**
     * 支付证件费用
     * 
     * @ApiMethod (POST)
     * @ApiRoute  (/api/fuka/payCertificate)
     * @ApiParams (name="exchange_id", type="integer", required=true, description="兑换记录ID")
     * @ApiReturn ({'code':'1','msg':'支付成功','data':{'certificate_no':''}})
     */
    public function payCertificate()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $exchangeId = $this->request->param('exchange_id/d', 0);
        if (!$exchangeId) {
            $this->error('兑换记录ID不能为空');
        }

        // 获取兑换记录
        $exchange = FukaExchangeRecord::where('id', $exchangeId)
            ->where('user_id', $user->id)
            ->find();
        
        if (!$exchange) {
            $this->error('兑换记录不存在');
        }

        // 检查是否已支付
        if ($exchange->is_get_certificate == 1) {
            $this->success('已获取', [
                'certificate_no' => $exchange->certificate_no
            ]);
        }

        // 获取奖品信息
        $prize = FukaPrize::get($exchange->prize_id);
        if (!$prize || !$prize->need_certificate || $prize->certificate_fee <= 0) {
            $this->error('该奖品不需要付费获取证件');
        }

        // TODO: 这里应该调用支付接口，暂时直接生成证件编号
        Db::startTrans();
        try {
            $certificateNo = $this->generateCertificateNo();
            $exchange->certificate_no = $certificateNo;
            $exchange->is_get_certificate = 1;
            $exchange->certificate_fee = $prize->certificate_fee;
            $exchange->pay_certificate_time = time();
            $exchange->save();

            Db::commit();
            
            $this->success('支付成功', [
                'certificate_no' => $certificateNo,
                'fee' => $prize->certificate_fee
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('支付失败，请稍后重试');
        }
    }

    /**
     * 生成取件码
     * 
     * @return string
     */
    private function generatePickupCode()
    {
        // 生成4位数字取件码
        return str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 生成证件编号
     * 
     * @return string
     */
    private function generateCertificateNo()
    {
        // 生成证件编号：CERT + 日期 + 随机数
        return 'CERT' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

