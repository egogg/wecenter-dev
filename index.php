<?php

if (! file_exists(dirname(__FILE__) . '/system/config/database.php') AND ! file_exists(dirname(__FILE__) . '/system/config/install.lock.php') AND !defined('SAE_TMP_PATH'))
{
	header('Location: ./install/');
	exit;
}

include('system/system.php');

AWS_APP::run();