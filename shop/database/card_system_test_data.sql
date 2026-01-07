-- 金卡系统测试数据生成脚本
-- 生成时间：2026-01-07
-- 说明：生成完整的测试数据，包括用户、金卡、订单、流程记录等

-- ====================================
-- 1. 测试用户（如果不存在则创建）
-- ====================================

-- 测试用户1：已完成步骤1
INSERT INTO `fa_user` (`id`, `username`, `nickname`, `mobile`, `realname`, `idcard`, `member_level`, `is_realname`, `money`, `createtime`, `updatetime`)
VALUES (10001, 'test_user_1', '测试用户1', '13800138001', '张三', '110101199001011234', 1, 1, 5000.00, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 测试用户2：已完成步骤1-3
INSERT INTO `fa_user` (`id`, `username`, `nickname`, `mobile`, `realname`, `idcard`, `member_level`, `is_realname`, `money`, `createtime`, `updatetime`)
VALUES (10002, 'test_user_2', '测试用户2', '13800138002', '李四', '110101199002021234', 1, 1, 8000.00, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 测试用户3：刚申请金卡
INSERT INTO `fa_user` (`id`, `username`, `nickname`, `mobile`, `realname`, `idcard`, `member_level`, `is_realname`, `money`, `createtime`, `updatetime`)
VALUES (10003, 'test_user_3', '测试用户3', '13800138003', '王五', '110101199003031234', 1, 1, 3000.00, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- ====================================
-- 2. 金卡记录
-- ====================================

-- 用户1的金卡（完成步骤1）
INSERT INTO `fa_fuka_wealth_card` (`id`, `user_id`, `holder_name`, `holder_idcard`, `flow_status`, `apply_status`, `apply_time`, `createtime`, `updatetime`)
VALUES (1001, 10001, '张三', '110101199001011234', 1, 2, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2的金卡（完成步骤3）
INSERT INTO `fa_fuka_wealth_card` (`id`, `user_id`, `holder_name`, `holder_idcard`, `flow_status`, `apply_status`, `apply_time`, `createtime`, `updatetime`)
VALUES (1002, 10002, '李四', '110101199002021234', 3, 2, UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户3的金卡（刚申请）
INSERT INTO `fa_fuka_wealth_card` (`id`, `user_id`, `holder_name`, `holder_idcard`, `flow_status`, `apply_status`, `apply_time`, `createtime`, `updatetime`)
VALUES (1003, 10003, '王五', '110101199003031234', 0, 2, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- ====================================
-- 3. 支付订单
-- ====================================

-- 用户1 - 步骤1已支付
INSERT INTO `fa_card_order` (`id`, `order_no`, `user_id`, `card_id`, `step_id`, `step_name`, `amount`, `pay_type`, `pay_status`, `pay_time`, `transaction_id`, `createtime`, `updatetime`)
VALUES (10001, 'CO20260107100001', 10001, 1001, 1, '协议签署', 300.00, 'wechat', 1, UNIX_TIMESTAMP() - 80000, 'WX20260107100001', UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 80000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤1已支付
INSERT INTO `fa_card_order` (`id`, `order_no`, `user_id`, `card_id`, `step_id`, `step_name`, `amount`, `pay_type`, `pay_status`, `pay_time`, `transaction_id`, `createtime`, `updatetime`)
VALUES (10002, 'CO20260107100002', 10002, 1002, 1, '协议签署', 300.00, 'alipay', 1, UNIX_TIMESTAMP() - 160000, 'ALI20260107100002', UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP() - 160000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤2已支付
INSERT INTO `fa_card_order` (`id`, `order_no`, `user_id`, `card_id`, `step_id`, `step_name`, `amount`, `pay_type`, `pay_status`, `pay_time`, `transaction_id`, `createtime`, `updatetime`)
VALUES (10003, 'CO20260107100003', 10002, 1002, 2, '使用协议跟金卡', 150.00, 'wechat', 1, UNIX_TIMESTAMP() - 100000, 'WX20260107100003', UNIX_TIMESTAMP() - 120000, UNIX_TIMESTAMP() - 100000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤3已支付
INSERT INTO `fa_card_order` (`id`, `order_no`, `user_id`, `card_id`, `step_id`, `step_name`, `amount`, `pay_type`, `pay_status`, `pay_time`, `transaction_id`, `createtime`, `updatetime`)
VALUES (10004, 'CO20260107100004', 10002, 1002, 3, '设置卡片密码', 200.00, 'unionpay', 1, UNIX_TIMESTAMP() - 40000, 'UP20260107100004', UNIX_TIMESTAMP() - 60000, UNIX_TIMESTAMP() - 40000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户3 - 步骤1待支付
INSERT INTO `fa_card_order` (`id`, `order_no`, `user_id`, `card_id`, `step_id`, `step_name`, `amount`, `pay_type`, `pay_status`, `createtime`, `updatetime`)
VALUES (10005, 'CO20260107100005', 10003, 1003, 1, '协议签署', 300.00, '', 0, UNIX_TIMESTAMP() - 1800, UNIX_TIMESTAMP() - 1800)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- ====================================
-- 4. 流程记录
-- ====================================

-- 用户1 - 步骤1（已支付待审核）
INSERT INTO `fa_fuka_card_flow_log` (`id`, `user_id`, `card_id`, `flow_step`, `step_name`, `flow_status`, `order_id`, `need_fee`, `fee_amount`, `fee_name`, `is_pay_fee`, `pay_fee_time`, `pay_trade_no`, `extra_data`, `createtime`, `updatetime`)
VALUES (1001, 10001, 1001, 1, '协议签署', 2, 10001, 1, 300.00, '协议签署费用', 1, UNIX_TIMESTAMP() - 80000, 'WX20260107100001', '{"agreement_signed":true}', UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 80000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤1（已完成）
INSERT INTO `fa_fuka_card_flow_log` (`id`, `user_id`, `card_id`, `flow_step`, `step_name`, `flow_status`, `order_id`, `need_fee`, `fee_amount`, `fee_name`, `is_pay_fee`, `pay_fee_time`, `pay_trade_no`, `extra_data`, `createtime`, `updatetime`)
VALUES (1002, 10002, 1002, 1, '协议签署', 3, 10002, 1, 300.00, '协议签署费用', 1, UNIX_TIMESTAMP() - 160000, 'ALI20260107100002', '{"agreement_signed":true}', UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP() - 150000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤2（已完成）
INSERT INTO `fa_fuka_card_flow_log` (`id`, `user_id`, `card_id`, `flow_step`, `step_name`, `flow_status`, `order_id`, `need_fee`, `fee_amount`, `fee_name`, `is_pay_fee`, `pay_fee_time`, `pay_trade_no`, `createtime`, `updatetime`)
VALUES (1003, 10002, 1002, 2, '使用协议跟金卡', 3, 10003, 1, 150.00, '使用协议跟金卡费用', 1, UNIX_TIMESTAMP() - 100000, 'WX20260107100003', UNIX_TIMESTAMP() - 120000, UNIX_TIMESTAMP() - 90000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 用户2 - 步骤3（已支付待审核）
INSERT INTO `fa_fuka_card_flow_log` (`id`, `user_id`, `card_id`, `flow_step`, `step_name`, `flow_status`, `order_id`, `need_fee`, `fee_amount`, `fee_name`, `is_pay_fee`, `pay_fee_time`, `pay_trade_no`, `extra_data`, `createtime`, `updatetime`)
VALUES (1004, 10002, 1002, 3, '设置卡片密码', 2, 10004, 1, 200.00, '设置卡片密码费用', 1, UNIX_TIMESTAMP() - 40000, 'UP20260107100004', '{"card_password":"123456"}', UNIX_TIMESTAMP() - 60000, UNIX_TIMESTAMP() - 40000)
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- ====================================
-- 5. 协议流程记录（用户1的步骤1）
-- ====================================

INSERT INTO `fa_user_agreement_flow` (`id`, `user_id`, `step_id`, `flow_step`, `status`, `start_time`, `completed_time`, `createtime`, `updatetime`)
VALUES 
(1001, 10001, 1, 1, 2, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 80000, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 80000),
(1002, 10001, 1, 2, 1, UNIX_TIMESTAMP() - 80000, NULL, UNIX_TIMESTAMP() - 80000, UNIX_TIMESTAMP()),
(1003, 10001, 1, 3, 0, NULL, NULL, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(1004, 10001, 1, 4, 0, NULL, NULL, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(1005, 10001, 1, 5, 0, NULL, NULL, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- ====================================
-- 6. 查询测试数据
-- ====================================

-- 查看所有测试用户
SELECT 
    id, username, nickname, realname, member_level, is_realname, money
FROM fa_user 
WHERE id IN (10001, 10002, 10003);

-- 查看所有测试金卡
SELECT 
    id, user_id, holder_name, flow_status, apply_status, 
    FROM_UNIXTIME(apply_time) as apply_time
FROM fa_fuka_wealth_card 
WHERE id IN (1001, 1002, 1003);

-- 查看所有测试订单
SELECT 
    id, order_no, user_id, step_id, step_name, amount, 
    pay_type, pay_status, transaction_id,
    FROM_UNIXTIME(createtime) as create_time,
    FROM_UNIXTIME(pay_time) as pay_time
FROM fa_card_order 
WHERE id >= 10001;

-- 查看所有流程记录
SELECT 
    id, user_id, card_id, flow_step, step_name, flow_status,
    fee_amount, is_pay_fee, pay_trade_no,
    FROM_UNIXTIME(createtime) as create_time
FROM fa_fuka_card_flow_log 
WHERE id >= 1001;

-- 查看协议流程
SELECT 
    id, user_id, flow_step, status,
    FROM_UNIXTIME(start_time) as start_time,
    FROM_UNIXTIME(completed_time) as completed_time
FROM fa_user_agreement_flow 
WHERE id >= 1001;

-- ====================================
-- 7. 统计信息
-- ====================================

SELECT '=== 数据统计 ===' as info;

SELECT 
    '测试用户' as type,
    COUNT(*) as count
FROM fa_user WHERE id >= 10001
UNION ALL
SELECT 
    '测试金卡' as type,
    COUNT(*) as count
FROM fa_fuka_wealth_card WHERE id >= 1001
UNION ALL
SELECT 
    '测试订单' as type,
    COUNT(*) as count
FROM fa_card_order WHERE id >= 10001
UNION ALL
SELECT 
    '流程记录' as type,
    COUNT(*) as count
FROM fa_fuka_card_flow_log WHERE id >= 1001
UNION ALL
SELECT 
    '协议流程' as type,
    COUNT(*) as count
FROM fa_user_agreement_flow WHERE id >= 1001;

SELECT '=== 测试场景 ===' as info;

SELECT 
    CASE u.id
        WHEN 10001 THEN '用户1：步骤1待审核'
        WHEN 10002 THEN '用户2：步骤1-2已完成，步骤3待审核'
        WHEN 10003 THEN '用户3：步骤1待支付'
    END as scenario,
    u.username,
    u.realname,
    c.flow_status,
    COUNT(o.id) as order_count,
    SUM(CASE WHEN o.pay_status = 1 THEN o.amount ELSE 0 END) as paid_amount
FROM fa_user u
LEFT JOIN fa_fuka_wealth_card c ON u.id = c.user_id
LEFT JOIN fa_card_order o ON u.id = o.user_id
WHERE u.id IN (10001, 10002, 10003)
GROUP BY u.id, u.username, u.realname, c.flow_status
ORDER BY u.id;

