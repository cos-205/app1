-- ===================================================================
-- 金卡流程系统数据库更新脚本
-- 版本：v1.0
-- 日期：2026-01-07
-- 说明：此脚本用于更新金卡流程系统的数据库结构和数据
-- ===================================================================

-- ===================================================================
-- 第一部分：修改现有表结构
-- ===================================================================

-- 1. 修改流程配置表，添加缺失字段
ALTER TABLE `fa_fuka_card_flow_config` 
  ADD COLUMN `step_type` CHAR(1) DEFAULT 'B' COMMENT '步骤类型:A=需要界面,B=只需支付' AFTER `step`,
  ADD COLUMN `button_text` VARCHAR(100) DEFAULT '' COMMENT '按钮文案' AFTER `step_desc`,
  ADD COLUMN `completed_text` VARCHAR(50) DEFAULT '' COMMENT '完成状态文案' AFTER `button_text`,
  ADD COLUMN `completed_title` VARCHAR(100) DEFAULT '' COMMENT '完成弹窗标题' AFTER `completed_text`,
  ADD COLUMN `scene_desc` TEXT COMMENT '使用场景说明' AFTER `completed_title`,
  ADD COLUMN `fee_receiver` VARCHAR(100) DEFAULT '' COMMENT '收费机构' AFTER `fee_desc`,
  ADD COLUMN `fee_purpose` VARCHAR(255) DEFAULT '' COMMENT '费用用途' AFTER `fee_receiver`,
  ADD COLUMN `refund_rule` VARCHAR(255) DEFAULT '' COMMENT '退还规则' AFTER `need_refund`;

-- 修改 step 字段注释（从1-8改为1-9）
ALTER TABLE `fa_fuka_card_flow_config` 
  MODIFY COLUMN `step` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '流程步骤:1-9';

-- 2. 修改流程记录表，添加缺失字段
ALTER TABLE `fa_fuka_card_flow_log` 
  ADD COLUMN `status` TINYINT(1) DEFAULT 1 COMMENT '状态:0=锁定,1=未支付,2=已支付待审核,3=已完成' AFTER `step_name`,
  ADD COLUMN `order_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '关联订单ID' AFTER `card_id`,
  ADD COLUMN `extra_data` TEXT COMMENT '额外数据(JSON格式)' AFTER `status`,
  ADD COLUMN `auditor_id` INT(11) UNSIGNED DEFAULT 0 COMMENT '审核人ID' AFTER `complete_time`,
  ADD COLUMN `audit_time` BIGINT(20) DEFAULT NULL COMMENT '审核时间' AFTER `auditor_id`;

-- 修改 flow_step 字段注释
ALTER TABLE `fa_fuka_card_flow_log` 
  MODIFY COLUMN `flow_step` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '流程步骤:1-9';

-- 添加索引
ALTER TABLE `fa_fuka_card_flow_log` 
  ADD INDEX `status` (`status`),
  ADD INDEX `order_id` (`order_id`);

-- ===================================================================
-- 第二部分：创建新表
-- ===================================================================

-- 3. 创建支付订单表
CREATE TABLE IF NOT EXISTS `fa_card_order` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `step_id` INT(11) NOT NULL DEFAULT 0 COMMENT '步骤ID',
  `step_name` VARCHAR(50) DEFAULT '' COMMENT '步骤名称',
  `amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '金额',
  `pay_type` VARCHAR(20) DEFAULT '' COMMENT '支付方式:wechat/alipay/unionpay',
  `pay_status` TINYINT(1) DEFAULT 0 COMMENT '支付状态:0=未支付,1=已支付,2=已退款',
  `pay_time` BIGINT(20) DEFAULT NULL COMMENT '支付时间',
  `transaction_id` VARCHAR(100) DEFAULT '' COMMENT '第三方交易号',
  `pay_url` VARCHAR(500) DEFAULT '' COMMENT '支付链接',
  `refund_status` TINYINT(1) DEFAULT 0 COMMENT '退款状态:0=未退款,1=退款中,2=已退款',
  `refund_time` BIGINT(20) DEFAULT NULL COMMENT '退款时间',
  `refund_transaction_id` VARCHAR(100) DEFAULT '' COMMENT '退款交易号',
  `refund_amount` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '退款金额',
  `createtime` BIGINT(20) DEFAULT NULL COMMENT '创建时间',
  `updatetime` BIGINT(20) DEFAULT NULL COMMENT '更新时间',
  `deletetime` BIGINT(20) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `step_id` (`step_id`),
  KEY `pay_status` (`pay_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金卡支付订单表';

-- 4. 创建协议流程配置表
CREATE TABLE IF NOT EXISTS `fa_card_agreement_flow` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `step_id` INT(11) NOT NULL DEFAULT 0 COMMENT '关联的金卡步骤ID',
  `flow_step` INT(11) NOT NULL DEFAULT 0 COMMENT '流程步骤:1-5',
  `flow_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '流程名称',
  `flow_desc` VARCHAR(255) DEFAULT '' COMMENT '流程描述',
  `duration` VARCHAR(20) DEFAULT '' COMMENT '预计时间',
  `sort` INT(11) DEFAULT 0 COMMENT '排序',
  `status` ENUM('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `createtime` BIGINT(20) DEFAULT NULL COMMENT '创建时间',
  `updatetime` BIGINT(20) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `step_id` (`step_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='协议流程配置表';

-- 5. 创建用户协议流程记录表
CREATE TABLE IF NOT EXISTS `fa_user_agreement_flow` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `step_id` INT(11) NOT NULL DEFAULT 0 COMMENT '关联的金卡步骤ID',
  `flow_step` INT(11) NOT NULL DEFAULT 0 COMMENT '流程步骤:1-5',
  `status` TINYINT(1) DEFAULT 0 COMMENT '状态:0=未开始,1=进行中,2=已完成',
  `start_time` BIGINT(20) DEFAULT NULL COMMENT '开始时间',
  `completed_time` BIGINT(20) DEFAULT NULL COMMENT '完成时间',
  `remark` VARCHAR(255) DEFAULT '' COMMENT '备注',
  `createtime` BIGINT(20) DEFAULT NULL COMMENT '创建时间',
  `updatetime` BIGINT(20) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_step` (`user_id`,`step_id`),
  KEY `flow_step` (`flow_step`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户协议流程记录表';

-- ===================================================================
-- 第三部分：更新流程配置数据
-- ===================================================================

-- 6. 清空现有配置数据
DELETE FROM `fa_fuka_card_flow_config`;

-- 7. 插入9个步骤的完整配置
INSERT INTO `fa_fuka_card_flow_config` 
(`step`, `step_type`, `step_name`, `step_desc`, `button_text`, `completed_text`, `completed_title`, 
 `scene_desc`, `need_fee`, `fee_amount`, `fee_name`, `fee_desc`, `fee_receiver`, `fee_purpose`, 
 `need_refund`, `refund_days`, `refund_rule`, `sort_order`, `weigh`, `status`, `createtime`, `updatetime`) 
VALUES
-- 步骤1：协议签署（A类）
(1, 'A', '协议签署', '签署金卡使用协议', '签署协议', '已签署', '协议签署成功！',
 '此步骤将为您签署金卡使用协议，确保您的金卡使用符合相关规定和条款。协议内容包括：金卡使用规范、账户安全责任、资金保障说明、服务条款确认。完成此步骤后，您的金卡将正式激活使用权限。',
 1, 300.00, '协议签署费用', '用于协议处理、邮寄、系统收录', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 1, 100, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤2：使用协议跟金卡（B类）
(2, 'B', '使用协议跟金卡', '确认金卡使用条款', '确认协议', '已确认', '协议确认成功！',
 '此步骤将为您的金卡绑定使用协议，确保您的金卡使用符合相关规定和条款。协议内容包括：金卡使用规范、账户安全责任、资金保障说明、服务条款确认。完成此步骤后，您的金卡将正式激活使用权限，可以进行后续功能开通。',
 1, 150.00, '协议确认费用', '用于协议绑定和系统确认', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 2, 200, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤3：设置卡片密码（A类）
(3, 'A', '设置卡片密码', '设置支付密码和取款密码', '设置密码', '已设置', '密码设置成功！',
 '此步骤将为您的金卡设置支付密码和取款密码，保障您的资金安全。密码设置完成后，您可以使用金卡进行各种交易操作。请牢记您的密码，不要泄露给他人。',
 1, 200.00, '密码设置服务费', '用于密码加密和安全验证', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 3, 300, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤4：大额收付款功能（A类）
(4, 'A', '大额收付款功能', '开通大额收付款功能', '开通功能', '已开通', '收付款功能开通成功！',
 '此步骤将为您的金卡开通大额收付款功能，您可以进行单笔最高100万元的转账。功能包括：大额转账、大额收款、实时到账、安全可靠。完成此步骤后，您需要设置单笔限额和日累计限额。',
 1, 500.00, '大额收付款服务费', '用于功能开通和额度配置', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 4, 400, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤5：签署支付宝保密合同（B类）
(5, 'B', '签署支付宝保密合同', '确认个人信息保护条款', '签署合同', '已签署', '保密合同签署成功！',
 '此步骤将为您的金卡签署支付宝保密协议，确保您的个人信息和资金安全。协议内容包括：个人信息保护承诺、资金隐私保障、交易信息加密、数据安全规范。完成此步骤后，您的金卡与支付宝账户将建立安全连接，可以使用支付宝相关功能。',
 1, 200.00, '保密合同签署费', '用于合同签署和信息保护', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 5, 500, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤6：财富金卡APP提现至卡片（B类）
(6, 'B', '财富金卡APP提现至卡片', '绑定金卡到APP', '开通提现', '已开通', '提现功能开通成功！',
 '此步骤将开通金卡与APP的提现通道，您可以在APP中直接将余额提现到金卡。功能包括：APP余额快速提现、实时到账金卡账户、支持批量提现、提现记录查询。完成此步骤后，您可以在APP中绑定金卡，随时进行资金提现操作。',
 1, 300.00, 'APP提现服务费', '用于提现通道开通和系统对接', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 6, 600, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤7：邮寄支付宝会员入场证（B类）
(7, 'B', '邮寄支付宝会员入场证', '邮寄会员专属凭证', '申请邮寄', '已邮寄', '邮寄申请成功！',
 '此步骤将为您邮寄支付宝会员专属入场证，这是您作为金卡会员的身份凭证。凭证包括：实体会员卡、专属会员编号、会员权益手册、线下活动入场券。完成此步骤后，我们将在3-5个工作日内为您邮寄入场证到您的收货地址。',
 1, 100.00, '会员凭证邮寄费', '包含制作费和邮寄费用', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 7, 700, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤8：开通微信支付功能（B类）
(8, 'B', '开通微信支付功能', '绑定微信支付', '开通微信支付', '已开通', '微信支付已开通！',
 '此步骤将为您的金卡开通微信支付功能，您可以在微信中使用金卡进行支付。功能包括：微信扫码支付、微信转账收款、微信红包提现、微信消费积分。完成此步骤后，您需要在微信中绑定金卡，即可使用微信支付相关功能。',
 1, 250.00, '微信支付服务费', '用于微信支付通道开通', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 8, 800, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 步骤9：开通支付宝支付功能（B类）
(9, 'B', '开通支付宝支付功能', '绑定支付宝支付', '开通支付宝支付', '已开通', '支付宝支付已开通！',
 '此步骤将为您的金卡开通支付宝支付功能，您可以在支付宝中使用金卡进行支付。功能包括：支付宝扫码支付、支付宝转账收款、余额宝互转、花呗还款。完成此步骤后，您需要在支付宝中绑定金卡，即可使用支付宝支付相关功能。',
 1, 250.00, '支付宝支付服务费', '用于支付宝支付通道开通', '金融管理监督总局', '终端处理及系统收录',
 1, 30, '所有步骤完成1个月后退还', 9, 900, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 8. 插入协议流程配置数据（为步骤1配置5个协议处理流程）
INSERT INTO `fa_card_agreement_flow` 
(`step_id`, `flow_step`, `flow_name`, `flow_desc`, `duration`, `sort`, `status`, `createtime`, `updatetime`)
VALUES
(1, 1, '协议签署', '签署财富金卡使用协议', '1-2天', 1, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 2, '邮寄处理', '协议文件邮寄至金融管理监督总局', '3-5天', 2, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 3, '系统录入', '录入金融监督总局系统', '5-7天', 3, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 4, '审核完成', '协议审核通过，可合法使用', '1-2天', 4, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 5, '费用退还', '300元签署费全额退还', '30天内', 5, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ===================================================================
-- 更新完成
-- ===================================================================

SELECT '====================================================================' AS '';
SELECT '金卡流程系统数据库更新完成！' AS '执行结果';
SELECT '====================================================================' AS '';
SELECT '已完成以下操作：' AS '';
SELECT '1. 修改流程配置表，添加8个字段' AS '';
SELECT '2. 修改流程记录表，添加5个字段和2个索引' AS '';
SELECT '3. 创建支付订单表' AS '';
SELECT '4. 创建协议流程配置表' AS '';
SELECT '5. 创建用户协议流程记录表' AS '';
SELECT '6. 更新流程配置数据（9个步骤）' AS '';
SELECT '7. 插入协议流程配置数据（5个阶段）' AS '';
SELECT '====================================================================' AS '';
SELECT '请使用以下命令验证：' AS '';
SELECT 'bash deploy.sh dev db-tables | grep -i card' AS '';
SELECT 'bash deploy.sh dev db-show fa_fuka_card_flow_config 20' AS '';
SELECT '====================================================================' AS '';

