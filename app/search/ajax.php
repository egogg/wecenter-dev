<?php

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['search_avail'])
		{
			$rule_action['rule_type'] = 'black'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		}

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function search_result_action()
	{
		if (!in_array($_GET['search_type'], array('questions', 'topics', 'users', 'articles')))
		{
			$_GET['search_type'] = null;
		}

		$search_result = $this->model('search')->search(cjk_substr($_GET['q'], 0, 64), $_GET['search_type'], $_GET['page'], get_setting('contents_per_page'), null, $_GET['is_recommend']);

		if ($search_result)
		{
			foreach ($search_result AS $key => $val)
			{
				switch ($val['type'])
				{
					case 'questions':
						$search_result_questions[] = $this->model('question')->get_question_info_by_id($val['search_id']);

						$search_result[$key]['focus'] = $this->model('question')->has_focus_question($val['search_id'], $this->user_id);

						break;

					case 'topics':
						$search_result_topics[] = $this->model('topic')->get_topic_by_id($val['search_id']);

						$search_result[$key]['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $val['search_id']);

						break;

					case 'users':
						$search_result_users[] = $this->model('account')->get_user_info_by_uid($val['search_id']);
						$search_result[$key]['focus'] = $this->model('follow')->user_follow_check($this->user_id, $val['search_id']);

						break;

					default:
						$search_result_articles[] = $this->model('article')->get_article_info_by_id($val['search_id']);
				}
			}
		}

		// 问题缩略图

		foreach ($search_result_questions as $key => $value) 
		{
			if ($value['has_attach'])
			{
				$value['attachs'] = $this->model('publish')->get_attach('question', $value['question_id'], 'min');
			}

			$search_result_questions[$key] = $value;
		}

		// 专题关注

		foreach ($search_result_topics as $key => $value) 
		{
			$search_result_topics[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $value['topic_id']);	
		}

		// 文章缩略图

		foreach ($search_result_articles as $key => $value) 
		{
			if ($value['has_attach'])
			{
				$value['attachs'] = $this->model('publish')->get_attach('article', $value['id'], 'min');
			}

			$search_result_articles[$key] = $value;
		}

		TPL::assign('search_result_questions', $search_result_questions);
		TPL::assign('search_result_users', $search_result_users);
		TPL::assign('search_result_topics', $search_result_topics);
		TPL::assign('search_result_articles', $search_result_articles);

		TPL::assign('search_result', $search_result);

		if (is_mobile())
		{
			TPL::output('m/ajax/search_result');
		}
		else
		{
			TPL::output('search/ajax/search_result');
		}
	}

	public function search_action()
	{
		$result = $this->model('search')->search(cjk_substr($_GET['q'], 0, 64), $_GET['type'], 1, $_GET['limit'], $_GET['topic_ids'], $_GET['is_recommend']);

		if (!$result)
		{
			$result = array();
		}

		if ($_GET['is_question_id'] AND is_digits($_GET['q']))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['q']);

			if ($question_info)
			{
				$result[] = $this->model('search')->prase_result_info($question_info);
			}
		}

		H::ajax_json_output($result);
	}
}