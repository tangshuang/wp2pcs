<?php

add_action('init','wp2pcs_check_refresh_token');
function wp2pcs_check_refresh_token() {
  $update_time = get_option('wp2pcs_baidu_token_update_time');
  if(time() > $update_time + 2*3600) {
    wp2pcs_refresh_baidu_token();
  }
}

add_action('wp2pcs_hourly_check_cron_task','wp2pcs_refresh_baidu_token');
function wp2pcs_refresh_baidu_token() {
  global $BaiduPCS;
  $refresh_token = get_option('wp2pcs_baidu_refresh_token');

  $meta = json_decode($BaiduPCS->getQuota());
  if(isset($meta->error_code) && in_array($meta->error_code,array(100,110,111,31023))) {
    $data = wp2pcs_curl(WP2PCS_API_URL.'/client-baidu-refresh-token.php',array('refresh_token' => $refresh_token));
    $data = json_decode($data);
    if(isset($data->access_token) && isset($data->refresh_token)) {
      update_option('wp2pcs_baidupcs_access_token',$data->access_token);
      update_option('wp2pcs_baidupcs_refresh_token',$data->refresh_token);
    }
  }

  update_option('wp2pcs_baidu_token_update_time',time());
}
