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
                // 设置关联数据的输出格式为数组
                $row = $item->toArray();
                
                // 递归处理所有关联数据，确保都是数组格式
                $row = $this->convertRelationsToArray($row);
                
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
                \think\Log::error('ExchangeRecord index error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                continue;
            }
        }
        
        $result = ['total' => $list->total(), 'rows' => $rows];
        return json($result);
    }
    
    /**
     * 递归将关联数据转换为数组
     * @param mixed $data
     * @return mixed
     */
    private function convertRelationsToArray($data)
    {
        if (is_object($data)) {
            // 如果是模型对象，转换为数组
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = (array)$data;
            }
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    $data[$key] = $this->convertRelationsToArray($value);
                }
            }
        }
        
        return $data;
    }

}
