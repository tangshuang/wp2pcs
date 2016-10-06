<?php

add_action('admin_init','wp2pcs_install_redirect');
function wp2pcs_install_redirect() {
  if(!current_user_can('edit_theme_options')) {
    return;
  }
  // 每个小时的定时任务，用于刷新token
  if(!wp_next_scheduled('wp2pcs_hourly_check_cron_task')) {
    $run_time = strtotime(date('Y-m-d H:00:00').' +1 hour');
    wp_schedule_event($run_time,'hourly','wp2pcs_hourly_check_cron_task');
  }
  // 跳转到关于页面
  if(get_user_meta(get_current_user_id(),'wp2pcs_plugin_version',true) != WP2PCS_PLUGIN_VERSION) {
    update_user_meta(get_current_user_id(),'wp2pcs_plugin_version',WP2PCS_PLUGIN_VERSION);
    wp_redirect(admin_url('options-general.php?page=wp2pcs&tab=about'));
    exit();
  }
}