<?php

register_deactivation_hook(WP2PCS_PLUGIN_NAME,'wp2pcs_uninstall');
function wp2pcs_uninstall(){
  // 关闭定时任务
  if(wp_next_scheduled('wp2pcs_backup_cron_task'))wp_clear_scheduled_hook('wp2pcs_backup_cron_task');
  //if(wp_next_scheduled('wp2pcs_token_cron_task'))wp_clear_scheduled_hook('wp2pcs_token_cron_task');
}