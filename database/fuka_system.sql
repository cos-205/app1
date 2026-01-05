-- ============================================
-- 集福卡系统数据库脚本
-- 文档版本：v3.3（层级统计优化版）
-- 生成日期：2026-01-05
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 第一步：扩展现有表
-- ============================================

-- 1.1 扩展 fa_user 表（添加集福卡系统必要字段）
ALTER TABLE `fa_user` 
ADD COLUMN `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名' AFTER `nickname`,
ADD COLUMN `idcard` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号' AFTER `realname`,
ADD COLUMN `is_realname` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否实名认证:1=是,0=否' AFTER `idcard`,
ADD COLUMN `realname_time` bigint DEFAULT NULL COMMENT '实名认证时间' AFTER `is_realname`,
ADD COLUMN `member_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员等级:0=普通,1=铂金,2=黄金,3=钻石,4=黑金,5=至尊' AFTER `level`,
ADD COLUMN `invite_code` varchar(20) NOT NULL DEFAULT '' COMMENT '邀请码' AFTER `member_level`,
ADD COLUMN `signin_days` int unsigned NOT NULL DEFAULT '0' COMMENT '连续签到天数' AFTER `successions`,
ADD COLUMN `last_signin_date` date DEFAULT NULL COMMENT '最后签到日期' AFTER `signin_days`,
ADD COLUMN `withdraw_password` varchar(255) NOT NULL DEFAULT '' COMMENT '提现密码' AFTER `password`,
ADD KEY `invite_code` (`invite_code`),
ADD KEY `member_level` (`member_level`),
ADD KEY `is_realname` (`is_realname`);

-- 1.2 扩展 fa_cus_share 表（添加父级ID树字段）
ALTER TABLE `fa_cus_share`
ADD COLUMN `parent_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '父级ID树:格式1,2,3(从根节点到父节点的路径)' AFTER `share_id`,
ADD COLUMN `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '层级:1=1级,2=2级,3=3级' AFTER `parent_ids`,
ADD KEY `parent_ids` (`parent_ids`(255)),
ADD KEY `level` (`level`);

-- ============================================
-- 第二步：创建新表
-- ============================================

-- 2.1 用户统计表
CREATE TABLE `fa_fuka_user_statistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属团队ID(队长用户ID)',
  `is_team_leader` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否队长:1=是,0=否',
  `total_invite_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '累计邀请人数',
  `valid_invite_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效邀请人数(已实名)',
  `total_fuka_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '累计获得福卡数',
  `current_fuka_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当前福卡数',
  `fuka_chance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '集福机会次数',
  `dividend_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '分红余额',
  `total_dividend` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '累计分红金额',
  `last_update_time` bigint DEFAULT NULL COMMENT '最后更新时间',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `team_id` (`team_id`),
  KEY `is_team_leader` (`is_team_leader`),
  KEY `valid_invite_count` (`valid_invite_count`),
  KEY `current_fuka_count` (`current_fuka_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户统计表';

-- 2.2 签到奖励规则表
CREATE TABLE `fa_fuka_signin_reward_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `days` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '连续签到天数',
  `reward_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖励类型:1=现金奖励,2=集福机会',
  `reward_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '奖励金额(万元)',
  `reward_chance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '奖励集福机会数',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '奖励描述',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  UNIQUE KEY `days` (`days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='签到奖励规则表';

-- 2.3 签到奖励领取记录表
CREATE TABLE `fa_fuka_signin_reward_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `signin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '签到记录ID',
  `rule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '奖励规则ID',
  `days` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '连续签到天数',
  `reward_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖励类型:1=现金奖励,2=集福机会',
  `reward_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '奖励金额(万元)',
  `reward_chance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '奖励集福机会数',
  `is_received` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已领取:1=是,0=否',
  `receive_time` bigint DEFAULT NULL COMMENT '领取时间',
  `wallet_log_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '余额变动记录ID(关联fa_cus_user_wallet_log.id)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `signin_id` (`signin_id`),
  KEY `rule_id` (`rule_id`),
  KEY `is_received` (`is_received`),
  UNIQUE KEY `user_rule_signin` (`user_id`, `rule_id`, `signin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='签到奖励领取记录表';

-- 2.4 会员等级配置表
CREATE TABLE `fa_fuka_member_level` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '等级:0=普通,1=铂金,2=黄金,3=钻石,4=黑金,5=至尊',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '等级名称',
  `invite_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '升级所需邀请人数',
  `can_get_card` tinyint(1) NOT NULL DEFAULT '0' COMMENT '可领取财富金卡:1=是,0=否',
  `dividend_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '每月分红金额(万元)',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '等级图标',
  `bg_color` varchar(20) NOT NULL DEFAULT '' COMMENT '背景色',
  `description` text COMMENT '等级说明',
  `rights` text COMMENT '权益说明:JSON格式',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='集福卡会员等级配置表';

-- 2.5 会员升级记录表
CREATE TABLE `fa_fuka_member_level_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `old_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '原等级:0=普通,1=铂金,2=黄金,3=钻石,4=黑金,5=至尊',
  `new_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '新等级:0=普通,1=铂金,2=黄金,3=钻石,4=黑金,5=至尊',
  `invite_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '升级时的邀请人数',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='会员升级记录表';

-- 2.6 福卡类型表
CREATE TABLE `fa_fuka_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type_code` varchar(20) NOT NULL DEFAULT '' COMMENT '类型编码',
  `type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '类型名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '福卡图标',
  `bg_image` varchar(255) NOT NULL DEFAULT '' COMMENT '背景图',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `is_universal` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否万能福:1=是,0=否',
  `can_buy` tinyint(1) NOT NULL DEFAULT '0' COMMENT '可购买:1=是,0=否',
  `buy_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买价格',
  `drop_rate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '掉落概率:1-10000',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=hidden',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_code` (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='福卡类型表';

-- 2.7 用户福卡记录表
CREATE TABLE `fa_fuka_user_card` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `fuka_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '福卡类型ID',
  `type_code` varchar(20) NOT NULL DEFAULT '' COMMENT '类型编码',
  `type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '类型名称',
  `source_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '获得方式:1=签到,2=邀请,3=任务,4=购买,5=团队,6=抽奖',
  `source_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已使用:1=是,0=否',
  `exchange_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '兑换记录ID',
  `used_time` bigint DEFAULT NULL COMMENT '使用时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fuka_type_id` (`fuka_type_id`),
  KEY `is_used` (`is_used`),
  KEY `user_status` (`user_id`, `is_used`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户福卡记录表';

-- 2.8 集福机会记录表
CREATE TABLE `fa_fuka_chance_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `change_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '变动类型:1=获得,2=使用',
  `change_count` int(10) NOT NULL DEFAULT '0' COMMENT '变动数量',
  `before_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '变动前数量',
  `after_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '变动后数量',
  `source_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '来源:1=签到,2=邀请,3=团队,4=活动,5=购买',
  `source_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来源ID',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='集福机会记录表';

-- 2.9 集福排行榜表
CREATE TABLE `fa_fuka_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `fuka_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '福卡数量',
  `rank` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排名',
  `update_time` bigint NOT NULL DEFAULT '0' COMMENT '更新时间',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `rank` (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='集福排行榜表';

-- 2.10 邀请记录表
CREATE TABLE `fa_fuka_user_invite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `inviter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人ID(对应fa_user.parent_user_id)',
  `inviter_mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '邀请人手机号',
  `inviter_name` varchar(50) NOT NULL DEFAULT '' COMMENT '邀请人姓名',
  `invitee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被邀请人ID',
  `invitee_mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '被邀请人手机号',
  `invitee_name` varchar(50) NOT NULL DEFAULT '' COMMENT '被邀请人姓名',
  `invite_code` varchar(20) NOT NULL DEFAULT '' COMMENT '邀请码',
  `invite_channel` varchar(50) NOT NULL DEFAULT '' COMMENT '邀请渠道',
  `is_realname` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已实名:1=是,0=否',
  `realname_time` bigint DEFAULT NULL COMMENT '实名时间',
  `is_valid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有效邀请:1=是,0=否',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `inviter_id` (`inviter_id`),
  KEY `invitee_id` (`invitee_id`),
  KEY `is_valid` (`is_valid`),
  KEY `inviter_valid` (`inviter_id`, `is_valid`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='邀请记录表';

-- 2.11 团队关系表
CREATE TABLE `fa_fuka_team_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '团队ID(队长用户ID)',
  `leader_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '队长ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成员ID',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '层级:1=1级会员,2=2级会员,3=3级会员',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级ID(对应fa_user.parent_user_id)',
  `is_realname` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已实名:1=是,0=否',
  `join_time` bigint NOT NULL DEFAULT '0' COMMENT '加入时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `leader_id` (`leader_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='团队关系表';

-- 2.12 团队奖励记录表
CREATE TABLE `fa_fuka_team_reward` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '团队ID',
  `leader_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '队长ID',
  `team_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '团队人数',
  `reward_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖励类型:1=20人手机,2=50人现金',
  `reward_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '奖励描述',
  `leader_reward` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '队长奖励金额(万元)',
  `member_reward` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成员奖励金额(万元)',
  `total_reward` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总奖励金额(万元)',
  `is_sent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已发放:1=是,0=否',
  `send_time` bigint DEFAULT NULL COMMENT '发放时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `leader_id` (`leader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='团队奖励记录表';

-- 2.13 金卡流程状态配置表
CREATE TABLE `fa_fuka_card_flow_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `step` tinyint(1) NOT NULL DEFAULT '0' COMMENT '流程步骤:1-8',
  `step_name` varchar(100) NOT NULL DEFAULT '' COMMENT '步骤名称',
  `step_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '步骤描述',
  `need_fee` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要收费:1=是,0=否',
  `fee_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '费用金额',
  `fee_name` varchar(100) NOT NULL DEFAULT '' COMMENT '费用名称',
  `fee_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '费用说明',
  `need_refund` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可退还:1=是,0=否',
  `refund_days` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退还天数(完成后多少天退还)',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序顺序',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  UNIQUE KEY `step` (`step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='金卡流程状态配置表';

-- 2.14 财富金卡表
CREATE TABLE `fa_fuka_wealth_card` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `card_no` varchar(50) NOT NULL DEFAULT '' COMMENT '卡号',
  `holder_name` varchar(50) NOT NULL DEFAULT '' COMMENT '持卡人姓名',
  `holder_idcard` varchar(18) NOT NULL DEFAULT '' COMMENT '持卡人身份证号',
  `card_balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '卡内余额',
  `card_password` varchar(255) NOT NULL DEFAULT '' COMMENT '卡片密码',
  `withdraw_password` varchar(255) NOT NULL DEFAULT '' COMMENT '取款密码',
  `flow_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '流程状态:0=未开始,1-8=对应流程步骤',
  `apply_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '申领状态:0=未申请,1=审核中,2=审核通过,3=审核失败,4=定制中,5=已邮寄,6=已签收',
  `apply_time` bigint DEFAULT NULL COMMENT '申领时间',
  `audit_time` bigint DEFAULT NULL COMMENT '审核时间',
  `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `make_time` bigint DEFAULT NULL COMMENT '制卡时间',
  `logistics_no` varchar(50) NOT NULL DEFAULT '' COMMENT '物流单号',
  `logistics_company` varchar(50) NOT NULL DEFAULT '' COMMENT '物流公司',
  `send_time` bigint DEFAULT NULL COMMENT '邮寄时间',
  `receive_time` bigint DEFAULT NULL COMMENT '签收时间',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否激活:1=是,0=否',
  `active_time` bigint DEFAULT NULL COMMENT '激活时间',
  `large_pay_limit` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '大额收付款限额',
  `is_open_large_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开通大额收付款:1=是,0=否',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `card_no` (`card_no`),
  KEY `flow_status` (`flow_status`),
  KEY `apply_status` (`apply_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='财富金卡表';

-- 2.15 金卡流程记录表
CREATE TABLE `fa_fuka_card_flow_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `card_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金卡ID',
  `flow_step` tinyint(1) NOT NULL DEFAULT '0' COMMENT '流程步骤:1-8',
  `step_name` varchar(100) NOT NULL DEFAULT '' COMMENT '步骤名称',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否完成:1=是,0=否',
  `complete_time` bigint DEFAULT NULL COMMENT '完成时间',
  `need_fee` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要收费:1=是,0=否',
  `fee_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '费用金额',
  `fee_name` varchar(100) NOT NULL DEFAULT '' COMMENT '费用名称',
  `is_pay_fee` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已支付费用:1=是,0=否',
  `pay_fee_time` bigint DEFAULT NULL COMMENT '支付费用时间',
  `pay_trade_no` varchar(100) NOT NULL DEFAULT '' COMMENT '支付交易单号',
  `money_log_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '余额变动记录ID(关联fa_user_money_log.id)',
  `need_refund` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可退还:1=是,0=否',
  `is_refund_fee` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已退还费用:1=是,0=否',
  `refund_fee_time` bigint DEFAULT NULL COMMENT '退还费用时间',
  `refund_trade_no` varchar(100) NOT NULL DEFAULT '' COMMENT '退还交易单号',
  `refund_wallet_log_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退还余额变动记录ID(关联fa_cus_user_wallet_log.id)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `card_id` (`card_id`),
  KEY `flow_step` (`flow_step`),
  KEY `is_completed` (`is_completed`),
  UNIQUE KEY `card_flow_step` (`card_id`, `flow_step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='金卡流程记录表';

-- 2.16 奖品表
CREATE TABLE `fa_fuka_prize` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `prize_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖品类型:1=手机,2=汽车,3=现金,4=其他',
  `prize_image` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品图片',
  `prize_value` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '奖品价值',
  `need_fuka_set` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '需要福卡套数',
  `need_pickup_code` tinyint(1) NOT NULL DEFAULT '0' COMMENT '需要取件码:1=是,0=否',
  `pickup_code_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '取件码费用',
  `need_certificate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '需要证件:1=是,0=否',
  `certificate_type` varchar(50) NOT NULL DEFAULT '' COMMENT '证件类型',
  `certificate_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '证件费用',
  `stock` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '库存数量',
  `exchange_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '兑换次数',
  `description` text COMMENT '奖品说明',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='奖品表';

-- 2.17 兑换记录表
CREATE TABLE `fa_fuka_exchange_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `prize_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '奖品ID',
  `prize_name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `prize_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '奖品类型:1=手机,2=汽车,3=现金,4=其他',
  `fuka_set_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用福卡套数',
  `fuka_ids` text COMMENT '使用的福卡ID列表:JSON格式',
  `exchange_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '兑换状态:0=待审核,1=审核通过,2=定制中,3=待发货,4=已发货,5=已完成,6=已取消',
  `exchange_time` bigint NOT NULL DEFAULT '0' COMMENT '兑换时间',
  `audit_time` bigint DEFAULT NULL COMMENT '审核时间',
  `audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注',
  `logistics_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '物流记录ID',
  `pickup_code` varchar(20) NOT NULL DEFAULT '' COMMENT '取件码',
  `is_get_pickup_code` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否获取取件码:1=是,0=否',
  `pickup_code_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '取件码费用',
  `pay_pickup_time` bigint DEFAULT NULL COMMENT '支付取件码时间',
  `certificate_no` varchar(50) NOT NULL DEFAULT '' COMMENT '证件编号',
  `is_get_certificate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否获取证件:1=是,0=否',
  `certificate_fee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '证件费用',
  `pay_certificate_time` bigint DEFAULT NULL COMMENT '支付证件费用时间',
  `complete_time` bigint DEFAULT NULL COMMENT '完成时间',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `prize_id` (`prize_id`),
  KEY `exchange_status` (`exchange_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='兑换记录表';

-- 2.18 物流记录表
CREATE TABLE `fa_fuka_logistics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单类型:1=福卡兑换,2=财富金卡',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `address_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收货地址ID(关联fa_cus_user_address.id)',
  `logistics_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '物流状态:0=待发货,1=已发货,2=运输中,3=待取件,4=已签收',
  `logistics_company` varchar(50) NOT NULL DEFAULT '' COMMENT '物流公司',
  `logistics_no` varchar(50) NOT NULL DEFAULT '' COMMENT '物流单号',
  `send_time` bigint DEFAULT NULL COMMENT '发货时间',
  `receive_time` bigint DEFAULT NULL COMMENT '签收时间',
  `logistics_info` text COMMENT '物流跟踪信息:JSON格式',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `address_id` (`address_id`),
  KEY `logistics_no` (`logistics_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='物流记录表';

-- 2.19 分红记录表
CREATE TABLE `fa_fuka_dividend_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `member_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员等级:0=普通,1=铂金,2=黄金,3=钻石,4=黑金,5=至尊',
  `dividend_month` varchar(7) NOT NULL DEFAULT '' COMMENT '分红月份:YYYY-MM',
  `dividend_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '分红金额(万元)',
  `send_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发放状态:0=待发放,1=已发放,2=发放失败',
  `send_time` bigint DEFAULT NULL COMMENT '发放时间',
  `send_channel` varchar(50) NOT NULL DEFAULT '' COMMENT '发放渠道:alipay=支付宝',
  `send_account` varchar(100) NOT NULL DEFAULT '' COMMENT '发放账户',
  `trade_no` varchar(100) NOT NULL DEFAULT '' COMMENT '交易单号',
  `fail_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
  `money_log_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '余额变动记录ID(关联fa_user_money_log.id)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重:数值越大越靠前',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dividend_month` (`dividend_month`),
  KEY `send_status` (`send_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分红记录表';

-- ============================================
-- 第三步：插入初始数据
-- ============================================

-- 3.1 签到奖励规则初始数据
INSERT INTO `fa_fuka_signin_reward_rule` (`days`, `reward_type`, `reward_money`, `reward_chance`, `description`, `weigh`, `status`) VALUES
(3, 1, 3.00, 0, '连续签到3天奖励3万元', 100, 'normal'),
(7, 1, 8.00, 0, '连续签到7天奖励8万元', 200, 'normal'),
(15, 1, 20.00, 0, '连续签到15天奖励20万元', 300, 'normal'),
(30, 1, 40.00, 0, '连续签到30天奖励40万元', 400, 'normal');

-- 3.2 会员等级配置初始数据
INSERT INTO `fa_fuka_member_level` (`level`, `name`, `invite_count`, `can_get_card`, `dividend_money`, `weigh`, `status`) VALUES
(0, '普通会员', 0, 0, 0.00, 100, 'normal'),
(1, '铂金会员', 2, 1, 0.00, 200, 'normal'),
(2, '黄金会员', 5, 1, 5.00, 300, 'normal'),
(3, '钻石会员', 10, 1, 12.00, 400, 'normal'),
(4, '黑金会员', 20, 1, 26.00, 500, 'normal'),
(5, '至尊会员', 50, 1, 65.00, 600, 'normal');

-- 3.3 福卡类型初始数据
INSERT INTO `fa_fuka_type` (`type_code`, `type_name`, `is_universal`, `drop_rate`, `weigh`, `status`) VALUES
('aiguo', '爱国福', 0, 1800, 500, 'normal'),
('youshan', '友善福', 0, 2000, 400, 'normal'),
('jingye', '敬业福', 0, 1500, 300, 'normal'),
('hexie', '和谐福', 0, 2000, 200, 'normal'),
('fuqiang', '富强福', 0, 2000, 100, 'normal'),
('wanneng', '万能福', 1, 700, 600, 'normal');

-- 3.4 金卡流程状态配置初始数据
INSERT INTO `fa_fuka_card_flow_config` (`step`, `step_name`, `step_desc`, `need_fee`, `fee_amount`, `fee_name`, `fee_desc`, `need_refund`, `refund_days`, `sort_order`, `weigh`, `status`) VALUES
(1, '使用协议跟金卡', '签署金卡使用协议，确认金卡使用条款', 1, 300.00, '登记费用', '金融管理智光局终端处理及系统收录', 1, 30, 1, 100, 'normal'),
(2, '设置卡片密码', '设置金卡支付密码和取款密码', 0, 0.00, '', '', 0, 0, 2, 200, 'normal'),
(3, '卡片大额收付款功能', '开通大额收付款功能，设置收付款限额', 0, 0.00, '', '', 0, 0, 3, 300, 'normal'),
(4, '签署支付宝保密合同', '签署支付宝保密协议，确认个人信息保护条款', 0, 0.00, '', '', 0, 0, 4, 400, 'normal'),
(5, '财富金卡APP提现至卡片', '在APP中绑定金卡，支持从APP提现到金卡', 0, 0.00, '', '', 0, 0, 5, 500, 'normal'),
(6, '邮寄支付宝会员入场证', '系统自动邮寄支付宝会员入场证，用户收到后激活使用', 0, 0.00, '', '', 0, 0, 6, 600, 'normal'),
(7, '开通微信支付功能', '在金卡上开通微信支付，绑定微信账号', 0, 0.00, '', '', 0, 0, 7, 700, 'normal'),
(8, '开通支付宝支付功能', '在金卡上开通支付宝支付，绑定支付宝账号', 0, 0.00, '', '', 0, 0, 8, 800, 'normal');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 脚本执行完成
-- ============================================

