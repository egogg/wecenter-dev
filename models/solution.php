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

class solution_class extends AWS_MODEL
{
	public function get_solution_info_by_id($solution_id)
	{
		if (! $solution_id)
		{
			return false;
		}

		return $this->fetch_row('question_solution', 'id = ' . intval($solution_id));
	}

	public function remove_solution_by_id($solution_id)
	{
		$this->delete('question_solution', "id = " . intval($solution_id));
	}

	public function save_solution($solution_content)
	{
		$now = time();

		$solution_info = array(
			'content' => htmlspecialchars($solution_content),
			'add_time' => $now,
			'update_time' => $now,
			'has_attach' => 0
		);

		return $this->insert('question_solution', $solution_info);
	}

	public function update_solution($solution_id, $solution_content)
	{
		$solution_info['content'] = htmlspecialchars($solution_content);
		return $this->update('question_solution', $solution_info, 'id = ' . intval($solution_id));
	}

	public function get_question_solution_record($question_id, $user_id)
	{
		return $this->fetch_row('question_solution_record', 'question_id = ' . intval($question_id) . ' AND uid = ' . intval($user_id));
	}

	public function save_question_solution_record($question_id, $user_id)
	{
		$now = time();

		$solution_record = array (
			'question_id' => intval($question_id),
			'uid' => intval($user_id),
			'add_time' => $now
		);

		return $this->insert('question_solution_record', $solution_record);
	}
}
