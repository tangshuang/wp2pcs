<?php

// WP2PCS升级后可能存在的一些变化，通过本文件进行调整

// 免费版关闭视频播放器功能
if(!get_option('wp2pcs_site_id')) {
  delete_option('wp2pcs_video_m3u8');
  delete_option('wp2pcs_load_videoplay');
}

// 会员过期改为站点过期
$wp2pcs_vip_expire = get_option('wp2pcs_vip_expire');
if($wp2pcs_vip_expire) {
  update_option('wp2pcs_site_expire',$wp2pcs_vip_expire);
  delete_option('wp2pcs_vip_expire');
}

// wp2pcs_load_videoplay 改为 wp2pcs_video_player
$wp2pcs_load_videoplay = get_option('wp2pcs_load_videoplay');
if($wp2pcs_load_videoplay) {
  update_option('wp2pcs_video_player',$wp2pcs_load_videoplay);
  delete_option('wp2pcs_load_videoplay');
}

// baidupcs 改为 baidu
$wp2pcs_baidupcs_access_token = get_option('wp2pcs_baidupcs_access_token');
if($wp2pcs_baidupcs_access_token) {
  update_option('wp2pcs_baidu_access_token',$wp2pcs_baidupcs_access_token);
  delete_option('wp2pcs_baidupcs_access_token');
}
$wp2pcs_baidupcs_refresh_token = get_option('wp2pcs_baidupcs_refresh_token');
if($wp2pcs_baidupcs_refresh_token) {
  // baidu refresh token直接记录，不再以数组的形式保存
  if(is_array($wp2pcs_baidupcs_refresh_token)) $wp2pcs_baidupcs_refresh_token = $wp2pcs_baidupcs_refresh_token['token'];
  update_option('wp2pcs_baidu_refresh_token',$wp2pcs_baidupcs_refresh_token);
  delete_option('wp2pcs_baidupcs_refresh_token');
}

if(wp_next_scheduled('wp2pcs_token_cron_task')) wp_clear_scheduled_hook('wp2pcs_token_cron_task');
if(wp_next_scheduled('wp_backup_to_pcs_corn_task_database')) wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_database');

// 更新wp2pcs.duapp.com为baidu.com.wp2pcs.com
$wp2pcs_update_post_app_url = get_option('wp2pcs_update_app_url');
if($wp2pcs_update_post_app_url != WP2PCS_APP_URL) {
  global $wpdb;
  $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content,'http://wp2pcs.duapp.com/','".WP2PCS_APP_URL."/');");
  $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content,'http://baidu.com.wp2pcs.com/','".WP2PCS_APP_URL."/');");
  $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content,'http://www.baidu.com.wp2pcs.com/','".WP2PCS_APP_URL."/');");
  $wpdb->query("UPDATE $wpdb->posts SET post_content=REPLACE(post_content,'http://pan.baidu.com.wp2pcs.com/','".WP2PCS_APP_URL."/');");
  update_option('wp2pcs_update_app_url',WP2PCS_APP_URL);
}

if(file_exists(dirname(__FILE__).'/hook/backup.php')) @unlink(dirname(__FILE__).'/hook/backup.php');
if(file_exists(dirname(__FILE__).'/hook/notice.php')) @unlink(dirname(__FILE__).'/hook/notice.php');
if(file_exists(dirname(__FILE__).'/hook/update.php')) @unlink(dirname(__FILE__).'/hook/update.php');