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

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_explore'])
		{
			$rule_action['actions'][] = 'list';
		}

		return $rule_action;
	}

	public function list_action()
	{
		if ($_GET['feature_id'])
		{
			$topic_ids = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id']);
		}
		else
		{
			$topic_ids = explode(',', $_GET['topic_id']);
		}

		if ($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}
		else
		{
			$per_page = get_setting('contents_per_page');
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$posts_list = $this->model('posts')->get_hot_posts($_GET['post_type'], $_GET['category'], $topic_ids, $_GET['day'], $_GET['page'], $per_page);
		}
		else
		{
			$posts_list = $this->model('posts')->get_posts_list($_GET['post_type'], $_GET['page'], $per_page, $_GET['sort_type'], $topic_ids, $_GET['category'], $_GET['answer_count'], $_GET['day'], $_GET['is_recommend']);
		}

		if (!is_mobile() AND $posts_list)
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
				}
			}
		}

		TPL::assign('posts_list', $posts_list);

		if (is_mobile())
		{
			TPL::output('m/ajax/explore_list');
		}
		else
		{
			TPL::output('explore/ajax/list');
		}
	}

	public function load_question_list_action()
	{
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

				$question_quiz_stats['total'] = 0;
				$question_quiz_stats['passed'] = 0;
				$question_quiz_record = $this->model('quiz')->get_question_quiz_record_by_question($question_info['question_id']);
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
				$recommend_question_list[$key]['quiz_stats'] = $question_quiz_stats;
			}
		}

		TPL::assign('question_list', $recommend_question_list);
		TPL::output('block/question_list.tpl.htm');
	}
}