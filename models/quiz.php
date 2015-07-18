<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class quiz_class extends AWS_MODEL
{
	public function save_question_quiz($quiz_type, $countdown, $quiz_content)
	{
		switch ($quiz_type) {
			case 'singleSelection':
				$quiz_type_id = 1;

				break;
			case 'multipleSelection':
				$quiz_type_id = 2;

				break;
			case 'crossword':
				$quiz_type_id = 3;

				break;
			case 'textInput':
				$quiz_type_id = 4;

				break;
			default:
				$quiz_type_id = 0;
				break;
		}

		return $this->insert('question_quiz', array(
			'type' => intval($quiz_type_id),
			'countdown' => intval($countdown),
			'content' => $quiz_content
		));
	}

	public function get_question_quiz_info_by_id($quiz_id)
	{
		return $this->fetch_row('question_quiz', 'id = ' . intval($quiz_id));
	}
}
