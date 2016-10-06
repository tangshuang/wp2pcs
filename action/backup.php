<?php

if(isset($_POST['action']) && $_POST['action'] == 'update-backup-setting') {
  check_admin_referer();
  update_option('wp2pcs_backup_file',$_POST['wp2pcs_backup_file']);
  update_option('wp2pcs_backup_data',$_POST['wp2pcs_backup_data']);
  update_option('wp2pcs_backup_time',$_POST['wp2pcs_backup_time']);
  update_option('wp2pcs_backup_path_include',trim($_POST['wp2pcs_backup_path_include']));
  update_option('wp2pcs_backup_path_exclude',trim($_POST['wp2pcs_backup_path_exclude']));
  update_option('wp2pcs_backup_path_must',trim($_POST['wp2pcs_backup_path_must']));
  // 开启定时任务
  if(wp_next_scheduled('wp2pcs_backup_cron_task')) {
    wp_clear_scheduled_hook('wp2pcs_backup_cron_task');
    update_option('wp2pcs_backup_amount',0);
  }
  if($_POST['wp2pcs_backup_file'] != 'never' || $_POST['wp2pcs_backup_data'] != 'never') {
    $run_time = strtotime(date('Y-m-d '.$_POST['wp2pcs_backup_time'].':00').' +1 day');
    // $run_time = strtotime('+1 minute');
    wp_schedule_event($run_time,'daily','wp2pcs_backup_cron_task');
  }
  wp_redirect(add_query_arg(array('tab'=>$page_name,'time'=>time()),$page_url));
}
