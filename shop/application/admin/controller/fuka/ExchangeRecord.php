<?php

namespace app\admin\controller\fuka;

use app\common\controller\Backend;

/**
 * 兑换记录管理
 *
 * @icon fa fa-circle-o
 */
class ExchangeRecord extends Backend
{

    /**
     * ExchangeRecord模型对象
     * @var \app\admin\model\fuka\ExchangeRecord
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fuka\ExchangeRecord;
        $this->view->assign("prizeTypeList", $this->model->getPrizeTypeList());
        $this->view->assign("exchangeStatusList", $this->model->getExchangeStatusList());
        $this->view->assign("isGetPickupCodeList", $this->model->getIsGetPickupCodeList());
        $this->view->assign("isGetCertificateList", $this->model->getIsGetCertificateList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 查看（重写index方法以支持关联查询）
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        
        // 使用with预加载关联数据，避免N+1查询
        $list = $this->model
            ->with(['user', 'prize', 'wufuCards'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        
        // 将模型集合转换为数组，确保关联数据和访问器属性正确序列化
        $rows = [];
        foreach ($list->items() as $item) {
            try {
                // 使用 toArray 方法，它会自动处理关联数据和访问器
                // ThinkPHP 的 toArray 会自动处理关联数据
                $row = $item->toArray();
                
                // 处理嵌套的关联数据，确保它们都是数组格式
                // 处理 wufuCards 关联（可能是集合，支持驼峰和下划线命名）
                $wufuCardsKey = null;
                if (isset($row['wufu_cards'])) {
                    $wufuCardsKey = 'wufu_cards';
                } elseif (isset($row['wufuCards'])) {
                    $wufuCardsKey = 'wufuCards';
                }
                
                if ($wufuCardsKey) {
                    $wufuCardsValue = $row[$wufuCardsKey];
                    if (is_object($wufuCardsValue)) {
                        // 如果是集合或模型对象，转换为数组
                        if (method_exists($wufuCardsValue, 'toArray')) {
                            $wufuCards = $wufuCardsValue->toArray();
                        } elseif ($wufuCardsValue instanceof \think\Collection) {
                            $wufuCards = $wufuCardsValue->toArray();
                        } else {
                            $wufuCards = [];
                            foreach ($wufuCardsValue as $card) {
                                $cardData = is_object($card) ? (method_exists($card, 'toArray') ? $card->toArray() : (array)$card) : $card;
                                // 处理嵌套的 type 关联
                                if (isset($cardData['type']) && is_object($cardData['type'])) {
                                    $cardData['type'] = method_exists($cardData['type'], 'toArray') ? $cardData['type']->toArray() : (array)$cardData['type'];
                                }
                                $wufuCards[] = $cardData;
                            }
                        }
                        $row[$wufuCardsKey] = $wufuCards;
                        // 同时设置下划线版本，确保兼容性
                        if ($wufuCardsKey === 'wufuCards') {
                            $row['wufu_cards'] = $wufuCards;
                        }
                    } elseif (!is_array($wufuCardsValue)) {
                        // 如果不是数组也不是对象，设置为空数组
                        $row[$wufuCardsKey] = [];
                    }
                }
                
                // 确保 user 和 prize 是数组（支持驼峰和下划线命名）
                $userKey = isset($row['user']) ? 'user' : (isset($row['User']) ? 'User' : null);
                if ($userKey && is_object($row[$userKey])) {
                    $row[$userKey] = $row[$userKey]->toArray();
                    if ($userKey === 'User') {
                        $row['user'] = $row['User'];
                    }
                }
                
                $prizeKey = isset($row['prize']) ? 'prize' : (isset($row['Prize']) ? 'Prize' : null);
                if ($prizeKey && is_object($row[$prizeKey])) {
                    $row[$prizeKey] = $row[$prizeKey]->toArray();
                    if ($prizeKey === 'Prize') {
                        $row['prize'] = $row['Prize'];
                    }
                }
                
                // 格式化费用字段（如果模型中没有定义访问器，在这里处理）
                if (isset($row['pickup_code_fee']) && !isset($row['pickup_code_fee_text'])) {
                    $row['pickup_code_fee_text'] = '¥' . number_format((float)$row['pickup_code_fee'], 2);
                }
                if (isset($row['certificate_fee']) && !isset($row['certificate_fee_text'])) {
                    $row['certificate_fee_text'] = '¥' . number_format((float)$row['certificate_fee'], 2);
                }
                
                $rows[] = $row;
            } catch (\Exception $e) {
                // 如果某条记录处理失败，记录错误但继续处理其他记录
                output_log('error', 'ExchangeRecord index error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                continue;
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $rows];
        
        // 测试 JSON 编码，如果失败则记录错误
        $json = json_encode($result, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            output_log('error', 'ExchangeRecord JSON encode error: ' . json_last_error_msg());
            // 如果编码失败，尝试清理数据后重试
            $result = $this->cleanDataForJson($result);
            $json = json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        
        return json($result);
    }
    
    /**
     * 清理数据，移除无法序列化的内容
     * @param mixed $data
     * @return mixed
     */
    private function cleanDataForJson($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if (is_resource($value)) {
                    continue; // 跳过资源类型
                }
                if (is_object($value)) {
                    // 尝试转换为数组
                    if (method_exists($value, 'toArray')) {
                        $value = $value->toArray();
                    } else {
                        continue; // 无法转换的对象跳过
                    }
                }
                if (is_array($value)) {
                    $value = $this->cleanDataForJson($value);
                }
                $result[$key] = $value;
            }
            return $result;
        }
        return $data;
    }

}
