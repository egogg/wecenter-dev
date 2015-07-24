<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

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
			'get_question_comments',
			'get_answer_comments',
			'log',
			'get_focus_users',
			'get_answer_users',
			'fetch_share_data',
			'check_quiz_answer',
			'init_question_content',
			'begin_question_quiz_countdown'
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
			$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

			foreach($focus_users_info as $key => $val)
			{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
					$focus_users[$key] = array(
						'uid' => 0,
						'user_name' => AWS_APP::lang()->_t('匿名用户'),
						'avatar_file' => get_avatar_url(0, 'mid'),
					);
				}
				else
				{
					$focus_users[$key] = array(
						'uid' => $val['uid'],
						'user_name' => $val['user_name'],
						'avatar_file' => get_avatar_url($val['uid'], 'mid'),
						'url' => get_js_url('/people/' . $val['url_token'])
					);
				}
			}
		}

		H::ajax_json_output($focus_users);
	}

	public function save_invite_action()
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (!$invite_user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($invite_user_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
		}

		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		}

		if ($this->model('answer')->has_answer_by_uid($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已经回答过该问题')));
		}

		if ($question_info['published_uid'] == $invite_user_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请问题的发起者回答问题')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已接受过邀请')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
		}

		$this->model('question')->add_invite($_POST['question_id'], $this->user_id, $invite_user_info['uid']);

		$this->model('account')->update_question_invite_count($invite_user_info['uid']);

		if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($invite_user_info['uid']) AND $invite_user_info['weixin_settings']['QUESTION_INVITE'] != 'N')
		{
			$this->model('weixin')->send_text_message($weixin_user['openid'], "有会员在问题 [" . $question_info['question_content'] . "] 邀请了你进行回答", $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']));
		}

		$notification_id = $this->model('notify')->send($this->user_id, $invite_user_info['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval($_POST['question_id']), array(
			'from_uid' => $this->user_id,
			'question_id' => intval($_POST['question_id'])
		));

		$this->model('email')->action_email('QUESTION_INVITE', $_POST['uid'], get_js_url('/question/' . $question_info['question_id'] . '?notification_id-' . $notification_id), array(
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

		TPL::assign('comments', $comments);

		if (is_mobile())
		{
			TPL::output("m/ajax/question_comments");
		}
		else
		{
			TPL::output("question/ajax/comments");
		}
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
				'url' => get_js_url('/publish/wait_approval/question_id-' . $question_info['question_id'] . '__is_mobile-' . $_POST['_is_mobile'])
			), 1, null));
		}
		else
		{
			$answer_id = $this->model('publish')->publish_answer($question_info['question_id'], $answer_content, $this->user_id, $_POST['anonymous'], $_POST['attach_access_key'], $_POST['auto_focus']);

			if ($_POST['_is_mobile'])
			{
				//$url = get_js_url('/m/question/id-' . $question_info['question_id'] . '__item_id-' . $answer_id . '__rf-false');

				$this->model('answer')->set_answer_publish_source($answer_id, 'mobile');
			}
			else
			{
				//$url = get_js_url('/question/' . $question_info['question_id'] . '?item_id=' . $answer_id . '&rf=false');
			}

			$answer_info = $this->model('answer')->get_answer_by_id($answer_id);


			if ($answer_info['has_attach'])
			{
				$answer_info['attachs'] = $this->model('publish')->get_attach('answer', $answer_id, 'min');

				$answer_info['insert_attach_ids'] = FORMAT::parse_attachs($answer_info['answer_content'], true);
			}

			$answer_info['user_info'] = $this->user_info;
			$answer_info['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($answer_info['answer_content']))));

			TPL::assign('answer_info', $answer_info);

			if (is_mobile())
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'ajax_html' => TPL::output('m/ajax/question_answer', false)
				), 1, null));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'ajax_html' => TPL::output('question/ajax/answer', false)
				), 1, null));
			}


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
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请填写正确的 Email')));
		}

		if ($_POST['email'] == $this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你不能邀请自己')));
		}

		if ($this->model('question')->check_email_invite($_GET['question_id'], $this->user_id, $_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('此 E-mail 已接收过邀请')));
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

	// 检查问题答案

	public function question_quiz_check_answer_action()
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

		$is_sepcial_user = ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid']);
		
		// 检查是否为限时答题

		$is_countdown = ($quiz['countdown'] > 0);

		// 保存答题记录

		if(!$is_sepcial_user)
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
		}

		// 用户答题记录

		$try_count = 0;
		$passed_quiz = false;
		if($quiz_record = $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $this->user_id))
		{
			$try_count = count($quiz_record);
			$passed_quiz = $quiz_record[0]['passed'];
		}

		// 总答题记录信息

		$question_quiz_stats_total = 0;
		$question_quiz_stats_passed = 0;
		$question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($question_info['question_id']);
		if($question_quiz_record)
		{
			foreach ($question_quiz_record as $key => $value) {
				if($value['passed'])
				{
					$question_quiz_stats_passed++;
				}

				$question_quiz_stats_total++;
			}
		}

        H::ajax_json_output(array(
        	'is_countdown' => $is_countdown,
        	'user_answer' => $user_answer,
        	'spend_time' => $spend_time,
        	'correct' => $is_correct_answer,
        	'try_count' => $try_count,
        	'passed_quiz' => $passed_quiz,
        	'quiz_stats_total' => $question_quiz_stats_total,
        	'quiz_stats_passed' => $question_quiz_stats_passed
        ));
	}

	public function question_quiz_timeout_action () 
	{
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function init_question_content_action ()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if ($question_info['has_attach'])
		{
			$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min');

			$question_info['attachs_ids'] = FORMAT::parse_attachs($question_info['question_detail'], true);
		}

		$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($question_info['question_detail'])));
		
		// 答题选项

		if(intval($question_info['quiz_id']) > 0) 
		{
			$question_quiz = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id']);

			TPL::assign('question_quiz', $question_quiz);
		}
		TPL::assign('question_info', $question_info);

		// 用户答题记录

		$try_count = 0;
		$passed_quiz = false;
		if($quiz_record = $this->model('quiz')->get_question_quiz_record_by_user($question_info['question_id'], $this->user_id))
		{
			$try_count = count($quiz_record);
			$passed_quiz = $quiz_record[0]['passed'];
		}
		TPL::assign('passed_quiz', $passed_quiz);
		TPL::assign('try_count', $try_count);

		if($question_quiz && $question_quiz['countdown'] > 0)
		{
			// 限时答题

			if($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR $this->user_id == $question_info['published_uid'])
			{
				// 对于特殊用户，直接返回问题内容

				TPL::output('question/ajax/question_content');
			}
			else
			{
				// 对于普通用户

				if(!$quiz_record)
				{
					TPL::output('question/ajax/question_content_countdown');
				}
				else
				{
					if($quiz_record[0]['passed'])
					{
						TPL::output('question/ajax/question_content');
					}
					else
					{
						TPL::output('question/ajax/question_content_countdown');
					}
				}
			}
		}
		else
		{
			// 非限时答题

			TPL::output('question/ajax/question_content');
		}
	}

	public function begin_question_quiz_countdown_action ()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if ($question_info['has_attach'])
		{
			$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min');

			$question_info['attachs_ids'] = FORMAT::parse_attachs($question_info['question_detail'], true);
		}

		$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($question_info['question_detail'])));
		
		// 答题选项

		if(intval($question_info['quiz_id']) > 0) 
		{
			$question_quiz = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id']);

			TPL::assign('question_quiz', $question_quiz);
		}
		TPL::assign('question_info', $question_info);

		// 添加临时答题记录

		$record_id = 0;
		if(!$this->user_info['permission']['is_administortar'] AND !$this->user_info['permission']['is_moderator'] AND $this->user_id != $question_info['published_uid'])
		{
			$record_id = $this->model('quiz')->save_question_quiz_record($question_info['question_id'], $this->user_id, null, false, -1);
		}
		TPL::assign('question_quiz_record_id', $record_id);
		
		TPL::output('question/ajax/question_content');
	}
}