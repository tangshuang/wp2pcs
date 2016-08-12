<?php

add_action('init','wp2pcs_refresh_baidu_token');
function wp2pcs_refresh_baidu_token() {
  global $BaiduPCS;
  $refresh_token = get_option('wp2pcs_baidu_refresh_token');
  $wp2pcs_baidu_token_update_time = get_option('wp2pcs_baidu_token_update_time');

  // 每隔两个小时检查一次百度账号是否授权过期
  if(strtotime($wp2pcs_baidu_token_update_time.' +2 hours') < time()) {
    update_option('wp2pcs_baidu_token_update_time',date('Y-m-d H:i:s'));
    $meta = json_decode($BaiduPCS->getQuota());
    if(isset($meta->error_code) && in_array($meta->error_code,array(100,110,111,31023))) {
      $data = get_by_curl(WP2PCS_API_URL.'/client-baidu-refresh-token.php',array('refresh_token' => $refresh_token));
      $data = json_decode($data);
      if(isset($data->access_token) && isset($data->refresh_token)) {
        update_option('wp2pcs_baidupcs_access_token',$data->access_token);
        update_option('wp2pcs_baidupcs_refresh_token',$data->refresh_token);
      }
    }
  }
}

add_action('wp_footer','wp2pcs_footer_copyright',-10);
function wp2pcs_footer_copyright() {
  echo '<!-- 本站由WP2PCS驱动，自动备份网站到云盘，调用云盘资源 http://www.wp2pcs.com -->'."\n";
}
