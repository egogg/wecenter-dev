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

		$rule_action['actions'] = array(
			'load_answers',
			'get_question_comments',
			'get_answer_comments',
			'log',
			'get_focus_users',
			'get_answer_users',
			'remove_answer',
			'fetch_share_data',
			'init_question_content',
			'question_quiz_submit_answer',
			'begin_question_quiz_countdown',
			'get_question_solution',
			'get_question_solution_record',
			'save_question_solution_record',
			'get_question_quiz_retry_integral',
			'save_question_quiz_retry_integral',
			'get_question_view_solution_integral',
			'save_question_view_solution_integral',
			'question_quiz_timeout',
			'load_more_question_quiz_record',
			'load_more_question_quiz_record_user',
			'invited_users',
			'user_invited_users'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function fetch_answer_data_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_GET['id']);

		if ($answer_info['uid'] == $this->user_id OR $this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'])
		{
			echo json_encode($answer_info);
		}
	}

	public function uninterested_action()
	{
		if (!$_POST['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		$this->model('question')->add_question_uninterested($this->user_id, $_POST['question_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function get_focus_users_action()
	{
		if ($focus_users_info = $this->model('question')->get_focus_users_by_question($_GET['question_id'], 18))
		{
			TPL::assign('users', $focus_users_info);
			TPL::output('block/user_list_square');
		}
	}

	public function save_invite_action()
	{
		$invited_user_count = $this->model('question')->get_invited_user_count($_GET['question_id'], $this->user_id);
		$invitation_limit = get_setting('user_question_invite_limit');
		if($invited_user_count >= $invitation_limit)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你已经邀请了' . $invited_user_count . '个用户，不能邀请更多用户了')));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (!$invite_user_info = $this->model('account')->get_user_info_by_uid($_GET['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($invite_user_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
		}

		// if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		// {
		// 	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		// }

		// if ($this->model('answer')->has_answer_by_uid($_POST['question_id'], $invite_user_info['uid']))
		// {
		// 	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已经回答过该问题')));
		// }

		if ($question_info['published_uid'] == $invite_user_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请问题的发起者回答问题')));
		}

		if ($this->model('question')->has_question_invite($_GET['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已接受过邀请')));
		}

		if ($this->model('question')->has_question_invite($_GET['question_id'], $invite_user_info['uid'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
		}

		$this->model('question')->add_invite($_GET['question_id'], $this->user_id, $invite_user_info['uid']);

		$this->model('account')->update_question_invite_count($invite_user_info['uid']);

		if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($invite_user_info['uid']) AND $invite_user_info['weixin_settings']['QUESTION_INVITE'] != 'N')
		{
			$this->model('weixin')->send_text_message($weixin_user['openid'], "有用户邀请你回答问题 [" . $question_info['question_content'] . "]", $this->model('openid_weixin_weixin')->redirect_url('/question/' . $question_info['question_id']));
		}

		$notification_id = $this->model('notify')->send($this->user_id, $invite_user_info['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval($_GET['question_id']), array(
			'from_uid' => $this->user_id,
			'question_id' => intval($_GET['question_id'])
		));

		$this->model('email')->action_email('QUESTION_INVITE', $_GET['uid'], get_js_url('/question/' . $question_info['question_id'] . '?notification_id-' . $notification_id), array(
			'user_name' => $this->user_info['user_name'],
			'question_title' => $question_info['question_content'],
		));

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function agree_answer_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if ($this->model('answer')->agree_answer($this->user_id, $_POST['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'agree'
			)), 1, null);
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'disagree'
			)), 1, null);
		}
	}

	public function fetch_share_data_action()
	{
		switch ($_GET['type'])
		{
			case 'question':
				$question_info = $this->model('question')->get_question_info_by_id($_GET['item_id']);

				$question_info['question_content'] = trim(cjk_substr($question_info['question_content'], 0, 100, 'UTF-8', '...'), "\r\n\t");

				$url = get_js_url('/question/' . $question_info['question_id'] . '?fromuid=' . $this->user_id);

				$message = AWS_APP::lang()->_t('我看到一个不错的问题, 想和你分享:') . ' ' . $question_info['question_content'] . ' ' . $url;
			break;

			case 'answer':
				$answer_info = $this->model('answer')->get_answer_by_id($_GET['item_id']);

				$user_info = $this->model('account')->get_user_info_by_uid($answer_info['uid']);

				$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);

				$answer_info['answer_content'] = trim(cjk_substr($answer_info['answer_content'], 0, 100, 'UTF-8', '...'), "\r\n\t");

				$answer_info['answer_content'] = str_replace(array(
					"\r",
					"\n",
					"\t"
				), ' ', $answer_info['answer_content']);

				$url = get_js_url('/question/' . $answer_info['question_id'] . '?fromuid=' . $this->user_id . '&answer_id=' . $answer_info['answer_id'] . '&single=true');

				if ($answer_info['anonymous'])
				{
					$user_info['user_name'] = AWS_APP::lang()->_t('匿名用户');
				}

				$message = AWS_APP::lang()->_t('我看到一个不错的问题, 想和你分享:') . ' ' . $question_info['question_content'] . ' - ' . $user_info['user_name'] . ": " . $answer_info['answer_content'] . ' ' . $url;
			break;

			case 'article':
				$article_info = $this->model('article')->get_article_info_by_id($_GET['item_id']);

				$article_info['message'] = trim(cjk_substr($article_info['message'], 0, 100, 'UTF-8', '...'), "\r\n\t");

				$article_info['message'] = str_replace(array(
					"\r",
					"\n",
					"\t"
				), ' ', $article_info['message']);

				$url = get_js_url('/article/' . $article_info['id'] . '?fromuid=' . $this->user_id);

				$message = AWS_APP::lang()->_t('我看到一个不错的文章, 想和你分享:') . ' ' . $article_info['title'] . ": " . $article_info['message'] . ' ' . $url;
			break;
		}

		$data = array(
			'message' => $message,
			'url' => $url,
			'sina_akey' => get_setting('sina_akey') ? get_setting('sina_akey') : '3643094708',
			'qq_app_key' => get_setting('qq_app_key') ? get_setting('qq_app_key') : '801158211',
		);

		H::ajax_json_output(AWS_APP::RSM(array(
			'share_txt' => $data
		), 1, null));
	}

	public function save_answer_comment_action()
	{
		if (! $_GET['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回复不存在')));
		}

		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有发表评论的权限')));
		}

		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入评论内容')));
		}

		if (get_setting('comment_limit') > 0 AND cjk_strlen($_POST['message']) > get_setting('comment_limit'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字节', get_setting('comment_limit'))));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($_POST['message']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		$this->model('answer')->insert_answer_comment($_GET['answer_id'], $this->user_id, $_POST['message']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['answer_id']),
			'type_name' => 'answer'
		), 1, null));
	}

	public function get_answer_comments_action()
	{
		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);

		TPL::assign('question', $this->model('question')->get_question_info_by_id($answer_info['question_id']));

		TPL::assign('answer_comments', $comments);
		TPL::output("question/ajax/comments");
	}

	public function save_question_comment_action()
	{
		if (! $_GET['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有发表评论的权限')));
		}

		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入评论内容')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		if (get_setting('comment_limit') > 0 AND (cjk_strlen($_POST['message']) > get_setting('comment_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字节', get_setting('comment_limit'))));
		}

		$this->model('question')->insert_question_comment($_GET['question_id'], $this->user_id, $_POST['message']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['question_id']),
			'type_name' => 'question'
		), 1, null));
	}

	public function get_question_comments_action()
	{
		$comments = $this->model('question')->get_question_comments($_GET['question_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}

		TPL::assign('question', $this->model('question')->get_question_info_by_id($_GET['question_id']));

		TPL::assign('comments', $comments);

		TPL::output("question/ajax/comments");
	}

	public function answer_vote_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if ($answer_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的回复进行投票')));
		}

		if (! in_array($_POST['value'], array(
			- 1,
			1
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投票数据错误, 无法进行投票')));
		}

		$reputation_factor = $this->model('account')->get_user_group_by_id($this->user_info['reputation_group'], 'reputation_factor');

		$this->model('answer')->change_answer_vote($_POST['answer_id'], $_POST['value'], $this->user_id, $reputation_factor);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function cancel_question_invite_action()
	{
		$this->model('question')->cancel_question_invite($_GET['question_id'], $this->user_id, $_GET['recipients_uid']);

		$this->model('account')->update_question_invite_count($_GET['recipients_uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function question_invite_delete_action()
	{
		$question_invite_id = intval($_POST['question_invite_id']);

		$this->model('question')->delete_question_invite($question_invite_id, $this->user_id);

		$this->model('account')->update_question_invite_count($this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function question_thanks_action()
	{
		if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能感谢自己的问题')));
		}

		if ($this->model('question')->question_thanks($_POST['question_id'], $this->user_id, $this->user_info['user_name']))
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_QUESTION_THANK, notify_class::CATEGORY_QUESTION, $_POST['question_id'], array(
				'question_id' => intval($_POST['question_id']),
				'from_uid' => $this->user_id
			));

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));
		}
	}

	public function question_answer_rate_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if ($this->user_id == $answer_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('不能评价自己发表的回复')));
		}

		if ($_POST['type'] == 'thanks' AND $this->model('answer')->user_rated('thanks', $_POST['answer_id'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('已感谢过该回复, 请不要重复感谢')));
		}

		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y' and $_POST['type'] == 'thanks')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		}

		if ($this->model('answer')->user_rate($_POST['type'], $_POST['answer_id'], $this->user_id, $this->user_info['user_name']))
		{
			if ($answer_info['uid'] != $this->user_id)
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_ANSWER_THANK, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'question_id' => $answer_info['question_id'],
					'from_uid' => $this->user_id,
					'item_id' => $answer_info['answer_id']
				));
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));
		}
	}

	public function focus_action()
	{
		if (!$_POST['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if (! $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('question')->add_focus_question($_POST['question_id'], $this->user_id)
		), 1, null));
	}

	public function save_answer_action()
	{
		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的问题不能回复')));
		}

		$answer_content = trim($_POST['answer_content'], "\r\n\t");

		if (! $answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		// 判断是否是问题发起者
		if (get_setting('answer_self_question') == 'N' and $question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能回复自己发布的问题，你可以修改问题内容')));
		}

		// 判断是否已回复过问题
		if ((get_setting('answer_unique') == 'Y') AND $this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个问题只能回复一次，你可以编辑回复过的回复')));
		}

		if (strlen($answer_content) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字节', get_setting('answer_length_lower'))));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($answer_content))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		if (!$this->model('publish')->insert_attach_is_self_upload($answer_content, $_POST['attach_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
		}

		if (human_valid('answer_valid_hour') and ! AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		$this->model('draft')->delete_draft($question_info['question_id'], 'answer', $this->user_id);

		if ($this->publish_approval_valid() OR H::sensitive_word_exists($answer_content))
		{
			$this->model('publish')->publish_approval('answer', array(
				'question_id' => $question_info['question_id'],
				'answer_content' => $answer_content,
				'anonymous' => $_POST['anonymous'],
				'attach_access_key' => $_POST['attach_access_key'],
				'auto_focus' => $_POST['auto_focus']
			), $this->user_id, $_POST['attach_access_key']);

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/wait_approval/question_id-' . $question_info['question_id'])
			), 1, null));
		}
		else
		{
			$answer_id = $this->model('publish')->publish_answer($question_info['question_id'], $answer_content, $this->user_id, $_POST['anonymous'], $_POST['attach_access_key'], $_POST['auto_focus']);

			$answer_info = $this->model('answer')->get_answer_by_id($answer_id);


			if ($answer_info['has_attach'])
			{
				$answer_info['attachs'] = $this->model('publish')->get_attach('answer', $answer_id, 'min');

				$answer_info['insert_attach_ids'] = FORMAT::parse_attachs($answer_info['answer_content'], true);
			}

			$answer_info['user_info'] = $this->user_info;
			$answer_info['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($answer_info['answer_content']))));

			TPL::assign('answer_info', $answer_info);

			H::ajax_json_output(AWS_APP::RSM(array(
				'ajax_html' => TPL::output('question/ajax/answer', false)
			), 1, null));
		}
	}

	public function update_answer_action()
	{
		if (! $answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('答案不存在')));
		}

		if ($_POST['do_delete'])
		{
			if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
			}

			$this->model('answer')->remove_answer_by_id($_GET['answer_id']);

			// 通知回复的作者
			if ($this->user_id != $answer_info['uid'])
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_REMOVE_ANSWER, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'from_uid' => $this->user_id,
					'question_id' => $answer_info['question_id']
				));
			}

			$this->model('question')->save_last_answer($answer_info['question_id']);

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}

		$answer_content = trim($_POST['answer_content'], "\r\n\t");

		if (!$answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		if (strlen($answer_content) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字节', get_setting('answer_length_lower'))));
		}

		if (! $this->user_info['permission']['publish_url'] AND FORMAT::outside_url_exists($answer_content))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你所在的用户组不允许发布站外链接')));
		}

		if (!$this->model('publish')->insert_attach_is_self_upload($answer_content, $_POST['attach_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
		}

		if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个回复')));
		}

		if ($answer_info['uid'] == $this->user_id and (time() - $answer_info['add_time'] > get_setting('answer_edit_time') * 60) and get_setting('answer_edit_time') and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经超过允许编辑的时限')));
		}

		$this->model('answer')->update_answer($_GET['answer_id'], $answer_info['question_id'], $answer_content, $_POST['attach_access_key']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'target_id' => $_GET['target_id'],
			'display_id' => $_GET['display_id']
		), 1, null));
	}

	public function remove_answer_action()
	{
		if (! $answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('答案不存在')));
		}

		if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		$this->model('answer')->remove_answer_by_id($_GET['answer_id']);

		// 通知回复的作者
		if ($this->user_id != $answer_info['uid'])
		{
			$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_REMOVE_ANSWER, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
				'from_uid' => $this->user_id,
				'question_id' => $answer_info['question_id']
			));
		}

		$this->model('question')->save_last_answer($answer_info['question_id']);
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function log_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定问题不存在')));
		}

		$log_list = ACTION_LOG::get_action_by_event_id($_GET['id'], (intval($_GET['page']) * get_setting('contents_per_page')) . ', ' . get_setting('contents_per_page'), ACTION_LOG::CATEGORY_QUESTION, implode(',', array(
			ACTION_LOG::ADD_QUESTION,
			ACTION_LOG::MOD_QUESTON_TITLE,
			ACTION_LOG::MOD_QUESTION_DESCRI,
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::DELETE_TOPIC,
			ACTION_LOG::REDIRECT_QUESTION,
			ACTION_LOG::MOD_QUESTION_CATEGORY,
			ACTION_LOG::MOD_QUESTION_ATTACH,
			ACTION_LOG::DEL_REDIRECT_QUESTION
		)));

		//处理日志记录
		$log_list = $this->model('question')->analysis_log($log_list, $question_info['published_uid'], $question_info['anonymous']);

		if (! $unverified_modify_all = $question_info['unverified_modify'])
		{
			$unverified_modify_all = array();
		}

		$unverified_modify = array();

		foreach ($unverified_modify_all as $key => $val)
		{
			$unverified_modify = array_merge($unverified_modify, $val);
		}

		TPL::assign('unverified_modify', $unverified_modify);
		TPL::assign('question_info', $question_info);

		TPL::assign('list', $log_list);

		TPL::output('question/ajax/log');
	}

	public function redirect_action()
	{
		$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的问题不能设置重定向')));
		}

		if (!$this->user_info['permission']['redirect_question'] AND ! ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if ((!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator']) AND $this->user_info['permission']['function_interval'] AND ((time() - AWS_APP::cache()->get('function_interval_timer_redirect_' . $this->user_id)) < $this->user_info['permission']['function_interval']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('灌水预防机制已经打开, 在 %s 秒内不能操作', $this->user_info['permission']['function_interval'])));
		}

		$this->model('question')->redirect($this->user_id, $_POST['item_id'], $_POST['target_id']);

		if ($_POST['target_id'] AND $_POST['item_id'] AND $question_info['published_uid'] != $this->user_id)
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_REDIRECT_QUESTION, notify_class::CATEGORY_QUESTION, $_POST['item_id'], array(
				'from_uid' => $this->user_id,
				'question_id' => intval($_POST['item_id'])
			));
		}

		AWS_APP::cache()->set('function_interval_timer_redirect_' . $this->user_id, time(), 86400);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function email_invite_action()
	{
		if (! H::valid_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请填写正确的email地址')));
		}

		if ($_POST['email'] == $this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你不能邀请自己')));
		}

		if ($this->model('question')->check_email_invite($_GET['question_id'], $this->user_id, $_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你已经邀请过该朋友')));
		}

		$this->model('question')->add_invite($_GET['question_id'], $this->user_id, 0, $_POST['email']);

		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

		$this->model('email')->action_email('INVITE_QUESTION', $_POST['email'], get_js_url('/question/' . $_GET['question_id'] . '?fromuid=' . $this->user_id), array(
			'user_name' => $this->user_info['user_name'],
			'question_title' => $question_info['question_content']
		));

		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('邀请成功')));
	}

	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			if ($this->user_id != $question_info['published_uid'])
			{
				$this->model('account')->send_delete_message($question_info['published_uid'], $question_info['question_content'], $question_info['question_detail']);
			}

			$this->model('question')->remove_question($question_info['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}

	public function set_recommend_action()
	{
		if (!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		switch ($_POST['action'])
		{
			case 'set':
				$this->model('question')->set_recommend($_POST['question_id']);
			break;

			case 'unset':
				$this->model('question')->unset_recommend($_POST['question_id']);
			break;
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_comment_action()
	{
		if (! in_array($_GET['type'], array(
			'answer',
			'question'
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}

		if (! $_GET['comment_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		$comment = $this->model($_GET['type'])->get_comment_by_id($_GET['comment_id']);

		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administortar'] AND $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限删除该评论')));
		}

		$this->model($_GET['type'])->remove_comment($_GET['comment_id']);

		if ($_GET['type'] == 'answer')
		{
			$this->model('answer')->update_answer_comments_count($comment['answer_id']);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->update_question_comments_count($comment['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function answer_force_fold_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if (! $answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复不存在')));
		}

		if (! $answer_info['force_fold'])
		{
			$this->model('answer')->update_answer_by_id($_POST['answer_id'], array(
				'force_fold' => 1
			));

			if (! $this->model('integral')->fetch_log($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id']))
			{
				ACTION_LOG::set_fold_action_history($answer_info['answer_id'], 1);

				$this->model('integral')->process($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id'], get_setting('integral_system_config_answer_fold'), AWS_APP::lang()->_t('回复折叠') . ' #' . $answer_info['answer_id']);
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'fold'
			), 1, AWS_APP::lang()->_t('强制折叠回复')));
		}
		else
		{
			$this->model('answer')->update_answer_by_id($_POST['answer_id'], array(
				'force_fold' => 0
			));

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'unfold'
			), 1, AWS_APP::lang()->_t('撤销折叠回复')));
		}
	}

	public function lock_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		$this->model('question')->lock_question($_POST['question_id'], !$question_info['lock']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function get_report_reason_action()
	{
		if ($report_reason = explode("\n", get_setting('report_reason')))
		{
			$data = array();

			foreach ($report_reason as $key => $val)
			{
				$val = trim($val);

				if ($val)
				{
					$data[] = $val;
				}
			}
		}

		H::ajax_json_output(AWS_APP::RSM($data, 1));
	}

	public function save_report_action()
	{
		if (trim($_POST['reason']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请填写举报理由')));
		}

		$this->model('question')->save_report($this->user_id, $_POST['type'], $_POST['target_id'], htmlspecialchars($_POST['reason']), $_SERVER['HTTP_REFERER']);

		$recipient_uid = get_setting('report_message_uid') ? get_setting('report_message_uid') : 1;

		$this->model('message')->send_message($this->user_id, $recipient_uid, AWS_APP::lang()->_t('有新的举报, 请登录后台查看处理: %s', get_js_url('/admin/question/report_list/')));

		H::ajax_json_output(AWS_APP::RSM(null, 1, AWS_APP::lang()->_t('举报成功')));
	}

	public function set_best_answer_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回答不存在')));
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['best_answer'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题已经存在最佳回复')));
		}

		$this->model('answer')->set_best_answer($_POST['answer_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	// 答案解析

	public function get_question_solution_data_action()
	{
		$solution_info['id'] = 0;
		$solution_info['content'] = '';

		$question_info = $this->model('question')->get_question_info_by_id($_GET['id']);
		if($question_info && $question_info['solution_id'])
		{
			if ($question_info['published_uid'] == $this->user_id OR $this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'])
			{
				$solution_info = $this->model('solution')->get_solution_info_by_id($question_info['solution_id']);	
				if($solution_info)
				{
					echo json_encode($solution_info);
					return;
				}
			}
		}

		echo json_encode($solution_info);
	}

	public function save_question_solution_action()
	{
		if(!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if($question_info['solution_id'] != $_GET['solution_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题和答案信息不匹配')));
		}

		$solution_info = $this->model('solution')->get_solution_info_by_id($_GET['solution_id']);

		// 删除答案

		if($_POST['do_delete'])
		{
			if($solution_info)
			{
				if ($solution_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行删除操作')));
				}

				$this->model('solution')->remove_question_solution_by_id($_GET['solution_id']);
				
				// 通知答案的作者

				if ($this->user_id != $question_info['published_uid'])
				{
					$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_QUESTION_SOLUTION_MODIFIED, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
						'from_uid' => $this->user_id,
						'question_id' => $question_info['question_id']
					));
				}

				$this->model('question')->update_solution_id($question_info['question_id'], 0);
			}
			
			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}

		$solution_content = trim($_POST['solution_content'], "\r\n\t");

		if (!$solution_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('<span class="hidden-xs">答案解析不能为空，</span>请输入详细答案解析')));
		}

		if (!$this->model('publish')->insert_attach_is_self_upload($solution_content, $_POST['attach_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只允许插入当前页面上传的附件')));
		}

		if ($question_info['published_uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个答案解析')));
		}

		if(!$solution_info)
		{
			// 保存为新的答案解析

			$solution_id = $this->model('solution')->save_solution($solution_content);
			$this->model('question')->update_solution_id($question_info['question_id'], $solution_id);
		}
		else
		{
			// 更新已有的答案解析

			$solution_id = $solution_info['id'];
			$this->model('solution')->update_solution($solution_id, $solution_content);
		}

		$attach_access_key = $_POST['attach_access_key'];
		if ($attach_access_key)
		{
			$this->model('publish')->update_attach('solution', $solution_id, $attach_access_key);
		}

		// 通知答案的作者

		if ($this->user_id != $question_info['published_uid'])
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_QUESTION_SOLUTION_MODIFIED, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
				'from_uid' => $this->user_id,
				'question_id' => $question_info['question_id']
			));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'target_id' => $_GET['target_id'],
			'display_id' => $_GET['display_id']
		), 1, null));
	}

	// 检查问题答案

	public function question_quiz_submit_answer_action()
	{
		// 获取答案信息

		if(!$_GET['question_id'])
		{
			H::ajax_json_output(array(
				'internal_error' => true 
			));
		}

		if(!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			H::ajax_json_output(array(
				'internal_error' => true 
			));
		}

		if(!$question_quiz_info = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id'], true)) 
		{
			H::ajax_json_output(array(
				'internal_error' => true 
			));
		}

		$quiz = json_decode($question_quiz_info['content'], true);
        if (!(json_last_error() === JSON_ERROR_NONE))
        {
            H::ajax_json_output(array(
				'internal_error' => true 
			));
        }

		// 检查答案正确性
		
		$is_correct_answer = true;
        
        if($_GET['answer'])
        {
        	$user_answer = $_GET['answer'];
        }
        else
        {
        	$is_correct_answer = false;
        }

        if($_GET['spend_time'])
        {
        	$spend_time = $_GET['spend_time'];
        }
		
		switch($quiz['type'])
		{
			case 'singleSelection':
				if(!is_numeric($user_answer))
				{
					$is_correct_answer = false;
				}
				else
				{	
					$answer_index = intval($user_answer) - 1;
					if($answer_index < 0 || $answer_index >= count($quiz['answers'])) 
					{
						$is_correct_answer = false;
					}
					else
					{
						$is_correct_answer = $quiz['answers'][$answer_index]['answer'];
					}
				}

				break;
			case 'multipleSelection':
				$correct_answer = array();
				foreach ($quiz['answers'] as $i => $answer)
				{
					if($answer['answer'])
					{
						$correct_answer[] = ($i + 1);
					}
				}
				$correct_answer = implode(',', $correct_answer);
				$is_correct_answer = ($correct_answer == $user_answer);

				break;
			case 'crossword':
				$is_correct_answer = ($user_answer == $quiz['answers'][0]['answer']);
				
				break;
			case 'textInput':
				$correct_answer = array();
				foreach ($quiz['answers'] as $i => $value) 
				{
					$correct_answer[] = $value['answer'];
				}
				$correct_answer = implode(',', $correct_answer);
				$is_correct_answer = ($correct_answer == $user_answer);

				break;
			default :
				$is_correct_answer = false;
		}

		// 检查是否为特殊用户

		$is_special_user = ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid']);

		// 检查是否为限时答题

		$is_countdown = ($quiz['countdown'] > 0);

		// 保存答题记录

		if(!$is_special_user)
		{
			if($_GET['record_id'])
			{
				// 更新已有答题记录状态

				$this->model('quiz')->update_question_quiz_record($_GET['record_id'], $user_answer, $is_correct_answer, $spend_time);
			}
			else
			{
				// 保存新的答题记录

				$this->model('quiz')->save_question_quiz_record($_GET['question_id'], $this->user_id, $user_answer, $is_correct_answer, $spend_time);
			}

			// 更新用户答题统计

			$this->model('account')->update_user_quiz_count_info($this->user_id);

			// 更新问题答题统计

			$this->model('question')->update_question_quiz_count_info($question_info['question_id']);

			// 积分操作

			$required_integral = $this->question_quiz_answer_integral($_GET['question_id'], $is_correct_answer);

			if($is_correct_answer)
			{
				// 对于回答过的问题不加积分

				if(!$this->model('quiz')->user_question_quiz_passed($question_info['question_id'], $this->user_id))
				{
					$this->model('integral')->process($this->user_id, 'QUESTION_QUIZ_CORRECT', $required_integral, AWS_APP::lang()->_t('答题正确 #') . $_GET['question_id']);
				} 
				else 
				{
					$required_integral = 0;
				}
			}
			else
			{
				$this->model('integral')->process($this->user_id, 'QUESTION_QUIZ_INCORRECT', -$required_integral, AWS_APP::lang()->_t('答题错误 #') . $_GET['question_id']);
			}
		}

        H::ajax_json_output(array(
        	'is_countdown' => $is_countdown,
        	'user_answer' => $user_answer,
        	'spend_time' => $spend_time,
        	'correct' => $is_correct_answer,
        	'user_integral' => $this->user_info['integral'],
        	'integral' => $required_integral
        ));
	}

	public function question_quiz_timeout_action () 
	{
		// 是否为特殊用户

		if($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid'])
		{
			// 对于特殊用户，直接返回

			H::ajax_json_output(array(
				'is_special_user' => true
			));
		}

		if($_GET['record_id'])
		{
			// 更新答题状态

			$this->model('quiz')->update_question_quiz_record_timeout($_GET['record_id']);

			// 更新用户答题统计

			$this->model('account')->update_user_quiz_count_info($this->user_id);

			// 更新问题答题统计

			$this->model('question')->update_question_quiz_count_info($question_info['question_id']);

			// 积分操作

			$required_integral = $this->question_base_integral($_GET['question_id']) * abs(get_setting('question_quiz_timeout_integral_coeffcient'));
			if($required_integral > 0)
			{
				$this->model('integral')->process($this->user_id, 'QUESTION_QUIZ_TIMEOUT', -$required_integral, AWS_APP::lang()->_t('答题超时 #') . $_GET['question_id']);
			}

			H::ajax_json_output(array(
				'required_integral' => $required_integral,
				'user_integral' => $this->user_info['integral']
			));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function complete_unfinished_question_quiz_record()
	{
		if($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid'])
		{
			// 对于特殊用户，直接返回

			return;
		}

		// 获取该用户的所有未完成的答题记录

		$unfinished_quiz_record = $this->model('quiz')->get_unfinished_question_quiz_record($this->user_id);
		if($unfinished_quiz_record)
		{
			// 对所有记录按照答题超时进行积分操作

			foreach ($unfinished_quiz_record AS $key => $quiz_record)
			{
				$required_integral = $this->question_base_integral($quiz_record['question_id']) * abs(get_setting('question_quiz_timeout_integral_coeffcient'));
				if($required_integral > 0)
				{
					$this->model('integral')->process($this->user_id, 'QUESTION_QUIZ_INVALID', -$required_integral, AWS_APP::lang()->_t('答题无效或超时 #') . $quiz_record['question_id']);
				}
			}

			// 更新未完成记录

			$this->model('quiz')->update_unfinished_question_quiz_record($this->user_id);

			// 更新用户答题统计

			$this->model('account')->update_user_quiz_count_info($this->user_id);

			// 更新问题答题统计

			$this->model('question')->update_question_quiz_count_info($question_info['question_id']);
		}
	}

	public function init_question_content_action ()
	{
		if(!($question_info = $this->model('question')->load_detailed_question_info($_GET['id'])))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		TPL::assign('question_info', $question_info);

		// 清算该用户所有未纪录的超时答题

		$this->complete_unfinished_question_quiz_record();

		// 用户答题记录

		TPL::assign('quiz_record', $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $this->user_id, 1, 5));
		$try_count = $this->model('quiz')->get_question_quiz_record_user_count();
		TPL::assign('quiz_record_count', $try_count);
		$passed_quiz = $this->model('quiz')->user_question_quiz_passed($question_info['question_id'], $this->user_id);
		TPL::assign('passed_quiz', $passed_quiz);

		// 是否显示答题选项

		$has_quiz_options = ($question_info['quiz_id'] > 0);
		$is_countdown = ($question_info['question_quiz']['countdown'] > 0);

		// 是否为首页推荐

		TPL::assign('is_recommend_homepage', $this->model('recommend')->recommend_homepage_check('question', $question_info['question_id']));
		TPL::assign('is_top_question', $this->model('recommend')->recommend_homepage_check('top_question', $question_info['question_id']));

		$show_question_content = (!$has_quiz_options OR ($has_quiz_options AND !$is_countdown) OR ($is_countdown AND $passed_quiz));
		$show_question_quiz = (($has_quiz_options AND !$is_countdown) OR ($is_countdown AND $passed_quiz));
		$answer_question_mode = ($has_quiz_options AND !$is_countdown AND !$try_count);

		TPL::assign('show_question_content', $show_question_content);
		TPL::assign('show_question_quiz', $show_question_quiz);
		TPL::assign('answer_question_mode', $answer_question_mode);

		$user_answered = $this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id);
		TPL::assign('user_answered', $user_answered);

		TPL::assign('attach_access_key', md5($this->user_id . time()));
		TPL::output('question/ajax/question_content');
	}

	public function begin_question_quiz_countdown_action ()
	{
		if(!($question_info = $this->model('question')->load_detailed_question_info($_GET['id'])))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		TPL::assign('question_info', $question_info);

		// 清算该用户所有未纪录的超时答题

		$this->complete_unfinished_question_quiz_record();

		// 添加临时答题记录

		$record_id = 0;
		if(!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND $this->user_id != $question_info['published_uid'])
		{
			$record_id = $this->model('quiz')->save_question_quiz_record($question_info['question_id'], $this->user_id, null, false, -1);
		}
		TPL::assign('question_quiz_record_id', $record_id);
		
		TPL::assign('show_question_content', true);
		TPL::assign('show_question_quiz', true);
		TPL::assign('answer_question_mode', true);

		TPL::output('question/ajax/question_content');
	}

	public function get_question_solution_action()
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			return;
		}

		// 检测用户权限

		if(!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid']))
		{
			if(!$this->model('solution')->get_question_solution_record($_GET['question_id'], $this->user_id))
			{
				return;
			}
		}

		// 获取答题选项答案

		if($question_info['quiz_id'])
		{
			$quiz_info = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id'], true);
			if(!$quiz_info)
			{
				return;
			}

			$quiz = json_decode($quiz_info['content'], true);
			if (!(json_last_error() === JSON_ERROR_NONE))
	        {
	            return;
	        }

	        $answer_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	        $answer_max_index = strlen($answer_alphabet);
	        switch($quiz['type'])
			{
				case 'singleSelection':
					foreach ($quiz['answers'] as $i => $answer) {
					 	if($answer['answer'])
					 	{
					 		if($i >= $answer_max_index)
					 		{
					 			return;
					 		}

					 		$quiz_answer .= '<li>' . $answer_alphabet[$i] . '、' . $quiz['options'][$i]['content'] . '</li>';

					 		break;
					 	}
					 }

					break;
				case 'multipleSelection':
					foreach ($quiz['answers'] as $i => $answer)
					{
						if($answer['answer'])
					 	{
					 		if($i >= $answer_max_index)
					 		{
					 			return;
					 		}

					 		$quiz_answer .= '<li>' . $answer_alphabet[$i] . '、' . $quiz['options'][$i]['content'] . '</li>'; 
					 	}
					}
					
					break;
				case 'crossword':
					$quiz_answer = '<li>' . $quiz['answers'][0]['answer'] . '</li>';
					
					break;
				case 'textInput':
					foreach ($quiz['answers'] as $i => $answer) 
					{
						$quiz_answer .= '<li>' . ($i + 1) . '、' . $quiz['options'][$i]['content'] . '：<span class="text-input-answer">' . $answer['answer'] . '</span></li>';
					}

					break;
			}
		}
		TPL::assign('quiz_answer', $quiz_answer);

		// 获取答案解析

		if($question_info['solution_id'])
		{
			$solution_info = $this->model('solution')->get_solution_info_by_id($question_info['solution_id']);
			$question_solution = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($solution_info['content']))));
			
			TPL::assign('question_solution', $question_solution);
		}

		TPL::output('question/ajax/question_solution');
	}

	function get_question_solution_record_action()
	{
		// 获取问题

		if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		// 获取用户最后一次答题的状态信息

		// $passed_quiz = false;
		// if($quiz_record = $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $this->user_id))
		// {
		// 	$passed_quiz = $quiz_record[0]['passed'];
		// }

		$solution_not_exist = !($question_info['solution_id'] OR $question_info['quiz_id']);
		if($solution_not_exist) 
		{
			H::ajax_json_output(array(
				'solution_not_exist' => true
			));
		}

		// 获取用户答题购买答案记录信息

		$solution_record = $this->model('solution')->get_question_solution_record($_GET['question_id'], $this->user_id);
		if($solution_record OR $this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid'])
		{
			H::ajax_json_output(array(
				'record_exist' => true 
			));	
		}

		// 输出答题解析积分信息

		$question_integral = get_setting('difficulty_level_' . $question_info['difficulty'] . '_integral') * abs(get_setting('question_quiz_solution_integral_coeffcient'));

		H::ajax_json_output(array(
			'record_exist' => false,
			'required_integral' => $question_integral 
		));	
	}

	function save_question_solution_record_action()
	{
		// 获取问题

		if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		$solution_info = $this->model('solution')->get_solution_info_by_id($question_info['solution_id']);
		if(!$question_info['quiz_id'] && !$solution_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('答案解析不存在')));
		}

		// 添加购买记录

		$solution_record_id = $this->model('solution')->save_question_solution_record($_GET['question_id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	function question_base_integral($question_id)
	{
		// 获取问题

		if (!$question_info = $this->model('question')->get_question_info_by_id(intval($question_id)))
		{
			return -1;
		}

		// 特殊用户不需要积分

		if($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid'])
		{
			return 0;
		}

		return get_setting('difficulty_level_' . $question_info['difficulty'] . '_integral');
	}

	function question_quiz_answer_integral($question_id, $correct)
	{
		$base_integral = $this->question_base_integral($question_id);

		if($base_integral <= 0)
		{
			return $base_integral;
		}

		$coefficient = 1.0;
		if($correct)
		{
			$coefficient = abs(get_setting('question_quiz_correct_integral_coeffcient'));

		}
		else 
		{
			$coefficient = abs(get_setting('question_quiz_wrong_integral_coeffcient'));
		}

		return $base_integral * $coefficient;
	}

	function get_question_quiz_retry_integral_action()
	{
		$required_integral = $this->question_base_integral($_GET['question_id']) * abs(get_setting('question_quiz_retry_integral_coeffcient'));
		if($required_integral < 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('计算积分失败')));
		}

		H::ajax_json_output(array(
			'not_enough_integral' => $this->user_info['integral'] < $required_integral,
			'user_integral' => $this->user_info['integral'],
			'required_integral' => $required_integral
		));
	}

	function save_question_quiz_retry_integral_action()
	{
		// 保存积分记录

		$required_integral = $this->question_base_integral($_GET['question_id']) * abs(get_setting('question_quiz_retry_integral_coeffcient'));
		if($required_integral < 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('计算积分失败')));
		}

		if ($this->user_info['integral'] < $required_integral)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分不足')));
		}

		$this->model('integral')->process($this->user_id, 'QUESTION_QUIZ_RETRY', -$required_integral, AWS_APP::lang()->_t('重新答题 #') . $_GET['question_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	function get_question_view_solution_integral_action()
	{
		$required_integral = $this->question_base_integral($_GET['question_id']) * abs(get_setting('question_quiz_solution_integral_coeffcient'));
		if($required_integral < 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('计算积分失败')));
		}

		H::ajax_json_output(array(
			'not_enough_integral' => $this->user_info['integral'] < $required_integral,
			'user_integral' => $this->user_info['integral'],
			'required_integral' => $required_integral
		));
	}

	function save_question_view_solution_integral_action()
	{
		// 保存积分记录

		$required_integral = $this->question_base_integral($_GET['question_id']) * abs(get_setting('question_quiz_solution_integral_coeffcient'));
		if($required_integral < 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('计算积分失败')));
		}

		if ($this->user_info['integral'] < $required_integral)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分不足')));
		}

		$this->model('integral')->process($this->user_id, 'QUESTION_VIEW_SOLUTION', -$required_integral, AWS_APP::lang()->_t('查看答题解析 #') . $_GET['question_id']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	function load_answers_action()
	{
		// 获取问题

		if (!$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			return;
		}

		$this->model('question')->calc_popular_value($question_info['question_id']);
		$this->model('question')->update_views($question_info['question_id']);

		if (! $_GET['sort'] or $_GET['sort'] != 'ASC')
		{
			$_GET['sort'] = 'DESC';
		}
		else
		{
			$_GET['sort'] = 'ASC';
		}

		if (is_digits($_GET['uid']))
		{
			$answer_list_where[] = 'uid = ' . intval($_GET['uid']);
			$answer_count_where = 'uid = ' . intval($_GET['uid']);
		}
		else if ($_GET['uid'] == 'focus' and $this->user_id)
		{
			if ($friends = $this->model('follow')->get_user_friends($this->user_id, false))
			{
				foreach ($friends as $key => $val)
				{
					$follow_uids[] = $val['uid'];
				}
			}
			else
			{
				$follow_uids[] = 0;
			}

			$answer_list_where[] = 'uid IN(' . implode($follow_uids, ',') . ')';
			$answer_count_where = 'uid IN(' . implode($follow_uids, ',') . ')';
			$answer_order_by = 'add_time ASC';
		}
		else if ($_GET['sort_key'] == 'add_time')
		{
			$answer_order_by = $_GET['sort_key'] . " " . $_GET['sort'];
		}
		else
		{
			$answer_order_by = "agree_count " . $_GET['sort'] . ", against_count ASC, add_time ASC";
		}

		if ($answer_count_where)
		{
			$answer_count = $this->model('answer')->get_answer_count_by_question_id($question_info['question_id'], $answer_count_where);
		}
		else
		{
			$answer_count = $question_info['answer_count'];
		}

		if (isset($_GET['answer_id']) and (! $this->user_id OR $_GET['single']))
		{
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($_GET['answer_id']));
		}
		else if (! $this->user_id AND !$this->user_info['permission']['answer_show'])
		{
			if ($question_info['best_answer'])
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($question_info['best_answer']));
			}
			else
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, null, 'agree_count DESC');
			}
		}
		else
		{
			if ($answer_list_where)
			{
				$answer_list_where = implode(' AND ', $answer_list_where);
			}

			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], 100), $answer_list_where, $answer_order_by);
		}

		// 最佳回复预留
		$answers[0] = '';

		if (! is_array($answer_list))
		{
			$answer_list = array();
		}

		$answer_ids = array();
		$answer_uids = array();

		foreach ($answer_list as $answer)
		{
			$answer_ids[] = $answer['answer_id'];
			$answer_uids[] = $answer['uid'];

			if ($answer['has_attach'])
			{
				$has_attach_answer_ids[] = $answer['answer_id'];
			}
		}

		if (!in_array($question_info['best_answer'], $answer_ids) AND intval($_GET['page']) < 2)
		{
			$answer_list = array_merge($this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . $question_info['best_answer']), $answer_list);
		}

		if ($answer_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_ids);

			$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_ids, $this->user_id);

			$answer_users_rated_thanks = $this->model('answer')->users_rated('thanks', $answer_ids, $this->user_id);
			$answer_users_rated_uninterested = $this->model('answer')->users_rated('uninterested', $answer_ids, $this->user_id);
			$answer_attachs = $this->model('publish')->get_attachs('answer', $has_attach_answer_ids, 'min');
		}

		foreach ($answer_list as $answer)
		{
			if ($answer['has_attach'])
			{
				$answer['attachs'] = $answer_attachs[$answer['answer_id']];

				$answer['insert_attach_ids'] = FORMAT::parse_attachs($answer['answer_content'], true);
			}

			$answer['user_rated_thanks'] = $answer_users_rated_thanks[$answer['answer_id']];
			$answer['user_rated_uninterested'] = $answer_users_rated_uninterested[$answer['answer_id']];

			$answer['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($answer['answer_content']))));

			$answer['agree_users'] = $answer_agree_users[$answer['answer_id']];
			$answer['agree_status'] = $answer_vote_status[$answer['answer_id']];

			if ($question_info['best_answer'] == $answer['answer_id'] AND intval($_GET['page']) < 2)
			{
				$answers[0] = $answer;
			}
			else
			{
				$answers[] = $answer;
			}

			// 获取回答评论列表

			$comments = $this->model('answer')->get_answer_comments($answer['answer_id']);

			$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

			foreach ($comments as $key => $val)
			{
				$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
				$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
				$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
			}
			
			$answer_comments[$answer['answer_id']] = $comments;
		}

		if (!$answers[0])
		{
			unset($answers[0]);
		}

		if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
		{
			$user_answered = true;
		}
		else
		{
			$user_answered = false;
		}

		TPL::assign('user_answered', $user_answered);
		TPL::assign('answers', $answers);
		TPL::assign('comments', $answer_comments);
		TPL::assign('answer_count', $answer_count);

		TPL::output('question/ajax/answer_list');
	}

	public function load_more_question_quiz_record_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			return;
		}

		$question_quiz_record = $this->model('quiz')->get_question_quiz_record_list_page($question_info['question_id'], $_GET['page'], 10);

		TPL::assign('question_quiz_record_list', $question_quiz_record);

		TPL::output('block/quiz_record_list');
	}

	public function load_more_question_quiz_record_user_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']))
		{
			return;
		}

		$records = $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $_GET['uid'], $_GET['page'], 10);
		TPL::assign('quiz_record', $records);
		TPL::output('question/ajax/user_quiz_record_list');
	}

	public function invited_users_action()
	{
		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);
		if(!$question_info)
		{
			return;
		}

		$invited_users = $this->model('question')->get_invited_user_list($question_info['question_id'], $_GET['page'], 10);
		if($invited_users)
		{
			foreach ($invited_users as $key => $val) 
			{
				$exclude_uids[] = $val['recipients_uid'];
				$uids[] = $val['recipients_uid'];
				$uids[] = $val['sender_uid'];
			}

			$users_info = $this->model('account')->get_user_info_by_uids($uids, true);

			foreach ($invited_users as $key => $val) 
			{
				$invited_users[$key]['recipient_info'] = $users_info[$val['recipients_uid']];
				$invited_users[$key]['sender_info'] = $users_info[$val['sender_uid']]; 

				// 答题状态及记录

				if($question_info['quiz_id'])
				{
					// 获取答题记录

					$quiz_count = 0;
					$passed_quiz = false;
					if($quiz_record = $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $val['recipients_uid'], 1, 5))
					{
						$quiz_count = count($quiz_record);
						$passed_quiz = $quiz_record[0]['passed'];
					}

					$invited_users[$key]['quiz_record'] = $quiz_record;
					$invited_users[$key]['quiz_passed'] = $passed_quiz;
					$invited_users[$key]['quiz_count'] = $quiz_count;
				}
				else 
				{
					// 是否评论

					$invited_users[$key]['answered'] = $this->model('answer')->has_answer_by_uid($question_info['question_id'], $val['recipients_uid']);
				}
			}
		}

		TPL::assign('question_info', $question_info);
		TPL::assign('invited_users', $invited_users);
		TPL::output('question/ajax/invited_users');
	}

	public function user_invited_users_action()
	{
		if($this->user_id)
		{
			$invite_limit = get_setting('user_question_invite_limit');
			$user_invitations = $this->model('question')->get_invited_users($_GET['question_id'], $_GET['uid'], 5);
			$user_invite_count = $this->model('question')->get_invited_user_count($_GET['question_id'], $_GET['uid']);
			$remaind_invite_count = $invite_limit - $user_invite_count;

			$user_invitations_all = $this->model('question')->get_invited_users($_GET['question_id'], $_GET['uid'], $invite_limit);
			$uids = null;
			foreach ($user_invitations_all as $key => $val) 
			{
				$uids[] = $val['recipients_uid'];
			}
			
			$users_info = $this->model('account')->get_user_info_by_uids($uids, true);
			foreach ($user_invitations_all as $key => $val) 
			{
				$user_invitations_all[$key]['recipient_info'] = $users_info[$val['recipients_uid']];
			}

			TPL::assign('user_invite_count', $user_invite_count);
			TPL::assign('user_invitations', $user_invitations);
			TPL::assign('user_invitations_all', $user_invitations_all);
			TPL::assign('remaind_invite_count', $remaind_invite_count);

			TPL::output('question/ajax/user_invited_users');
		}
	}
}