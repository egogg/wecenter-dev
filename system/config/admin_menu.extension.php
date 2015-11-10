<?php

$config[] = array(
    'title' => AWS_APP::lang()->_t('自定义功能'),
    'cname' => 'custom',
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
        )
    )
);