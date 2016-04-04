<?php

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public $per_page;

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		return $rule_action;
	}

	public function setup()
	{
		if (get_setting('index_per_page'))
		{
			$this->per_page = get_setting('index_per_page');
		}

		HTTP::no_cache_header();
	}

	public function list_action()
	{
		if ($drafts = $this->model('draft')->get_all('answer', $this->user_id, intval($_GET['page']) * $this->per_page .', '. $this->per_page))
		{
			foreach ($drafts AS $key => $val)
			{
				$drafts[$key]['question_info'] = $this->model("question")->get_question_info_by_id($val['item_id']);
			}
		}

		TPL::assign('drafts', $drafts);

		if (is_mobile())
		{
			TPL::output('m/ajax/draft');
		}
		else
		{
			TPL::output('draft/ajax/list');
		}
	}
}