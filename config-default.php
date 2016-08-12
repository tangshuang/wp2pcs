<?php

// 本地
define('WP2PCS_TEMP_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'temp.dir');
define('WP2PCS_CACHE_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'cache.dir');
define('WP2PCS_CACHE_COUNT',20);// 某一个附件被访问N次后缓存在本地
define('WP2PCS_SITE_URL',substr(home_url(),strpos(home_url(),'://')+3));// 当前站点的地址，仅指http://后面的部分，不包含http://或https://
//define('ALTERNATE_WP_CRON',true);// 当你发现自己错过了很多定时任务时，可以帮助你执行没有执行完的定时任务

// 百度云
define('WP2PCS_BAIDU_ACCESS_TOKEN',get_option('wp2pcs_baidu_access_token'));
define('WP2PCS_BAIDUPCS_REMOTE_ROOT','/apps/wp2pcs/'.WP2PCS_SITE_URL);

// 腾讯及微云
define('WP2PCS_TENCENT_APP_ID',get_option('wp2pcs_tencent_app_id'));
define('WP2PCS_TENCENT_OPEN_ID',get_option('wp2pcs_tencent_open_id'));
define('WP2PCS_TENCENT_ACCESS_TOKEN',get_option('wp2pcs_tencent_access_token'));
define('WP2PCS_WEIYUN_REMOTE_ROOT','/wp2pcs/'.WP2PCS_SITE_URL);

// 服务端
define('WP2PCS_API_URL','http://api.wp2pcs.com');

// 应用端
define('WP2PCS_APP_URL','http://wp2pcs.duapp.com');