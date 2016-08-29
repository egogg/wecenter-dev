<?php

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
    die;
}

class ajax_slide extends AWS_ADMIN_CONTROLLER
{
    public function setup()
    {
        HTTP::no_cache_header();

        if (!$this->user_info['permission']['is_administortar'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有访问权限, 请重新登录')));
        }
    }

    public function save_slide_action()
    {
        if (!$_POST['title'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写幻灯片标题')));
        }

        if (!$_POST['category'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择幻灯片分类')));
        }

        if ($_POST['id'])
        {
            $slide_info = $this->model('slide')->get_slide_by_id($_POST['id']);

            if (!$slide_info)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定幻灯片不存在')));
            }
        }

        if ($slide_info)
        {
            $this->model('slide')->save_slide($slide_info['id'], $_POST['title'], $_POST['description'], $_POST['link'], $_POST['category']);

            $id = $slide_info['id'];
        }
        else
        {
            $id = $this->model('slide')->save_slide(null, $_POST['title'], $_POST['description'], $_POST['link']);

            if (!$id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('保存失败')));
            }
        }

        if ($_FILES['img']['name'])
        {
            AWS_APP::upload()->initialize(array(
                'allowed_types' => 'jpg,jpeg,png,gif',
                'upload_path' => get_setting('upload_dir') . '/slide',
                'is_image' => TRUE
            ))->do_upload('img');


            if (AWS_APP::upload()->get_error())
            {
                switch (AWS_APP::upload()->get_error())
                {
                    default:
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误代码') . ': ' . AWS_APP::upload()->get_error()));

                        break;

                    case 'upload_invalid_filetype':
                        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('文件类型无效')));

                        break;
                }
            }

            $upload_data = AWS_APP::upload()->data();

            if (!$upload_data)
            {
                H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('上传失败, 请与管理员联系')));
            }

            foreach (AWS_APP::config()->get('image')->slide_thumbnail as $key => $val)
            {
                $thumb_file[$key] = $upload_data['file_path'] . $id . "-" . $key . '.jpg';

                AWS_APP::image()->initialize(array(
                    'quality' => 90,
                    'source_image' => $upload_data['full_path'],
                    'new_image' => $thumb_file[$key],
                    'width' => $val['w'],
                    'height' => $val['h']
                ))->resize();
            }

            @unlink($upload_data['full_path']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/admin/slide/list/')
        ), 1, null));
    }

    public function save_slide_sort_action()
    {
        if (!$_POST['order'] OR !is_array($_POST['order']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
        }

        foreach ($_POST['order'] AS $id => $order)
        {
            $this->model('slide')->set_slide_sort($id, $order);
        }

        H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('幻灯片排序已自动保存')));
    }

    public function remove_slide_action()
    {
        if (!$this->model('slide')->remove_slide($_POST['id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('删除幻灯片失败')));
        }

        H::ajax_json_output(AWS_APP::RSM(null, 1, null));
    }
}
