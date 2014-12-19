<?php

define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','test');
define('DB_CHAR','utf8');

define('WEB_ROOT',realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'));
define('ZIP_EXLUDE',serialize(array(WEB_ROOT.'/dontbackup1',WEB_ROOT.'/dontbackup2')));// 在array中填写要排除备份的路径
define('RUN_RATE',1);// 自动执行的频率，单位天

define('REMOTE_ROOT','/apps/wp2pcs/'.$_SERVER['SERVER_NAME']);// 你把资源的放在网盘里面的哪个目录里，注意末尾不要斜杠
define('BAIDU_TOKEN','');// 百度云的wp2pcs access token
define('TENCE_TOKEN','');// 腾讯云的wp2pcs access token