-------------------------------------------------------------------------------
-- 2016-03
-------------------------------------------------------------------------------

USE `naokr`;

-- asw_question表中需要添加的表项

ALTER TABLE `aws_question` ADD COLUMN `quiz_poft_ratio` float NOT NULL DEFAULT '0' COMMENT '该问题的一次通过率';

-- aws_account表中需要添加的表项

ALTER TABLE `aws_users` ADD COLUMN `question_quiz_poft_ratio` float NOT NULL DEFAULT '0' COMMENT '该用户答题的一次通过率';

-------------------------------------------------------------------------------
-- 2016-05-14
-------------------------------------------------------------------------------

USE `naokr`;

INSERT INTO `aws_system_setting` (`varname`, `value`) VALUES 
('user_question_invite_recommend', 's:2:"24";'),
('user_question_invite_limit', 's:2:"20";');

-------------------------------------------------------------------------------
-- 2016-05-21
-------------------------------------------------------------------------------

USE `naokr`;

ALTER TABLE `aws_users` ADD COLUMN `is_auto_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为随机密码';