<?php

if (!defined('IN_ANWSION'))
{
    die;
}

class sitemap extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        if (!$this->user_info['permission']['is_administortar'])
        {
            H::redirect_msg(AWS_APP::lang()->_t('你没有访问权限, 请重新登录'));
        }

        @set_time_limit(0);
    }

    public function init_action()
    {
        $param = '';
        if($_GET['reset'])
        {
            $param = 'reset-1';
        }

        H::redirect_msg(AWS_APP::lang()->_t('正在生成sitemap，可能需要较长时间，请不要刷新或关闭浏览器，耐心等候...'), '/admin/sitemap/generate_sitemap/' . $param);
    }

    public function generate_sitemap_action()
    {
        $return_url = '/admin/settings/category-sitemap';

        switch ($this->model('sitemap')->generate_sitemap_all($_GET['reset'])) 
        {
            case sitemap_class::SITEMAP_SUCCESS:
            {
                H::redirect_msg(AWS_APP::lang()->_t('sitemap生成成功，请等待系统自动返回...'),$return_url);

                break;
            }
            case sitemap_class::SITEMAP_DIR_NOT_EXIST:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：sitemap根目录不存在'), $return_url);

                break;
            }
            case sitemap_class::SITEMAP_DIR_NOT_WRITABLE:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：目录%s不可写，请联系后台管理员进行设置', $sitemap_dir),$return_url);

                break;
            }
            case sitemap_class::SITEMAP_DIR_NOT_EXIST_M:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：sitemap根目录（移动版）不存在'), $return_url);

                break;
            }
            case sitemap_class::SITEMAP_DIR_NOT_WRITABLE_M:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：目录%s不可写，请联系后台管理员进行设置', $sitemap_dir_m),$return_url);

                break;
            }
            case sitemap_class::SITEMAP_BASE_URL_NULL:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：网站根链接不能为空'), $return_url);

                break;
            }
            case sitemap_class::SITEMAP_BASE_URL_NULL_M:
            {
                H::redirect_msg(AWS_APP::lang()->_t('错误：网站根链接（移动版）不能为空'),$return_url);

                break;
            }
            
            default:
                break;
        }
    }
}