<?php

// 添加菜单
add_action('admin_menu','wp2pcs_add_admin_menu');
function wp2pcs_add_admin_menu() {
  add_options_page('wp2pcs','WP2PCS','edit_theme_options','wp2pcs','wp2pcs_admin_menu_page');
}

// 菜单显示页面
function wp2pcs_admin_menu_page() {
  $page_name = isset($_GET['tab']) ? $_GET['tab'] : 'general';
  $page_url = admin_url('options-general.php?page=wp2pcs&tab='.$page_name);
  $file = WP2PCS_PLUGIN_DIR."/admin/$page_name.php";
  if(file_exists($file)) include($file);
}

// 初始化
add_action('admin_init','wp2pcs_add_admin_init');
function wp2pcs_add_admin_init() {
  if(wp2pcs_get_url_file_name() != 'options-general.php' || $_GET['page'] != 'wp2pcs') {
    return;
  }
  // 加载脚本
  add_action('admin_enqueue_scripts','wp2pcs_admin_init_scripts');
  // 执行提交动作
  wp2pcs_admin_init_action();
}

function wp2pcs_admin_init_scripts() {
  wp_register_script('wp2pcs_general_script',WP2PCS_PLUGIN_URL.'/assets/javascript.js');
  wp_enqueue_script('wp2pcs_general_script');
}

function wp2pcs_admin_init_action() {
  if(wp2pcs_get_url_file_name() != 'options-general.php' || $_GET['page'] != 'wp2pcs' || !isset($_REQUEST['action'])) {
    return;
  }
  $page_name = isset($_GET['tab']) ? $_GET['tab'] : 'general';
  $page_url = admin_url('options-general.php?page=wp2pcs&tab='.$page_name);
  $file = WP2PCS_PLUGIN_DIR."/action/$page_name.php";
  if(file_exists($file)) include($file);
}