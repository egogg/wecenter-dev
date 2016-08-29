<?php

if (!defined('IN_ANWSION'))
{
    die;
}

class slide extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('幻灯片管理'), "admin/slide/list/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(601));
    }

    public function list_action()
    {
        $slide_list = $this->model('slide')->get_slide_list();
        foreach ($slide_list as $key => $value) 
        {
            $slide_list[$key]['category_info'] = $this->model('slide')->get_slide_category_info($value['category']);
        }

        TPL::assign('slide_list', $slide_list);

        TPL::output('admin/slide/list');
    }

    public function edit_action()
    {
        if ($_GET['id'])
        {
            $slide_info = $this->model('slide')->get_slide_by_id($_GET['id']);

            if (!$slide_info)
            {
                H::redirect_msg(AWS_APP::lang()->_t('指定幻灯片不存在'), '/admin/slide/list/');
            }

            TPL::assign('slide_info', $slide_info);
        }

        TPL::output('admin/slide/edit');
    }
}
