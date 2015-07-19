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
	private function get_quiz_type_id($quiz_type) 
	{
		switch ($quiz_type) {
			case 'singleSelection':

				return 1;
			case 'multipleSelection':
				return 2;
			case 'crossword':
				return 3;
			case 'textInput':
				return 4;
			default:
				return 0;
		}

		return 0;
	}

	public function save_question_quiz($quiz_type, $countdown, $quiz_content)
	{
		return $this->insert('question_quiz', array(
			'type' => intval($this->get_quiz_type_id($quiz_type)),
			'countdown' => intval($countdown),
			'content' => $quiz_content
		));
	}

	public function update_question_quiz($quiz_id, $quiz_type, $countdown, $quiz_content)
	{
		if (!$quesion_quiz_info = $this->get_question_quiz_info_by_id($quiz_id))
		{
			return false;
		}

		$quiz_type_id = $this->get_quiz_type_id($quiz_type);
		if($quesion_quiz_info['type'] != $quiz_type_id)
		{
			$this->update('question_quiz', array(
				'type' => intval($quiz_type_id)
			), 'id = ' . intval($quiz_id));
		}

		if($quesion_quiz_info['countdown'] != $countdown)
		{
			$this->update('question_quiz', array(
				'countdown' => intval($countdown)
			), 'id = ' . intval($quiz_id));
		}

		if($quesion_quiz_info['content'] != $quiz_content)
		{
			$this->update('question_quiz', array(
				'content' => $quiz_content
			), 'id = ' . intval($quiz_id));
		}
	}

	public function delete_question_quiz_by_id($quiz_id)
	{
		$this->delete('question_quiz', 'id = ' . intval($quiz_id));
	}

	public function get_question_quiz_info_by_id($quiz_id)
	{
		return $this->fetch_row('question_quiz', 'id = ' . intval($quiz_id));
	}
}
