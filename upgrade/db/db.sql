USE `naokr`;

ALTER TABLE `aws_users` ADD COLUMN `profile_update_time` int(10) DEFAULT NULL COMMENT '信息更新时间';