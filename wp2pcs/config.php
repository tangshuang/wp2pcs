<?php

// 本地
define('WP2PCS_SITE_URL',substr(home_url(),strpos(home_url(),'://')+3));// 当前站点的地址，仅指http://后面的部分，不包含http://或https://
//define('ALTERNATE_WP_CRON',true);// 当你发现自己错过了很多定时任务时，可以帮助你执行没有执行完的定时任务

define('WP2PCS_TEMP_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'temp.dir');
define('WP2PCS_CACHE_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR.'cache.dir');
define('WP2PCS_CACHE_EXPIRES','+30 days');// 附件在浏览器上的缓存过期时间，按照strtotime的写法写
define('WP2PCS_URL_PREFIX','wp2pcs');// 附件调用的时候URL中的判定字符串

// 百度云
define("WP2PCS_BAIDUPCS_REMOTE_DIR", "你的百度PCS应用APP的名称");
define('WP2PCS_BAIDU_ACCESS_TOKEN', get_option('wp2pcs_baidu_access_token'));
define('WP2PCS_BAIDU_REFRESH_TOKEN', get_option('wp2pcs_baidu_refresh_token'));
define('WP2PCS_BAIDUPCS_REMOTE_ROOT','/apps/'.WP2PCS_BAIDUPCS_REMOTE_DIR.'/'.WP2PCS_SITE_URL);
define('WP2PCS_BAIDUPCS_SHARE_ROOT','/apps/'.WP2PCS_BAIDUPCS_REMOTE_DIR.'/share');

// API
define('WP2PCS_API_URL','http://你的授权服务器的域名');

// 外链URL
define('WP2PCS_APP_URL','http://你的外链服务器的域名/路径前缀'); // 需要配合static-server使用
