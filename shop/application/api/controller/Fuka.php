<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\fuka\FukaType;
use app\common\model\fuka\FukaUserCard;
use app\common\model\fuka\FukaUserStatistics;
use app\common\model\fuka\FukaChanceLog;
use app\common\model\fuka\FukaRank;
use app\common\model\fuka\FukaExchangeRecord;
use app\common\model\fuka\FukaPrize;
use app\common\validate\fuka\Fuka as FukaValidate;
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
                $statistics[$typeCode] = [
                    'type_code' => $typeCode,
                    'type_name' => $card->type_name,
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
        if (!empty($params['fuka_type_id'])) {
            $this->validate($params, FukaValidate::class . '.useChance');
        }

        // 获取用户统计信息
        $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
        if (!$userStats || $userStats->fuka_chance <= 0) {
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

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('集福失败，请稍后重试');
        }
        
        $this->success('集福成功', ['card' => $card]);
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
        
        if ($types->isEmpty()) {
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

        // 默认返回第一个
        return $types[0];
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
        $params = $this->request->only(['prize_id']);
        $this->validate($params, FukaValidate::class . '.exchange');
        
        $prizeId = $this->request->param('prize_id/d', 0);

        // 获取奖品信息
        $prize = FukaPrize::get($prizeId);
        if (!$prize || $prize->status != 'normal') {
            $this->error('奖品不存在');
        }

        if ($prize->stock <= 0) {
            $this->error('奖品库存不足');
        }

        // 获取用户未使用的福卡
        $userCards = FukaUserCard::where('user_id', $user->id)
            ->where('is_used', 0)
            ->where('status', 'normal')
            ->select();

        if (count($userCards) < $prize->need_fuka_set * 5) {
            $this->error('福卡数量不足');
        }

        // 检查是否可以组成足够的五福卡
        $canExchange = $this->checkCanExchange($userCards, $prize->need_fuka_set);
        if (!$canExchange) {
            $this->error('无法组成足够的五福卡套数');
        }

        $exchangeRecord = null;
        
        Db::startTrans();
        try {
            // 选择要使用的福卡
            $usedCards = $this->selectCardsForExchange($userCards, $prize->need_fuka_set);
            $usedCardIds = array_column($usedCards, 'id');

            // 创建兑换记录
            $exchangeRecord = new FukaExchangeRecord();
            $exchangeRecord->user_id = $user->id;
            $exchangeRecord->prize_id = $prize->id;
            $exchangeRecord->prize_name = $prize->prize_name;
            $exchangeRecord->prize_type = $prize->prize_type;
            $exchangeRecord->fuka_set_count = $prize->need_fuka_set;
            $exchangeRecord->fuka_ids = json_encode($usedCardIds);
            $exchangeRecord->exchange_status = 0; // 待审核
            $exchangeRecord->exchange_time = time();
            $exchangeRecord->save();

            // 标记福卡为已使用（需要先创建兑换记录以获取ID）
            FukaUserCard::where('id', 'in', $usedCardIds)
                ->update([
                    'is_used' => 1,
                    'used_time' => time(),
                    'exchange_id' => $exchangeRecord->id
                ]);

            // 更新奖品库存
            $prize->stock -= 1;
            $prize->exchange_count += 1;
            $prize->save();

            // 更新用户统计
            $userStats = FukaUserStatistics::where('user_id', $user->id)->find();
            if ($userStats) {
                $userStats->current_fuka_count -= count($usedCards);
                $userStats->save();
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('兑换失败，请稍后重试');
        }
        
        $this->success('兑换成功', ['exchange_record' => $exchangeRecord]);
    }

    /**
     * 检查是否可以兑换
     * 
     * @param array $cards 用户福卡列表
     * @param int $needSets 需要的套数
     * @return bool
     */
    private function checkCanExchange($cards, $needSets)
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
     * 获取兑换记录列表
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/exchangeList)
     * @ApiParams (name="page", type="integer", required=false, description="页码")
     * @ApiParams (name="limit", type="integer", required=false, description="每页数量")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[],'total':0}})
     */
    public function exchangeList()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请先登录');
        }

        $list = FukaExchangeRecord::where('user_id', $user->id)
            ->with(['prize'])
            ->order('createtime desc')
            ->paginate();

        $this->success('获取成功', $list);
    }

    /**
     * 获取集福排行榜
     * 
     * @ApiMethod (GET)
     * @ApiRoute  (/api/fuka/rankList)
     * @ApiParams (name="limit", type="integer", required=false, description="返回数量，默认5")
     * @ApiReturn ({'code':'1','msg':'获取成功','data':{'list':[]}})
     */
    public function rankList()
    {
        $limit = $this->request->param('limit/d', 5);

        $list = FukaRank::where('status', 'normal')
            ->order('rank asc')
            ->limit($limit)
            ->select();

        $this->success('获取成功', ['list' => $list]);
    }
}

