<?php

if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['rule_type'] = 'black';
		}
		else
		{
			$rule_action['rule_type'] = 'white';
		}

		return $rule_action;
	}

	public function index_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

        if (is_digits($_GET['id']))
        {
            if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
            {
                $user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
            }
        }
        else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
        {

        }
        else
        {
            $user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
        }

        if (!$user)
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/');
        }

        if (urldecode($user['url_token']) != $_GET['id'])
        {
            HTTP::redirect('/people/' . $user['url_token']);
        }

        $this->model('people')->update_views($user['uid']);

		TPL::assign('user', $user);

		$job_info = $this->model('account')->get_jobs_by_id($user['job_id']);

		TPL::assign('job_name', $job_info['job_name']);

		if ($user['weibo_visit'])
		{
			if ($users_sina = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user['uid']))
			{
				TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
			}
		}

		TPL::assign('education_experience_list', $this->model('education')->get_education_experience_list($user['uid']));

		$jobs_list = $this->model('work')->get_jobs_list();

		if ($work_experience_list = $this->model('work')->get_work_experience_list($user['uid']))
		{
			foreach ($work_experience_list as $key => $val)
			{
				$work_experience_list[$key]['job_name'] = $jobs_list[$val['job_id']];
			}
		}

		TPL::assign('work_experience_list', $work_experience_list);

		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), 'people/' . $user['url_token']);

		TPL::import_css('css/user.css');

		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 12));
		TPL::assign('fans_list', $this->model('follow')->get_user_fans($user['uid'], 1, 8));
		TPL::assign('friends_list', $this->model('follow')->get_user_friends($user['uid'], 1, 8));
		TPL::assign('focus_topics', $this->model('topic')->get_focus_topic_list($user['uid'], 1, 8));

		TPL::assign('user_actions_questions', $this->model('actions')->get_user_actions($user['uid'], 5, ACTION_LOG::ADD_QUESTION, $this->user_id));
		TPL::assign('user_actions_answers', $this->model('actions')->get_user_actions($user['uid'], 5, ACTION_LOG::ANSWER_QUESTION, $this->user_id));
		TPL::assign('user_actions', $this->model('actions')->get_user_actions($user['uid'], 5, implode(',', array(
			ACTION_LOG::ADD_QUESTION,
			ACTION_LOG::ANSWER_QUESTION,
			ACTION_LOG::ADD_REQUESTION_FOCUS,
			ACTION_LOG::ADD_AGREE,
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::ADD_TOPIC_FOCUS,
			ACTION_LOG::ADD_ARTICLE
		)), $this->user_id));

		TPL::assign('user_question_list_publish', $this->model('question')->get_user_question_list_publish($user['uid'], 1, 5));
		
		TPL::assign('user_question_list_answered', $this->model('question')->get_user_question_list_answered($user['uid'], 1, 5));
		TPL::assign('user_answered_question_count', $this->model('quiz')->get_user_answerd_question_count($user['uid']));
		
		TPL::assign('user_question_list_failed', $this->model('question')->get_user_question_list_failed($user['uid'], 1, 5));
		TPL::assign('user_failed_question_count', $this->model('quiz')->get_user_failed_question_count($user['uid']));

		TPL::assign('user_answer_list', $this->model('answer')->get_user_answer_list($user['uid'], 1, 5));

		TPL::output('people/index');
	}

	public function index_square_action()
	{
		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}

		$this->crumb(AWS_APP::lang()->_t('用户列表'), '/people/');

		if ($_GET['topic_id'])
		{
			if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($this->model('topic')->get_child_topic_ids($_GET['topic_id']), get_setting('contents_per_page'), 4))
			{
				foreach ($helpful_users AS $key => $val)
				{
					$users_list[$key] = $val['user_info'];
					$users_list[$key]['experience'] = $val['experience'];


					foreach ($val['experience'] AS $exp_key => $exp_val)
					{
						$users_list[$key]['total_agree_count'] += $exp_val['agree_count'];
					}
				}
			}
		}
		else
		{
			$where = array();

			if ($_GET['group_id'])
			{
				$where[] = 'group_id = ' . intval($_GET['group_id']);
			}

			if($_GET['sort_type'])
			{
				switch ($_GET['sort_type']) {
					case 'passed':
						$sort_key = 'question_quiz_count_passed DESC';
					break;
					case 'poft':
						$sort_key = 'question_quiz_poft_ratio DESC';
					break;
					case 'question_count':
						$sort_key = 'question_count DESC';
					break;
					case 'quiz_count':
						$sort_key = 'question_quiz_count_total DESC';
					break;
					case 'integral':
						$sort_key = 'integral DESC';
					break;
					
					default:
						$sort_key = 'question_quiz_success_ratio DESC';
					break;
				}
			}
			else 
			{
				$sort_key = 'question_quiz_success_ratio DESC';
			}

			$where[] = 'forbidden = 0 AND group_id >=4 AND group_id < 99';
			$users_list = $this->model('account')->get_users_list(implode('', $where), calc_page_limit($_GET['page'], get_setting('user_rank_list_perpage')), true, false, $sort_key);

			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_js_url('/people/sort_type-' . $_GET['sort_type'] . '__group_id-' . $_GET['group_id']),
				'total_rows' => $this->model('account')->get_user_count(implode(' AND ', $where)),
				'per_page' => get_setting('user_rank_list_perpage')
			))->create_links());
		}

		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				if ($val['reputation'])
				{
					$reputation_users_ids[] = $val['uid'];
					$users_reputations[$val['uid']] = $val['reputation'];
				}

				$uids[] = $val['uid'];
			}

			if (!$_GET['topic_id'])
			{
				$reputation_topics = $this->model('people')->get_users_reputation_topic($reputation_users_ids, $users_reputations, 5);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['reputation_topics'] = $reputation_topics[$val['uid']];
				}
			}

			if ($uids AND $this->user_id)
			{
				$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);

				foreach ($users_list as $key => $val)
				{
					$users_list[$key]['focus'] = $users_follow_check[$val['uid']];
				}
			}

			TPL::assign('users_list', array_values($users_list));
		}

		if (!$_GET['group_id'])
		{
			TPL::assign('parent_topics', $this->model('topic')->get_parent_topics());
		}

		TPL::assign('custom_group', $this->model('account')->get_user_group_list(0, 1));

		if($_GET['sort_type'])
		{
			TPL::assign('sort_type', $_GET['sort_type']);
		}

		TPL::import_js('js/app/rank.js');
		TPL::output('people/square');
	}

	public function following_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

        if (is_digits($_GET['id']))
        {
            if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
            {
                $user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
            }
        }
        else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
        {

        }
        else
        {
            $user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
        }

        if (!$user)
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/');
        }

        // if (urldecode($user['url_token']) != $_GET['id'])
        // {
        //     HTTP::redirect('/people/' . $user['url_token']);
        // }

        // $this->model('people')->update_views($user['uid']);

		TPL::assign('user', $user);

		// $job_info = $this->model('account')->get_jobs_by_id($user['job_id']);

		// TPL::assign('job_name', $job_info['job_name']);

		if ($user['weibo_visit'])
		{
			if ($users_sina = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user['uid']))
			{
				TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
			}
		}

		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), 'people/' . $user['url_token']);

		TPL::import_css('css/user.css');

		TPL::assign('user_answered_question_count', $this->model('quiz')->get_user_answerd_question_count($user['uid']));
		TPL::assign('user_failed_question_count', $this->model('quiz')->get_user_failed_question_count($user['uid']));

		if($_GET['type'] == 'friends') 
		{
			$user_list = $this->model('follow')->get_user_friends($user['uid'], 1, get_setting('contents_per_page'));
			foreach ($user_list as $key => $val) 
			{
				$uids[] = $val['uid'];
			}
			if ($uids AND $this->user_id)
			{
				$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);

				foreach ($user_list as $key => $val)
				{
					$user_list[$key]['follow_check'] = $users_follow_check[$val['uid']];
				}
			}

			TPL::assign('friends_list', $user_list);

			TPL::assign('current_menu', 'following_friends');
		} 
		else if($_GET['type'] == 'fans')
		{
			$user_list = $this->model('follow')->get_user_fans($user['uid'], 1, get_setting('contents_per_page'));
			foreach ($user_list as $key => $val) 
			{
				$uids[] = $val['uid'];
			}
			if ($uids AND $this->user_id)
			{
				$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $uids);

				foreach ($user_list as $key => $val)
				{
					$user_list[$key]['follow_check'] = $users_follow_check[$val['uid']];
				}
			}
			TPL::assign('fans_list', $user_list);

			TPL::assign('current_menu', 'following_fans');
		}
		else
		{
			$topics = $this->model('topic')->get_focus_topic_list($user['uid'], 1, get_setting('contents_per_page'));
			foreach ($topics as $key => $value) 
			{
				$topics[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $value['topic_id']);	
			}
			TPL::assign('focus_topics', $topics);

			TPL::assign('current_menu', 'following_topics');
		}

		TPL::output('people/following');
	}

	public function questions_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

        if (is_digits($_GET['id']))
        {
            if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
            {
                $user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
            }
        }
        else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
        {

        }
        else
        {
            $user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
        }

        if (!$user)
        {
            H::redirect_msg(AWS_APP::lang()->_t('用户不存在'), '/');
        }

        // if (urldecode($user['url_token']) != $_GET['id'])
        // {
        //     HTTP::redirect('/people/' . $user['url_token']);
        // }

        // $this->model('people')->update_views($user['uid']);

		TPL::assign('user', $user);

		// $job_info = $this->model('account')->get_jobs_by_id($user['job_id']);

		// TPL::assign('job_name', $job_info['job_name']);

		if ($user['weibo_visit'])
		{
			if ($users_sina = $this->model('openid_weibo_oauth')->get_weibo_user_by_uid($user['uid']))
			{
				TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
			}
		}

		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));

		$this->crumb(AWS_APP::lang()->_t('%s 的个人主页', $user['user_name']), 'people/' . $user['url_token']);

		TPL::import_css('css/user.css');

		TPL::assign('user_answered_question_count', $this->model('quiz')->get_user_answerd_question_count($user['uid']));
		TPL::assign('user_failed_question_count', $this->model('quiz')->get_user_failed_question_count($user['uid']));

		if($_GET['type'] == 'answered') 
		{
			TPL::assign('user_question_list_answered', $this->model('question')->get_user_question_list_answered($user['uid'], 1, get_setting('contents_per_page')));

			TPL::assign('current_menu', 'questions_answered');
		} 
		else if($_GET['type'] == 'comments')
		{
			TPL::assign('user_answer_list', $this->model('answer')->get_user_answer_list($user['uid'], 1, get_setting('contents_per_page')));

			TPL::assign('current_menu', 'questions_comments');
		}
		else if($_GET['type'] == 'failed')
		{
			TPL::assign('user_question_list_failed', $this->model('question')->get_user_question_list_failed($user['uid'], 1, get_setting('contents_per_page')));

			TPL::assign('current_menu', 'questions_failed');
		}
		else if($_GET['type'] == 'publish')
		{
			TPL::assign('user_question_list_publish', $this->model('question')->get_user_question_list_publish($user['uid'], 1, get_setting('contents_per_page')));
			
			TPL::assign('current_menu', 'questions_publish');
		}
		else
		{
			TPL::assign('user_question_list_answered', $this->model('question')->get_user_question_list_answered($user['uid'], 1, get_setting('contents_per_page')));

			TPL::assign('current_menu', 'questions_answered');
		}

		TPL::output('people/questions');
	}
}