<?php

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function recommend_homepage_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		if ($this->model('recommend')->recommend_homepage_check($_POST['type'], $_POST['id']))
		{
			$action = 'remove';
			$this->model('recommend')->recommend_homepage_del($_POST['type'], $_POST['id']);
		}
		else
		{
			$action = 'add';
			$this->model('recommend')->recommend_homepage_add($_POST['type'], $_POST['id']);
		}

		// 消息通知

		// if($action == 'add')
		// {
		// 	// $this->model('notify')->send($this->user_id, $_POST['uid'], notify_class::TYPE_PEOPLE_FOCUS, notify_class::CATEGORY_PEOPLE, $this->user_id, array(
		// 	// 	'from_uid' => $this->user_id
		// 	// ));

		// 	// $this->model('email')->action_email('FOLLOW_ME', $_POST['uid'], get_js_url('/people/' . $this->user_info['url_token']), array(
		// 	// 	'user_name' => $this->user_info['user_name'],
		// 	// ));
		// }

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $action
		), 1, null));
	}
}