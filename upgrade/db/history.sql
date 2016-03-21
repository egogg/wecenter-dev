-------------------------------------------------------------------------------
-- 2016-03
-------------------------------------------------------------------------------

USE `naokr`;

-- asw_question表中需要添加的表项

ALTER TABLE `aws_question` ADD COLUMN `quiz_poft_ratio` float NOT NULL DEFAULT '0' COMMENT '该问题的一次通过率';

-- aws_account表中需要添加的表项

ALTER TABLE `aws_users` ADD COLUMN `question_quiz_poft_ratio` float NOT NULL DEFAULT '0' COMMENT '该用户答题的一次通过率';
