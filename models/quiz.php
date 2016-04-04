<?php

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
		if (!$question_quiz_info = $this->get_question_quiz_info_by_id($quiz_id, true))
		{
			return false;
		}

		$quiz_type_id = $this->get_quiz_type_id($quiz_type);
		if($question_quiz_info['type'] != $quiz_type_id)
		{
			$this->update('question_quiz', array(
				'type' => intval($quiz_type_id)
			), 'id = ' . intval($quiz_id));
		}

		if($question_quiz_info['countdown'] != $countdown)
		{
			$this->update('question_quiz', array(
				'countdown' => intval($countdown)
			), 'id = ' . intval($quiz_id));
		}

		if($question_quiz_info['content'] != $quiz_content)
		{
			$this->update('question_quiz', array(
				'content' => $quiz_content
			), 'id = ' . intval($quiz_id));
		}
	}

	public function remove_question_quiz_by_id($quiz_id)
	{
		$this->delete('question_quiz', 'id = ' . intval($quiz_id));
	}

	public function get_question_quiz_info_by_id($quiz_id, $with_answer = false)
	{
		if(!($question_quiz_info = $this->fetch_row('question_quiz', 'id = ' . intval($quiz_id))))
		{
			return false;
		}

		if(!$with_answer)
		{
			// 删除问题答案的部分

			$quiz = json_decode($question_quiz_info['content'], true);
            if (!(json_last_error() === JSON_ERROR_NONE))
            {
                return false;
            }

            unset($quiz['answers']);
            $question_quiz_info['content'] = json_encode($quiz);
		}

		return $question_quiz_info;
	}

	public function get_question_quiz_record_by_user($question_id, $uid)
	{
		return $this->fetch_all('question_quiz_record', 'question_id = ' . intval($question_id) . ' AND uid = ' . intval($uid), ' start_time DESC');
	}

	public function get_question_quiz_record_by_question($question_id, $limit = 10)
	{
		return $this->fetch_all('question_quiz_record', 'question_id = ' . intval($question_id), ' start_time DESC', intval($limit));
	}

	public function get_question_quiz_record_list_page($question_id, $page = 1, $per_page = 10)
	{
		$records = $this->fetch_page('question_quiz_record', 'question_id = ' . intval($question_id), ' start_time DESC', $page, $per_page);
		$this->record_list_total = $this->found_rows();

		$record_list = array();
		foreach ($records as $key => $value) {
			$record_list[$key] = $value;
			$record_list[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($value['uid'], true);
		}

		return $record_list;
	}

	public function get_question_quiz_record_by_id($record_id)
	{
		if (! $record_id)
		{
			return false;
		}

		return $this->fetch_row('question_quiz_record', 'id = ' . intval($record_id));
	}

	public function get_question_quiz_count($column, $item_id, $type = 'total')
	{
		switch ($type) {
			case 'total':
				$where = '';

				break;
			case 'passed':
				$where = ' AND passed = 1';

				break;
			case 'timeout':
				$where = ' AND user_answer IS NULL';

				break;
			default:
				$where = '';

				break;
		}

		return $this->count('question_quiz_record', $column . " = " . intval($item_id) . $where);
	}

	public function get_question_quiz_count_POFT($key_column, $key_id, $var_column)
	{
		return count($this->query_all("SELECT count(" . $var_column . ") FROM " . $this->get_table('question_quiz_record') . " WHERE " . $key_column . " = " . intval($key_id) . " AND " . $var_column . " in (SELECT " . $var_column . " FROM " . $this->get_table("question_quiz_record") . " WHERE " . $key_column . " = " . intval($key_id) . " AND passed = 1) GROUP BY " . $var_column . " HAVING count(" . $var_column . ") = 1"));
	}

	public function get_question_quiz_count_total_by_question_id($question_id)
	{
		return $this->get_question_quiz_count('question_id', $question_id, 'total');
	}

	public function get_question_quiz_count_passed_by_question_id($question_id)
	{
		return $this->get_question_quiz_count('question_id', $question_id, 'passed');
	}

	public function get_question_quiz_count_timeout_by_question_id($question_id)
	{
		return $this->get_question_quiz_count('question_id', $question_id, 'timeout');
	}

	public function get_question_quiz_count_POFT_by_question_id($question_id)
	{
		return $this->get_question_quiz_count_POFT('question_id', $question_id, 'uid');
	}

	public function get_question_quiz_success_ratio_by_question_id($question_id)
	{
		$total = $this->get_question_quiz_count('question_id', $question_id, 'total');
		$passed = $this->get_question_quiz_count('question_id', $question_id, 'passed');

		return ($total == 0 ? 0 : $passed / $total);
	}

	public function get_question_quiz_count_info_by_question_id($question_id)
	{
		$count_info['total'] = $this->get_question_quiz_count('question_id', $question_id, 'total');
		$count_info['passed'] = $this->get_question_quiz_count('question_id', $question_id, 'passed');
		$count_info['timeout'] = $this->get_question_quiz_count('question_id', $question_id, 'timeout');
		$count_info['POFT'] = $this->get_question_quiz_count_POFT('question_id', $question_id, 'uid');
		$count_info['success_ratio'] = ($count_info['total'] > 0 ? $count_info['passed'] / $count_info['total'] : 0);
		$count_info['poft_ratio'] = ($count_info['total'] > 0 ? $count_info['POFT'] / $count_info['total'] : 0);

		return $count_info;
	}

	public function get_question_quiz_count_total_by_uid($uid)
	{
		return $this->get_question_quiz_count('uid', $uid, 'total');
	}

	public function get_question_quiz_count_passed_by_uid($uid)
	{
		return $this->get_question_quiz_count('uid', $uid, 'passed');
	}

	public function get_question_quiz_count_timeout_by_uid($uid)
	{
		return $this->get_question_quiz_count('uid', $uid, 'timeout');
	}

	public function get_question_quiz_count_POFT_by_uid($uid)
	{
		return $this->get_question_quiz_count_POFT('uid', $uid, 'question_id');
	}

	public function get_question_quiz_success_ratio_by_uid($uid)
	{
		$total = $this->get_question_quiz_count('uid', $uid, 'total');
		$passed = $this->get_question_quiz_count('uid', $uid, 'passed');

		return ($total == 0 ? 0 : $passed / $total);
	}

	public function get_question_quiz_count_info_by_uid($uid)
	{
		$count_info['total'] = $this->get_question_quiz_count('uid', $uid, 'total');
		$count_info['passed'] = $this->get_question_quiz_count('uid', $uid, 'passed');
		$count_info['timeout'] = $this->get_question_quiz_count('uid', $uid, 'timeout');
		$count_info['POFT'] = $this->get_question_quiz_count_POFT('uid', $uid, 'question_id');
		$count_info['success_ratio'] = ($count_info['total'] > 0 ? $count_info['passed'] / $count_info['total'] : 0);
		$count_info['poft_ratio'] = ($count_info['total'] > 0 ? $count_info['POFT'] / $count_info['total'] : 0);

		return $count_info;
	}

	public function save_question_quiz_record($question_id, $uid, $user_answer, $passed, $time_spend)
	{
		$now = time();
		$quiz_record = array(
			'question_id' => intval($question_id),
			'uid' => intval($uid),
			'start_time' => $now,
			'end_time' => $now,
			'user_answer' => $user_answer,
			'passed' => intval($passed),
			'time_spend' => intval($time_spend)
		);

		$record_id = $this->insert('question_quiz_record', $quiz_record);

		return $record_id;
	}

	public function update_question_quiz_record($record_id, $user_answer, $passed, $time_spend)
	{
		$now = time();
		
		$this->update('question_quiz_record', array(
			'end_time' => $now,
			'user_answer' => $user_answer,
			'passed' => intval($passed),
			'time_spend' => intval($time_spend)
		), 'id = ' . intval($record_id));
	}

	public function update_question_quiz_record_timeout($record_id)
	{
		$this->update('question_quiz_record', array(
			'end_time' => time()
		), 'id = ' . intval($record_id));
	}

	public function get_unfinished_question_quiz_record($user_id)
	{
		return $this->fetch_all('question_quiz_record', 'uid = ' . intval($user_id) . ' AND start_time = end_time AND user_answer IS NULL');
	}

	public function update_unfinished_question_quiz_record($user_id)
	{
		$this->update('question_quiz_record', array(
			'end_time' => time()
		), 'uid = ' . intval($user_id) . ' AND start_time = end_time AND user_answer IS NULL');
	}

	public function get_quiz_ids_by_type($quiz_type)
	{
		$quiz_ids = array();
		$quizes = $this->fetch_all('question_quiz', 'type = ' . intval($quiz_type), ' id ASC');
		foreach ($quizes as $key => $value) 
		{
			$quiz_ids[$value['id']] = $value['id'];	
		}

		return $quiz_ids;
	}

	public function get_quiz_ids_with_countdown($is_countdown)
	{
		$quiz_ids = array();
		if($is_countdown)
		{
			$quizes = $this->fetch_all('question_quiz', 'countdown > 0', ' id ASC');
		}
		else
		{
			$quizes = $this->fetch_all('question_quiz', 'countdown = 0', ' id ASC');
		}
		
		foreach ($quizes as $key => $value) {
			$quiz_ids[$value['id']] = $value['id'];
		}

		return $quiz_ids;

	}

	public function get_question_quiz_type($quiz_id)
	{
		$quiz = $this->fetch_row('question_quiz', 'id = ' . intval($quiz_id));

		return $quiz['type'];
	}

	public function user_question_quiz_count($question_id, $uid)
	{
		return ($this->count('question_quiz_record', 'question_id = ' . intval($question_id) . ' AND uid = ' . intval($uid)));
	}

	public function user_question_quiz_passed($question_id, $uid)
	{
		return ($this->count('question_quiz_record', 'question_id = ' . intval($question_id) . ' AND uid = ' . intval($uid) . ' AND passed = 1') > 0);
	}

	public function get_question_quiz_record_list($limit = 10)
	{
		return $this->fetch_all('question_quiz_record', 'user_answer IS NOT NULL', ' end_time DESC' , intval($limit));
	}

	public function get_user_answered_question_ids($uid)
	{
		$results = $this->query_all("SELECT DISTINCT question_id FROM " .  $this->get_table('question_quiz_record') . " WHERE uid = " . intval($uid));
		foreach ($results as $key => $value) {
			$question_ids[] = $value['question_id'];
		}

		return $question_ids;
	}

	public function get_user_answerd_question_count($uid)
	{
		return count($this->get_user_answered_question_ids($uid));
	}

	public function get_user_passed_question_ids($uid)
	{
		$results = $this->query_all("SELECT DISTINCT question_id FROM " .  $this->get_table('question_quiz_record') . " WHERE uid = " . intval($uid) . " AND passed = 1");
		foreach ($results as $key => $value) {
			$question_ids[] = $value['question_id'];
		}

		return $question_ids;
	}

	public function get_user_passed_question_count($uid)
	{
		return count($this->get_user_passed_question_ids($uid));
	}

	public function get_user_failed_question_ids($uid)
	{
		$passed_question_ids = $this->get_user_passed_question_ids($uid);
		if($passed_question_ids)
		{
			$where = ' WHERE uid = ' . intval($uid) . ' AND question_id NOT IN(' . implode(',', $passed_question_ids) . ')';
		}
		else 
		{
			$where = ' WHERE uid = ' . intval($uid);
		}

		$results = $this->query_all("SELECT DISTINCT question_id FROM " .  $this->get_table('question_quiz_record') . $where);
		foreach ($results as $key => $value) {
			$question_ids[] = $value['question_id'];
		}

		return $question_ids;
	}

	public function get_user_failed_question_count($uid)
	{
		return count($this->get_user_failed_question_ids($uid));
	}
}
