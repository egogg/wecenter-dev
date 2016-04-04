<?php

if (!defined('IN_ANWSION'))
{
    die;
}

class slide_class extends AWS_MODEL
{
    public function get_slide_list($sort = true)
    {
        $sort = ($sort) ? 'order ASC' : 'id ASC';

        $slide_query = $this->fetch_all('slide', null, $sort);

        if (!$slide_query)
        {
            return false;
        }

        foreach ($slide_query AS $slide_info)
        {
            $slide_list[$slide_info['id']] = $slide_info;
        }

        return $slide_list;
    }

    public function get_slide_by_id($id)
    {
        if (!is_digits($id))
        {
            return false;
        }

        static $slide_list;

        if (!$slide_list[$id])
        {
            $slide_list[$id] = $this->fetch_row('slide', 'id = ' . intval($id));
        }

        return $slide_list[$id];
    }

    public function get_slide_by_link($link)
    {
        return $this->fetch_row('slide', 'link = "' . $this->quote($link) . '"');
    }

    public function sort_list($data_raw)
    {
        if (!$data_raw OR !is_array($data_raw))
        {
            return false;
        }

        foreach ($data_raw as $key => $data_info)
        {
            $order[$key] = $data_info['order'];

            $add_time[$key] = $data_info['add_time'];
        }

        array_multisort($order, SORT_ASC, SORT_NUMERIC, $update_time, SORT_DESC, SORT_NUMERIC, $data_raw);

        return $data_raw;
    }

    public function save_slide($id = null, $title, $description = null, $link = null)
    {
        if (isset($id) AND !is_digits($id))
        {
            return false;
        }

        $slide_info = array(
            'title' => htmlspecialchars($title),
            'description' => $description,
            'link' => $link,
            'add_time' => time(),
        );

        if ($id)
        {
            return $this->update('slide', $slide_info, 'id = ' . $id);
        }
        else
        {
            return $this->insert('slide', $slide_info);
        }
    }

    public function remove_slide($id)
    {
        if (!is_digits($id) OR !$this->delete('slide', 'id = ' . $id))
        {
            return false;
        }

        @unlink(get_setting('upload_dir') . '/slide/' . $id . '-max.jpg');

        @unlink(get_setting('upload_dir') . '/slide/' . $id . '-min.jpg');

        return true;
    }

    public function set_slide_sort($id, $order)
    {
        if (!is_digits($id) OR !is_digits($order) OR $order < 0 OR $order > 99)
        {
            return false;
        }

        return $this->update('slide', array(
            'order' => intval($order)
        ), 'id = ' . intval($id));
    }

    public function get_frontend_slides()
    {
        return $this->fetch_all('slide', '`order` > 0', 'order ASC');
    }
}
