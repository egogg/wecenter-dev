<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class sign_class extends AWS_MODEL
{
	public function is_today($timestamp)
	{
		$date = date('d/m/Y', $timestamp);

	    return ($date == date('d/m/Y'));
	}

	public function is_yesterday($timestamp)
	{
		$date = date('d/m/Y', $timestamp);

	    return ($date == date('d/m/Y', time() - 86400));
	}

	public function is_signed_today($uid)
	{
		// 获取最新签到记录

		$last_sign = $this->fetch_row('sign_in', 'uid = ' . intval($uid), 'sign_time DESC');

		if(!$last_sign)
		{
			return false;
		}

		return $this->is_today($last_sign['sign_time']);
	}

	public function sign_in($uid)
	{
		$last_sign = $this->fetch_row('sign_in', 'uid = ' . intval($uid), 'sign_time DESC');

		if(!$last_sign)
		{
			// 没有签到记录

			$continous = 0;
			$continous_all = 0;
		}
		else 
		{
			if($this->is_today($last_sign['sign_time']))
			{
				return -1;
			}

			$continous = $last_sign['continous'];
			$continous_all = $last_sign['continous_all'];

			if($this->is_yesterday($last_sign['sign_time']))
			{
				$continous++;
				$continous_all++;
			}
			else
			{
				$continous = 0;
				$continous_all = 0;
			}
			
			$continous %= 7;
		}

		$this->insert('sign_in', array(
			'uid' => intval($uid),
			'sign_time' => time(),
			'continous' => intval($continous),
			'continous_all' => intval($continous_all)
		));

		return $continous;
	}
}