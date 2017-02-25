<?php

/*
Plugin Name: WP2PCS
Plugin URI: http://www.tangshuang.net/wp2pcs
Description: 本插件帮助网站站长将网站和百度网盘连接，实现网站定时备份到云盘里。
Version: 1.6.1
Author: 否子戈
Author URI: http://www.tangshuang.net
*/

define('WP2PCS_PLUGIN_NAME',__FILE__);
define('WP2PCS_PLUGIN_DIR',dirname(WP2PCS_PLUGIN_NAME));
define('WP2PCS_PLUGIN_URL',plugins_url('',WP2PCS_PLUGIN_NAME));

require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
$plugin_data = get_plugin_data(WP2PCS_PLUGIN_NAME);
define('WP2PCS_PLUGIN_VERSION',$plugin_data['Version']); // can be only used in admin

date_default_timezone_set('PRC');

// 包含一些必备的函数和类，以提供下面使用
require(WP2PCS_PLUGIN_DIR.'/config.php');
require(WP2PCS_PLUGIN_DIR.'/libs/functions.php');

require(WP2PCS_PLUGIN_DIR.'/libs/BaiduPCS_PHP_SDK/BaiduPCS.class.php');
require(WP2PCS_PLUGIN_DIR.'/libs/FileUtil/FileZIP.class.php');
require(WP2PCS_PLUGIN_DIR.'/libs/DatabaseUtil/DBTool.class.php');

global $BaiduPCS;
$BaiduPCS = new BaiduPCS(WP2PCS_BAIDU_ACCESS_TOKEN);

require(WP2PCS_PLUGIN_DIR.'/libs/functions.backup.php');

// 添加hooks
$hook_dir = WP2PCS_PLUGIN_DIR.'/hook';
if(is_dir($hook_dir)) {
	$hook_files = wp2pcs_scandir($hook_dir);
	if(is_array($hook_files) && !empty($hook_files)) foreach($hook_files as $hook_file) {
		if(substr($hook_file,-4) == '.php') include($hook_dir.'/'.$hook_file);
	}
}

require(WP2PCS_PLUGIN_DIR.'/menu.php');
