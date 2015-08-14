<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index',
				'square'
			);
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		if (is_mobile())
		{
			HTTP::redirect('/m/question/' . $_GET['id']);
		}

		if ($_GET['column'] == 'log' AND !$this->user_id)
		{
			HTTP::redirect('/question/' . $_GET['id']);
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在或已被删除'), '/question/');
		}

		if (! $_GET['sort'] or $_GET['sort'] != 'ASC')
		{
			$_GET['sort'] = 'DESC';
		}
		else
		{
			$_GET['sort'] = 'ASC';
		}

		if (get_setting('unfold_question_comments') == 'Y')
		{
			$_GET['comment_unfold'] = 'all';
		}

		$question_info['redirect'] = $this->model('question')->get_redirect($question_info['question_id']);

		if ($question_info['redirect']['target_id'])
		{
			$target_question = $this->model('question')->get_question_info_by_id($question_info['redirect']['target_id']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_question = $this->model('question')->get_question_info_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('从问题 %s 跳转而来', '<a href="' . get_js_url('/question/' . $_GET['rf'] . '?rf=false') . '">' . $from_question['question_content'] . '</a>');
			}
		}

		if ($question_info['redirect'] and ! $_GET['rf'])
		{
			if ($target_question)
			{
				HTTP::redirect('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']);
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}
		else if ($question_info['redirect'])
		{
			if ($target_question)
			{
				$message = AWS_APP::lang()->_t('此问题将跳转至') . ' <a href="' . get_js_url('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']) . '">' . $target_question['question_content'] . '</a>';

				if ($this->user_id AND ($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'] OR (!$this->question_info['lock'] AND $this->user_info['permission']['redirect_question'])))
				{
					$message .= '&nbsp; (<a href="javascript:;" onclick="AWS.ajax_request(G_BASE_URL + \'/question/ajax/redirect/\', \'item_id=' . $question_info['question_id'] . '\');">' . AWS_APP::lang()->_t('撤消重定向') . '</a>)';
				}

				$redirect_message[] = $message;
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}

		if ($question_info['has_attach'])
		{
			$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'min');

			$question_info['attachs_ids'] = FORMAT::parse_attachs($question_info['question_detail'], true);
		}

		if ($question_info['category_id'] AND get_setting('category_enable') == 'Y')
		{
			$question_info['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);
		}

		$question_info['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['published_uid'], true);

		if ($_GET['column'] != 'log')
		{
			$this->model('question')->calc_popular_value($question_info['question_id']);
			$this->model('question')->update_views($question_info['question_id']);

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
			}

			if (! $answers[0])
			{
				unset($answers[0]);
			}

			if (get_setting('answer_unique') == 'Y')
			{
				if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
				{
					TPL::assign('user_answered', 1);
				}
				else
				{
					TPL::assign('user_answered', 0);
				}
			}

			TPL::assign('answers', $answers);
			TPL::assign('answer_count', $answer_count);
		}

		if ($this->user_id)
		{
			TPL::assign('question_thanks', $this->model('question')->get_question_thanks($question_info['question_id'], $this->user_id));

			TPL::assign('invite_users', $this->model('question')->get_invite_users($question_info['question_id']));

			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $question_info['published_uid']));

			if ($this->user_info['draft_count'] > 0)
			{
				TPL::assign('draft_content', $this->model('draft')->get_data($question_info['question_id'], 'answer', $this->user_id));
			}
		}

		$question_info['question_detail'] = FORMAT::parse_attachs(nl2br(FORMAT::parse_bbcode($question_info['question_detail'])));

		TPL::assign('question_info', $question_info);
		TPL::assign('question_focus', $this->model('question')->has_focus_question($question_info['question_id'], $this->user_id));

		$question_topics = $this->model('topic')->get_topics_by_item_id($question_info['question_id'], 'question');

		if (sizeof($question_topics) == 0 AND $this->user_id)
		{
			$related_topics = $this->model('question')->get_related_topics($question_info['question_content']);

			TPL::assign('related_topics', $related_topics);
		}

		TPL::assign('question_topics', $question_topics);

		TPL::assign('question_related_list', $this->model('question')->get_related_question_list($question_info['question_id'], $question_info['question_content']));
		TPL::assign('question_related_links', $this->model('related')->get_related_links('question', $question_info['question_id']));

		if ($this->user_id)
		{
			if ($question_topics)
			{
				foreach ($question_topics AS $key => $val)
				{
					$question_topic_ids[] = $val['topic_id'];
				}
			}

			if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($question_topic_ids, 17))
			{
				foreach ($helpful_users AS $key => $val)
				{
					if ($val['user_info']['uid'] == $this->user_id)
					{
						unset($helpful_users[$key]);
					}
					else
					{
						$helpful_users[$key]['has_invite'] = $this->model('question')->has_question_invite($question_info['question_id'], $val['user_info']['uid'], $this->user_id);

						$helpful_users[$key]['experience'] = end($helpful_users[$key]['experience']);
					}
				}

				TPL::assign('helpful_users', $helpful_users);
			}
		}

		$this->crumb($question_info['question_content'], '/question/' . $question_info['question_id']);

		if ($_GET['column'] == 'log')
		{
			$this->crumb(AWS_APP::lang()->_t('日志'), '/question/id-' . $question_info['question_id'] . '__column-log');
		}
		else
		{
			TPL::assign('human_valid', human_valid('answer_valid_hour'));

			if ($this->user_id)
			{
				TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
					'base_url' => get_js_url('/question/id-' . $question_info['question_id'] . '__sort_key-' . $_GET['sort_key'] . '__sort-' . $_GET['sort'] . '__uid-' . $_GET['uid']),
					'total_rows' => $answer_count,
					'per_page' => 100
				))->create_links());
			}
		}

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($question_info['question_content'])));

		TPL::set_meta('description', $question_info['question_content'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($question_info['question_detail'])), 0, 128, 'UTF-8', '...'));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		if (get_setting('upload_enable') == 'Y')
		{
			// fileupload
			TPL::import_js('js/fileupload.js');
		}

		TPL::assign('attach_access_key', md5($this->user_id . time()));
		TPL::assign('redirect_message', $redirect_message);

		$recommend_posts = $this->model('posts')->get_recommend_posts_by_topic_ids($question_topic_ids);

		if ($recommend_posts)
		{
			foreach ($recommend_posts as $key => $value)
			{
				if ($value['question_id'] AND $value['question_id'] == $question_info['question_id'])
				{
					unset($recommend_posts[$key]);

					break;
				}
			}

			TPL::assign('recommend_posts', $recommend_posts);
		}

		// 获取问题答题记录信息

		$question_quiz_stats['total'] = 0;
		$question_quiz_stats['passed'] = 0;
		$question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($question_info['question_id']);
		if($question_quiz_record)
		{
			foreach ($question_quiz_record as $key => $value) {
				if($value['passed'])
				{
					$question_quiz_stats['passed']++;
				}

				$question_quiz_stats['total']++;
			}
		}
		TPL::assign('question_quiz_stats', $question_quiz_stats);

		// 获取是否显示评论区

		$enable_comment = (!$question_info['quiz_id'] OR $question_quiz_stats > 0);
		TPL::assign('enable_comment', $enable_comment);

		// 添加题目解析提示提示

		if($this->user_id == $question_info['published_uid'] AND !$question_info['solution_id'])
		{
			TPL::assign('show_add_solution_hint', true);
		}

		// 获取上一道题目和下一道题目的信息

		$question_info_next = $this->model('question')->get_next_question_info($question_info['question_id']);
		$question_info_previous = $this->model('question')->get_previous_question_info($question_info['question_id']);
		TPL::assign('question_info_next', $question_info_next);
		TPL::assign('question_info_previous', $question_info_previous);

		TPL::import_js('js/app/question.js');
		TPL::import_css('css/question.css');
		TPL::import_js('js/quiz.js');
		TPL::import_css('css/quiz.css');
		TPL::import_js('js/sweetalert.min.js');
		TPL::import_css('css/sweetalert.css');

		TPL::output('question/index');
	}

	public function index_square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('问题'), '/question/');

		// 导航
		// if (TPL::is_output('block/content_nav_menu.tpl.htm', 'question/square'))
		// {
		// 	TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('question'));
		// }

		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('question'));

		//边栏可能感兴趣的人
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));
		}

		//边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_hot_users', $this->model('module')->sidebar_hot_users($this->user_id, 5));
		}

		//边栏热门话题
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($_GET['category']));
		}

		TPL::assign('sidebar_hot_topics', $this->model('topic')->get_hot_topics(0, 5));

		//边栏专题
		if (TPL::is_output('block/sidebar_feature.tpl.htm', 'question/square'))
		{
			TPL::assign('feature_list', $this->model('module')->feature_list());
		}

		if ($_GET['category'])
		{
			if (is_digits($_GET['category']))
			{
				$category_info = $this->model('system')->get_category_info($_GET['category']);
			}
			else
			{
				$category_info = $this->model('system')->get_category_info_by_url_token($_GET['category']);
			}
		}

		if ($category_info)
		{
			TPL::assign('category_info', $category_info);

			$this->crumb($category_info['title'], '/question/category-' . $category_info['id']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		if ($_GET['feature_id'])
		{
			$feature_info = $this->model('feature')->get_feature_by_id($_GET['feature_id']);

			TPL::assign('feature_info', $feature_info);
		}

		if ($feature_info['id'])
		{
			$topic_ids = $this->model('feature')->get_topics_by_feature_id($feature_info['id']);
		}

		// if ($_GET['sort_type'] == 'hot')
		// {
		// 	$question_list = $this->model('posts')->get_hot_posts('question', $category_info['id'], $topic_ids, $_GET['day'], $_GET['page'], get_setting('contents_per_page'));
		// }
		// else
		// {
		// 	$question_list = $this->model('posts')->get_posts_list('question', $_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $topic_ids, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		// }

		$filter_info = array(
			'sort_type' => $_GET['sort_type'],
			'category_id' => $category_info['id'],
			'difficulty' => intval($_GET['difficulty']),
			'quiztype' => intval($_GET['quiztype']),
			'countdown' => intval($_GET['countdown']),
		);

		TPL::assign('filter_info', $filter_info);

		$question_list = $this->model('question')->get_question_list($_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $category_info['id'], $_GET['difficulty'], $_GET['quiztype'], $_GET['countdown']);

		if ($question_list)
		{
			foreach ($question_list AS $key => $val)
			{	
				// 获取发表问题用户信息

				$question_list[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($val['published_uid']);

				// 获取问题分类信息

				$question_list[$key]['category_info'] = $this->model('system')->get_category_info($val['category_id']);

				// 获取问题评论

				if ($val['answer_count'])
				{
					$question_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
				}

				// 获取问题缩略图

				if ($val['has_attach'])
				{
					$question_list[$key]['attachs'] = $this->model('publish')->get_attach('question', $val['question_id'], 'square');
				}

				// 获取答题选项信息

				if($val['quiz_id'])
				{
					$question_list[$key]['quiz_info'] = $this->model('quiz')->get_question_quiz_info_by_id($val['quiz_id']);
				
					// 获取答题统计信息

					$question_quiz_stats['total'] = 0;
					$question_quiz_stats['passed'] = 0;
					$question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($val['question_id']);
					if($question_quiz_record)
					{
						foreach ($question_quiz_record as $i => $v) {
							if($v['passed'])
							{
								$question_quiz_stats['passed']++;
							}

							$question_quiz_stats['total']++;
						}

						if($question_quiz_stats['total'])
						{
							$question_quiz_stats['rate'] = $question_quiz_stats['passed'] / $question_quiz_stats['total'];
						}
						else
						{
							$question_quiz_stats['rate'] = 0.0;
						}
					}
					$question_list[$key]['quiz_stats'] = $question_quiz_stats;
				}
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/question/sort_type-' . preg_replace("/[\(\)\.;']/", '', $_GET['sort_type']) . '__category-' . $category_info['id'] . '__difficulty-' . $_GET['difficulty'] . '__quiztype-' . $_GET['quiztype'] . '__countdown-' . $_GET['countdown'] . '__is_recommend-' . $_GET['is_recommend']),
			'total_rows' => $this->model('question')->get_question_list_total(),
			'per_page' => get_setting('contents_per_page'),
			'num_links' => 2
		))->create_links());

		TPL::assign('question_list', $question_list);
		// TPL::assign('question_list_bit', TPL::output('explore/ajax/list', false));

		TPL::output('question/square');
	}

	public function unverify_modify_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']) or ! $_GET['log_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在'), '/');
		}

		if (($question_info['published_uid'] != $this->user_id) AND (! $this->user_info['permission']['is_administortar']) AND (! $this->user_info['permission']['is_moderator']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'), '/');
		}

		$this->model('question')->unverify_modify($_GET['question_id'], $_GET['log_id']);

		H::redirect_msg(AWS_APP::lang()->_t('取消确认修改成功, 正在返回...'), '/question/id-' . $_GET['question_id'] . '__column-log__rf-false');
	}

	public function verify_modify_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']) or ! $_GET['log_id'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('问题不存在'), '/');
		}

		if (($question_info['published_uid'] != $this->user_id) AND (! $this->user_info['permission']['is_administortar']) AND (! $this->user_info['permission']['is_moderator']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'), '/');
		}

		$this->model('question')->verify_modify($_GET['question_id'], $_GET['log_id']);

		H::redirect_msg(AWS_APP::lang()->_t('确认修改成功, 正在返回...'), '/question/id-' . $_GET['question_id'] . '__column-log__rf-false');
	}
}