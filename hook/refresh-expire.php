<?php

add_action('init','wp2pcs_refresh_expire_time');
function wp2pcs_refresh_expire_time() {
  $wp2pcs_site_expire = get_option('wp2pcs_site_expire');
  $wp2pcs_site_expire_update_time = get_option('wp2pcs_site_expire_update_time');
  $wp2pcs_site_id = get_option('wp2pcs_site_id');
  $wp2pcs_site_code = get_option('wp2pcs_site_code');

  // 每隔两个小时检查一次站点是否过期
  if($wp2pcs_site_id && $wp2pcs_site_expire <date('Y-m-d H:i:s') && strtotime($wp2pcs_site_expire_update_time.' +2 hours') < time()) {
    $data = get_by_curl(WP2PCS_API_URL.'/client-get-site-expire-time.php',array('site_id' => $wp2pcs_site_id,'code' => md5($wp2pcs_site_code.date('Y.m.d'))));
    $data = json_decode($data);
    if(isset($data->expire_time)) {
      update_option('wp2pcs_site_expire',$data->expire_time);
    }
    update_option('wp2pcs_site_expire_update_time',date('Y-m-d H:i:s'));
  }
}
