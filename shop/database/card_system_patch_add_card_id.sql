-- 金卡系统补丁：为fa_card_order表添加card_id字段
-- 执行时间：2026-01-07
-- 说明：修复代码中使用card_id但数据库表缺少此字段的问题

-- 添加card_id字段
ALTER TABLE `fa_card_order` 
ADD COLUMN `card_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '金卡ID' AFTER `user_id`,
ADD INDEX `idx_card_id` (`card_id`);

-- 为已存在的订单补充card_id（如果表中有数据）
-- 根据user_id查找对应的card_id
UPDATE `fa_card_order` o
LEFT JOIN `fa_fuka_wealth_card` c ON o.user_id = c.user_id
SET o.card_id = IFNULL(c.id, 0)
WHERE o.card_id = 0;

