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

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
    die;
}

class ajax_recommend extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
    }

    public function recommend_homepage_remove_action()
    {
        if (!$this->model('recommend')->recommend_homepage_del($_POST['type'], $_POST['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('删除精选失败')));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }

    public function recommend_homepage_batch_remove_action()
    {
        if (!$_POST['recommend_homepage_ids'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请批量选择首页精选项目')));
        }

        foreach ($_POST['recommend_homepage_ids'] AS $id)
        {
            $this->model('recommend')->recommend_homepage_remove_by_id($id);
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
