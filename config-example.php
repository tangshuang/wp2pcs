<?php

define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','test');
define('DB_CHAR','utf8');

define('WEB_ROOT',realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'));
define('ZIP_EXLUDE',serialize(array(WEB_ROOT.'/dontbackup1',WEB_ROOT.'/dontbackup2')));// 在array中填写要排除备份的路径
define('RUN_RATE',1);// 自动执行的频率，单位天

define('REMOTE_ROOT',$_SERVER['SERVER_NAME']);// 远程目录，例如你希望文件被备份到 /我的应用数据/wp2pcs/yourdomain/backup/ 目录下的话，这里就要定义为'yourdomain'，我默认是使用$_SERVER['SERVER_NAME']，但你也可以根据自己的情况来修改，而且这个地方还会用到index.php中，作为资源的调用根目录，例如你打算调用 /我的应用数据/wp2pcs/yourdomain/images/1.jpg 这个文件，也是基于这里的配置
define('BAIDU_TOKEN','');// 百度云的wp2pcs access token
define('TENCE_TOKEN','');// 腾讯云的wp2pcs access token