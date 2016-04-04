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
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		$rule_action['actions'] = array(
			'sign_in'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function sign_in_action()
	{
		if($_GET['uid'] != $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('签到失败！')));
		}

		if($this->model('sign')->is_signed_today($_GET['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'is_signed' => true
			)), 1, null);
		}

		$continous = $this->model('sign')->sign_in($_GET['uid']);
		$integral_every_day = get_setting('sign_integral_every_day');
		$integral_seventh_day = get_setting('sign_integral_seventh_day');

		// 积分操作

		$is_seventh_day = false;
		if($continous < 0)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'is_signed' => true
			)), 1, null);
		}
		else if($continous == 6)
		{
			$integral = $integral_seventh_day;
			$integral_message = '连续7天签到积分';
			$is_seventh_day = true;
		} 
		else
		{
			$integral = $integral_every_day;
			$integral_message = '每日签到积分';
		}

		$this->model('integral')->process($_GET['uid'], 'SIGN_IN', $integral, $integral_message, $_GET['uid']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'is_signed' => false,
			'continous' => $continous,
			'integral_every_day' => $integral_every_day,
			'integral_seventh_day' => $integral_seventh_day,
			'user_integral' => ($this->user_info['integral'] + $integral)
		)), 1, null);
	}
}