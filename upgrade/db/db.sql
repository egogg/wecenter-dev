USE `naokr`;

ALTER TABLE `aws_users` ADD COLUMN `is_auto_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为随机密码';