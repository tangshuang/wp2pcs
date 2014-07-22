<?php

// 经过判断或函数运算才能进行定义的常量
define('WP2PCS_BAIDU_APP_TOKEN',get_option('wp2pcs_baidu_app_token'));
define('WP2PCS_SITE_DOMAIN',$_SERVER['HTTP_HOST']);
define('WP2PCS_REMOTE_ROOT_PATH','/apps/wp2pcs/'.WP2PCS_SITE_DOMAIN);
define('WP2PCS_REMOTE_BACKUP_PATH',WP2PCS_REMOTE_ROOT_PATH.'/backup');
define('WP2PCS_TMP_PATH',get_real_path(ABSPATH.'/wp2pcs_tmp'));

if(!defined('CAN_WRITE'))define('CAN_WRITE',is_really_writable(WP2PCS_TMP_PATH));
if(!defined('IS_WIN'))define('IS_WIN',strpos(PHP_OS,'WIN')!==false);

// 当你发现自己错过了很多定时任务时，可以帮助你执行没有执行完的定时任务
//if(is_admin())define('ALTERNATE_WP_CRON',true);
