<?php

$config[] = array(
    'title' => AWS_APP::lang()->_t('自定义'),
    'cname' => 'nao',
    'children' => array(
        array(
            'id' => 601,
            'title' => AWS_APP::lang()->_t('幻灯片管理'),
            'url' => 'admin/slide/list/',
        ),
        array(
        	'id' => 602,
            'title' => AWS_APP::lang()->_t('难度及积分管理'),
            'url' => 'admin/integral/edit/',
        ),
        array(
            'id' => 603,
            'title' => AWS_APP::lang()->_t('首页精选管理'),
            'url' => 'admin/recommend/list/',
        )
    )
);