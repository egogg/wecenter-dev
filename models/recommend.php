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

class recommend_class extends AWS_MODEL
{
	public function recommend_homepage_add($item_type, $item_id)
	{	
		return $this->insert('recommend_homepage', array(
			'item_id' => intval($item_id),
			'item_type' => $item_type,
			'add_time' => time()
		));
	}

	public function recommend_homepage_check($item_type, $item_id)
	{
		return $this->fetch_one('recommend_homepage', 'id', 'item_id = ' . intval($item_id) . ' AND item_type = "' . $item_type . '"');
	}

	public function recommend_homepage_del($item_type, $item_id)
	{
		return $this->delete('recommend_homepage', 'item_id = ' . intval($item_id) . ' AND item_type = "' . $item_type . '"');
	}

	public function recommend_homepage_remove_by_id($id)
	{
		return $this->delete('recommend_homepage', 'id = ' . intval($id));
	}

	public function get_recommend_homepage_items($item_type, $limit)
	{
		return $this->fetch_all('recommend_homepage', 'item_type = "' . $item_type . '"', 'add_time' ,intval($limit));
	}

	public function get_recommend_question_items($page = 1, $per_page = 10, $sort = null)
	{
		$order_key = 'add_time DESC';

		$question_items = $this->fetch_page('recommend_homepage', 'item_type = "question"', $order_key, $page, $per_page);
		$this->question_item_total = $this->found_rows();

		return $question_items;
	}

	public function get_recommend_question_total()
	{
		return $this->question_item_total;
	}
}
