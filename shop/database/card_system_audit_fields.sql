-- 金卡系统审核字段补充
-- 为 fa_fuka_card_flow_log 添加审核相关字段
-- 注意：audit_time 和 auditor_id 字段已存在，只需添加 audit_remark

-- 检查是否已有 audit_remark 字段
SELECT COUNT(*) as has_audit_remark 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'fund_shop' 
  AND TABLE_NAME = 'fa_fuka_card_flow_log' 
  AND COLUMN_NAME = 'audit_remark';

-- 如果 audit_remark 不存在则添加
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = 'fund_shop' 
           AND TABLE_NAME = 'fa_fuka_card_flow_log' 
           AND COLUMN_NAME = 'audit_remark') = 0,
        'ALTER TABLE `fa_fuka_card_flow_log` ADD COLUMN `audit_remark` VARCHAR(500) DEFAULT \'\' COMMENT \'审核备注\' AFTER `audit_time`',
        'SELECT "audit_remark 字段已存在" as message'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 查看更新后的表结构
DESCRIBE `fa_fuka_card_flow_log`;

