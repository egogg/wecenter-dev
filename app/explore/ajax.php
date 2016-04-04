<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		// if ($this->user_info['permission']['visit_explore'])
		// {
		// 	$rule_action['actions'][] = 'list';
		// }
		$rule_action['actions'] = array(
			'list',
			'user_quiz_message',
			'load_question_list'
		);

		return $rule_action;
	}

	public function list_action()
	{
		if ($_GET['feature_id'])
		{
			$topic_ids = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']);
		}
		else
		{
			$topic_ids = explode(',', $_GET['topic_id']);
		}

		if ($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}
		else
		{
			$per_page = get_setting('contents_per_page');
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts($_GET['post_type'], $_GET['category'], $topic_ids, $_GET['day'], $_GET['page'], $per_page);
		}
		else
		{
			$posts_list = $this->model('posts')->get_posts_list($_GET['post_type'], $_GET['page'], $per_page, $_GET['sort_type'], $topic_ids, $_GET['category'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		}

		if (!is_mobile() AND $posts_list)
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
				}
			}
		}

		TPL::assign('posts_list', $posts_list);

		if (is_mobile())
		{
			TPL::output('m/ajax/explore_list');
		}
		else
		{
			TPL::output('explore/ajax/list');
		}
	}

	public function load_question_list_action()
	{
		$recommend_question_list = $this->model('question')->get_homepage_recommend_question_list($_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $category_info['id'], $_GET['difficulty'], $_GET['quiztype'], $_GET['countdown'], $_GET['urecord'], $_GET['date'], $this->user_id);

		if ($recommend_question_list)
		{
			foreach ($recommend_question_list AS $key => $val)
			{	
				$this->model('question')->load_list_question_info($recommend_question_list[$key], $val, $this->user_id);
			}

			TPL::assign('question_list', $recommend_question_list);
			TPL::output('block/question_list.tpl.htm');
		}
	}

	public function user_quiz_message_action() {
		$quiz_records = $this->model('quiz')->get_question_quiz_record_list(10);

		foreach ($quiz_records as $key => $record) {
			$user_info = $this->model('account')->get_user_info_by_uid($record['uid']);
			$question_info = $this->model('question')->get_question_info_by_id($record['question_id']);
			$question_quiz_info = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id']);

			if($user_info and $question_info and $question_quiz_info)
			{
				$user_quiz_messages[$key]['user_name'] = $user_info['user_name'];
				$user_quiz_messages[$key]['user_url'] = $user_info['url_token'];
				$user_quiz_messages[$key]['uid'] = $user_info['uid'];

				$user_quiz_messages[$key]['question_id'] = $record['question_id'];
				$user_quiz_messages[$key]['question_title'] = $question_info['question_content'];
				$user_quiz_messages[$key]['is_countdown'] = ($question_quiz_info['countdown'] > 0);

				$user_quiz_messages[$key]['passed'] = $record['passed'];
				$user_quiz_messages[$key]['record_time'] = $record['end_time'];
			}
		}

		TPL::assign('user_quiz_messages', array_values($user_quiz_messages));
		TPL::output('block/user_quiz_message.tpl.htm');
	}
}