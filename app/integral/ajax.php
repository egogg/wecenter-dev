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
		if ($log = $this->model('integral')->fetch_all('integral_log', 'uid = ' . $this->user_id, 'time DESC', (intval($_GET['page']) * 10) . ', ' . $this->per_page))
		{
			foreach ($log AS $key => $val)
			{
				$parse_items[$val['id']] = array(
					'item_id' => $val['item_id'],
					'action' => $val['action']
				);
			}

			TPL::assign('log', $log);
			TPL::assign('log_detail', $this->model('integral')->parse_log_item($parse_items));
		}

		TPL::output('integral/ajax/list');
	}
}