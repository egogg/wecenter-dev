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

class integral extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        $this->crumb(AWS_APP::lang()->_t('问题难度及积分管理'), "admin/integral/edit/");

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'), '/');
        }

        TPL::assign('menu_list', $this->model('admin')->fetch_menu_list(602));
    }

    public function edit_action()
    {
        TPL::assign('setting', get_setting(null, false));
        TPL::output('admin/integral/edit');
    }
}
