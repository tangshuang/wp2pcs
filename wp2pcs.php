<?php

/*
Plugin Name: WP2PCS
Plugin URI: http://www.wp2pcs.com/
Description: 本插件帮助网站站长将网站和百度网盘连接。网站定时备份，调用网盘资源在网站中使用。
Version: 1.5.5
Author: 否子戈
Author URI: http://www.utubon.com
*/

date_default_timezone_set('PRC');
define('WP2PCS_PLUGIN_NAME',__FILE__);
define('WP2PCS_PLUGIN_VERSION','1.5.5');

// 包含一些必备的函数和类，以提供下面使用
if(file_exists(dirname(__FILE__).'/config.php')) include(dirname(__FILE__).'/config.php');
else include(dirname(__FILE__).'/config-default.php');
require(dirname(__FILE__).'/libs/functions.lib.php');
require(dirname(__FILE__).'/libs/BaiduPCS.class.php');
require(dirname(__FILE__).'/libs/File.ZIP.class.php');

// 根据不同的PHP版本，使用不同的数据库第三方类
if(substr(PHP_VERSION, 0, 1) == '7') {
  require(dirname(__FILE__).'/libs/DBTool.class.php');
  $DBZIP = new DatabaseTool(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
}
else {
  require(dirname(__FILE__).'/libs/Database.ZIP.class.php');
  $DBZIP = new DBZIP(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
}

require(dirname(__FILE__).'/libs/functions.backup.php');

// 直接初始化全局变量
$BaiduPCS = new BaiduPCS(WP2PCS_BAIDU_ACCESS_TOKEN);
$FileZIP = new FileZIP;

// 添加菜单
add_action('admin_menu','wp2pcs_add_admin_menu');
function wp2pcs_add_admin_menu() {
  if(is_multisite()) return; // 不允许在多站点开启的情况下使用
  add_menu_page('WordPress连接云盘','WP2PCS','edit_theme_options','wp2pcs','wp2pcs_admin_menu_page','',66);
  add_submenu_page('wp2pcs','WordPress连接云盘','基础设置','edit_theme_options','wp2pcs-setting','wp2pcs_admin_menu_page');
  add_submenu_page('wp2pcs','WP2PCS资源管理','资源查看','edit_theme_options','wp2pcs-media','wp2pcs_admin_menu_page');
  add_submenu_page('wp2pcs','WP2PCS付费功能使用','付费功能','edit_theme_options','wp2pcs-advance','wp2pcs_admin_menu_page');
  remove_submenu_page('wp2pcs','wp2pcs');
}
// 菜单显示页面
function wp2pcs_admin_menu_page() {
  $file = isset($_GET['tab']) && !empty($_GET['tab']) ? $_GET['page'].'-'.$_GET['tab'] : $_GET['page'];
  $file = str_replace('wp2pcs-','',$file);
  $file = dirname(WP2PCS_PLUGIN_NAME)."/admin/$file.php";
  if(file_exists($file)) include($file);
  echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle" style="display:inline-block;width:728px;height:90px;margin:20px 0;" data-ad-client="ca-pub-0625745788201806" data-ad-slot="7099159194"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

// 添加动作
add_action('admin_init','wp2pcs_add_admin_init');
function wp2pcs_add_admin_init() {
  if(is_multisite()) return;
  if(get_url_file_name() != 'admin.php') return;
  if(!in_array($_GET['page'],array('wp2pcs','wp2pcs-setting','wp2pcs-media','wp2pcs-advance'))) return;
  // 加载脚本
  add_action('admin_enqueue_scripts','wp2pcs_admin_init_scripts');
  // 执行提交动作
  wp2pcs_admin_init_action();
}
function wp2pcs_admin_init_scripts() {
  wp_register_script('wp2pcs_general_script',plugins_url('/assets/javascript.js',WP2PCS_PLUGIN_NAME));
  wp_enqueue_script('wp2pcs_general_script');
}
function wp2pcs_admin_init_action() {
  $file = isset($_GET['tab']) && !empty($_GET['tab']) ? $_GET['page'].'-'.$_GET['tab'] : $_GET['page'];
  $file = str_replace('wp2pcs-','',$file);
  $file = dirname(WP2PCS_PLUGIN_NAME)."/action/$file.php";
  if(file_exists($file)) include($file);
}

include(dirname(__FILE__).'/update.php');

// 添加hooks
if(!is_multisite()) {
  $hook_dir = dirname(WP2PCS_PLUGIN_NAME).'/hook';
  if(is_dir($hook_dir)) {
    $hook_files = wp2pcs_scandir($hook_dir);
    if($hook_files){
      foreach($hook_files as $hook_file)
        if(substr($hook_file,-4) == '.php')
          include($hook_dir.'/'.$hook_file);
    }
  }
}