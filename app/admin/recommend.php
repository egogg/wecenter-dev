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

class recommend extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('首页精选管理'), "admin/recommend/list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(603));
    }

    public function list_action()
    {
        if($recommend_homepage_list = $this->model('recommend')->fetch_page('recommend_homepage', 'id > 0', ' add_time DESC', $_GET['page'], $this->per_page))
        {
            $total_rows = $this->model('recommend')->found_rows();

            foreach ($recommend_homepage_list AS $key => $val)
            {
                switch($val['item_type'])
                {
                    case 'question' :
                    {
                        $val['item_tag'] = '问题（问题）';
                        $val['item_link'] = 'question/' . $val['item_id'];
                        $question_info = $this->model('question')->get_question_info_by_id($val['item_id']);
                        if($question_info)
                        {
                            $val['item_title'] = $question_info['question_content'];
                        }
                        else
                        {
                            $val['item_title'] = '问题 #' . $val['item_id']; 
                        }
                        
                        break;
                    }
                    case 'article' :
                    {
                        $val['item_tag'] = '文章（知识）';
                        $val['item_link'] = 'article/' . $val['item_id'];
                        $article_info = $this->model('article')->get_article_info_by_id($val['item_id']);
                        if($article_info)
                        {
                            $val['item_title'] = $article_info['title'];
                        }
                        else 
                        {
                            $val['item_title'] = '文章 #' . $val['item_id'];
                        }

                        break;
                    }
                    case 'topic' :
                    {
                        $val['item_tag'] = '话题（专题）';
                        $val['item_link'] = 'topic/' . $val['item_id'];
                        $topic_info = $this->model('topic')->get_topic_by_id($val['item_id']);
                        if($topic_info)
                        {
                            $val['item_title'] = $topic_info['topic_title'];
                        }
                        else
                        {
                            $val['item_title'] = '话题 #' . $val['item_id'];
                        }

                        break;
                    }
                }

                $recommend_homepage_list[$key] = $val;
            }
        }

        TPL::assign('recommend_homepage_list', $recommend_homepage_list);

        TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
            'base_url' => get_js_url('/admin/recommend/list/') . implode('__', $url_param),
            'total_rows' => $total_rows,
            'per_page' => $this->per_page
        ))->create_links());

        $this->crumb(AWS_APP::lang()->_t('首页精选管理'), 'admin/recommend/list/');

        TPL::output('admin/recommend/list');
    }
}
