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
		$rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		if ($this->user_info['permission']['visit_explore'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}
	
	public function setup()
	{
		if (is_mobile() AND !$_GET['ignore_ua_check'])
		{
			switch ($_GET['app'])
			{
				default:
					HTTP::redirect('/m/');
				break;
			}
		}
	}

	public function index_action()
	{
		if (is_mobile())
		{
			HTTP::redirect('/m/explore/' . $_GET['id']);
		}

		if ($this->user_id)
		{
			$this->crumb(AWS_APP::lang()->_t('发现'), '/explore');

			if (! $this->user_info['email'])
			{
				HTTP::redirect('/account/complete_profile/');
			}
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

			$this->crumb($category_info['title'], '/category-' . $category_info['id']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		// 首页幻灯片

		$slides = $this->model('slide')->get_frontend_slides();
		TPL::assign('slides', $slides);

		// 导航
		
		if (TPL::is_output('block/content_nav_menu.tpl.htm', 'explore/index'))
		{
			TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('explore'));
		}

		// 置顶问题

		$recommend_items = $this->model('recommend')->get_recommend_homepage_items('top_question', $limit = 4);
		foreach ($recommend_items as $key => $item) {
			$question_info = $this->model('question')->get_question_info_by_id($item['item_id']);
			if ($question_info['has_attach'])
			{
				$question_info['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'square');
			}

			// 答题选项

			if($question_info['quiz_id'] > 0) 
			{
				$question_info['quiz_info'] = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id']);
			}
			
			// 分类信息

			$question_info['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);

			// 答题统计

			$question_quiz_total = 0;
			$question_quiz_passed = 0;
			$question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($question_info['question_id']);
			if($question_quiz_record)
			{
				foreach ($question_quiz_record as $i => $v) {
					if($v['passed'])
					{
						$question_quiz_passed++;
					}

					$question_quiz_total++;
				}


				$question_info['quiz_count'] = $question_quiz_total;
				if($question_quiz_total)
				{
					$question_info['pass_rate'] = $question_quiz_passed / $question_quiz_total;
				}
				else
				{
					$question_info['pass_rate'] = 0.0;
				}
			}

			$top_question_list[$key] = $question_info;
		}
		
		TPL::assign('top_question_list', $top_question_list);
		
		// 精选问题

		$recommend_items = $this->model('recommend')->get_recommend_question_items($_GET['page'], get_setting('contents_per_page'));
		foreach ($recommend_items as $key => $item) {
			$question_info = $this->model('question')->get_question_info_by_id($item['item_id']);
			$recommend_question_list[$key] = $question_info;

			// 获取发表问题用户信息

			$recommend_question_list[$key]['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['published_uid']);

			// 获取问题分类信息

			$recommend_question_list[$key]['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);

			// 获取问题评论

			if ($question_info['answer_count'])
			{
				$recommend_question_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($question_info['question_id'], 2, $question_info['published_uid']);
			}

			// 获取问题缩略图

			if ($question_info['has_attach'])
			{
				$recommend_question_list[$key]['attachs'] = $this->model('publish')->get_attach('question', $question_info['question_id'], 'square');
			}

			// 获取答题选项信息

			if($question_info['quiz_id'])
			{
				$recommend_question_list[$key]['quiz_info'] = $this->model('quiz')->get_question_quiz_info_by_id($question_info['quiz_id']);

				// 获取当前用户答题信息

				if($this->user_id > 0) 
				{
					$recommend_question_list[$key]['user_record_count'] = $this->model('quiz')->get_question_quiz_user_record_count($question_info['question_id'], $this->user_id);
				}
			
				// 获取答题统计信息

				$recommend_question_list[$key]['count_info'] = $this->model('quiz')->get_question_quiz_count_info_by_question_id($question_info['question_id']);
				// $question_quiz_stats['total'] = 0;
				// $question_quiz_stats['passed'] = 0;
				// $question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($question_info['question_id']);
				// if($question_quiz_record)
				// {
				// 	foreach ($question_quiz_record as $i => $v) {
				// 		if($v['passed'])
				// 		{
				// 			$question_quiz_stats['passed']++;
				// 		}

				// 		$question_quiz_stats['total']++;
				// 	}

				// 	if($question_quiz_stats['total'])
				// 	{
				// 		$question_quiz_stats['rate'] = $question_quiz_stats['passed'] / $question_quiz_stats['total'];
				// 	}
				// 	else
				// 	{
				// 		$question_quiz_stats['rate'] = 0.0;
				// 	}
				// }
				// $recommend_question_list[$key]['quiz_stats'] = $question_quiz_stats;
			}
		}

		TPL::assign('recommend_homepage_questions', $recommend_question_list);
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/'),
			'total_rows' => $this->model('recommend')->get_recommend_question_total(),
			'per_page' => get_setting('contents_per_page'),
			'num_links' => 2
		))->create_links());

		// 精选专题
		
		$recommend_items = $this->model('recommend')->get_recommend_homepage_items('topic', $limit = 4);
		foreach ($recommend_items as $key => $item) {
			$topic_info = $this->model('topic')->get_topic_by_id($item['item_id']);
			$topic_info['topic_description'] = nl2br(FORMAT::parse_bbcode($topic_info['topic_description']));
			if ($topic_info['parent_id'])
			{
				$parent_topic_info = $this->model('topic')->get_topic_by_id($topic_info['parent_id']);
				$topic_info['category'] = $parent_topic_info['topic_title'];
				$topic_info['category_id'] = $parent_topic_info['topic_id'];
			}

			$recommend_homepage_topics[$key] = $topic_info;
		}

		TPL::assign('recommend_homepage_topics', $recommend_homepage_topics);

		// 精选知识

		$recommend_items = $this->model('recommend')->get_recommend_homepage_items('article', $limit = 5);
		foreach ($recommend_items as $key => $item) {
			$article_ids[] = $item['item_id'];;
		}

		// 获取文章缩略图

		$article_attachs = $this->model('publish')->get_attachs('article', $article_ids, 'min');

		foreach ($recommend_items as $key => $item) {
			$article_info = $this->model('article')->get_article_info_by_id($item['item_id']);
			$article_info['attachs'] = $article_attachs[$article_info['id']];

			$recommend_homepage_articles[$key] = $article_info;
		}
		TPL::assign('recommend_homepage_articles', $recommend_homepage_articles);

		// 边栏可能感兴趣的人
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));
		}

		// 边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_hot_users', $this->model('module')->sidebar_hot_users($this->user_id, 5));
		}

		// 边栏热门话题
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'explore/index'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($category_info['id']));
		}

		// 边栏专题
		if (TPL::is_output('block/sidebar_feature.tpl.htm', 'explore/index'))
		{
			TPL::assign('feature_list', $this->model('module')->feature_list());
		}

		// if (! $_GET['sort_type'] AND !$_GET['is_recommend'])
		// {
		// 	$_GET['sort_type'] = 'new';
		// }

		// if ($_GET['sort_type'] == 'hot')
		// {
		// 	$posts_list = $this->model('posts')->get_hot_posts(null, $category_info['id'], null, $_GET['day'], $_GET['page'], get_setting('contents_per_page'));
		// }
		// else
		// {
		// 	$posts_list = $this->model('posts')->get_posts_list(null, $_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], null, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		// }

		// if ($posts_list)
		// {
		// 	foreach ($posts_list AS $key => $val)
		// 	{
		// 		if ($val['answer_count'])
		// 		{
		// 			$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
		// 		}
		// 	}
		// }

		// TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
		// 	'base_url' => get_js_url('/sort_type-' . preg_replace("/[\(\)\.;']/", '', $_GET['sort_type']) . '__category-' . $category_info['id'] . '__day-' . intval($_GET['day']) . '__is_recommend-' . intval($_GET['is_recommend'])),
		// 	'total_rows' => $this->model('posts')->get_posts_list_total(),
		// 	'per_page' => get_setting('contents_per_page')
		// ))->create_links());

		// TPL::assign('posts_list', $posts_list);
		// TPL::assign('posts_list_bit', TPL::output('explore/ajax/list', false));

		TPL::output('explore/index');
	}
}