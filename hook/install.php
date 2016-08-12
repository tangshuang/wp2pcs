<?php

/*
 * 第一次安装和每一次升级都会执行
 */

register_activation_hook(WP2PCS_PLUGIN_NAME,'wp2pcs_install');
function wp2pcs_install(){
  //wp_schedule_event(strtotime('+7 days'),'weekly','wp2pcs_token_cron_task');
  add_option('wp2pcs_install',1);
}

add_action('admin_init','wp2pcs_install_redirect');
function wp2pcs_install_redirect() {
  if(!current_user_can('edit_theme_options')) return;
  // 跳转到关于页面
  if(get_user_meta(get_current_user_id(),'wp2pcs_plugin_version',true) != WP2PCS_PLUGIN_VERSION || get_option('wp2pcs_install')) {
    update_user_meta(get_current_user_id(),'wp2pcs_plugin_version',WP2PCS_PLUGIN_VERSION);
    delete_option('wp2pcs_install');
    wp_redirect(add_query_arg(array('tab'=>'about','time'=>time()),menu_page_url('wp2pcs-setting',false)));
    exit();
  }
  // 首次更新的时候通知WP2PCS官方
  if(get_option('wp2pcs_plugin_version') != WP2PCS_PLUGIN_VERSION) {
    add_action('admin_print_footer_scripts','wp2pcs_install_script_notice');
    update_option('wp2pcs_plugin_version',WP2PCS_PLUGIN_VERSION);
    wp2pcs_install_sendmail();
  }
}

// 只会在第一次安装时邮件通知
function wp2pcs_install_sendmail() {
  if(get_option('wp2pcs_install_sendmail')) return;
  $home_url = home_url();
  $home_url = str_replace('http://','',$home_url);
  $home_url = str_replace('https://','',$home_url);
  $admin_email = get_option('admin_email');
  $message = "网站地址：$home_url \n管理员邮箱：$admin_email \nWP2PCS版本：".WP2PCS_PLUGIN_VERSION;
  $result = wp_mail('frustigor@qq.com',"[WP2PCS]有新网站使用了WP2PCS $home_url",$message);
  if($result) update_option('wp2pcs_install_sendmail',1);
}

// 会在每一次升级时通知
function wp2pcs_install_script_notice() {
  $home_url = home_url();
  $admin_email = get_option('admin_email');
  echo '<script src="'.WP2PCS_API_URL.'/client-install-notice.js.php?home_url='.urlencode($home_url).'&admin_email='.$admin_email.'&version='.WP2PCS_PLUGIN_VERSION.'&.js"></script>'."\n";
}