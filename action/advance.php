<?php

if(isset($_POST['action']) && $_POST['action'] == 'update-site-code') {
  check_admin_referer();
  $site_url = explode('://',home_url());
  $site_scheme = $site_url[0];
  $site_url = $site_url[1];
  $site_code = trim($_POST['wp2pcs_site_code']);
  if(!$site_code) {
    delete_option('wp2pcs_site_code');
    delete_option('wp2pcs_site_id');
    wp_die('请填写站点码。<a href="javascript:history.go(-1);">返回</a>');
  }
  $baidu_access_token = WP2PCS_BAIDU_ACCESS_TOKEN;
  $baidu_refresh_token = get_option('wp2pcs_baidu_refresh_token');
  $result = get_by_curl(WP2PCS_API_URL.'/client-submit-site-code.php',array(
    'site_scheme' => $site_scheme,
    'site_url' => $site_url,
    'site_code' => $site_code,
    'baidu_access_token' => $baidu_access_token,
    'baidu_refresh_token' => $baidu_refresh_token
  ),false);
  if(!$result || trim($result) == ''){
    wp_die('没有获取数据，请检查你的网站是否支持curl，或者官网服务器出问题，稍后再试试。<br><a href="javascript:history.go(-1);">返回</a>');
  }
  $data = json_decode($result,true);
  if(is_null($data)) {
    wp_die('获取站点ID错误：'.$result);
  }
  elseif(isset($data['error_code'])) {
    delete_option('wp2pcs_site_id');
    delete_option('wp2pcs_site_code');
    wp_die($data['error_code'].': '.$data['error_msg'].' <br><a href="javascript:history.go(-1);">返回</a>');
  }
  elseif(!isset($data['site_id'])) {
    wp_die('没有获取站点ID，请稍后再试试。 <br><a href="javascript:history.go(-1);">返回</a>');
  }
  elseif(!isset($data['site_code'])) {
    wp_die('没有获取站点码，请先自行检查，如果仍没有解决请联系管理员查明情况。');
  }
  elseif(!isset($data['expire_time'])) {
    wp_die('没有获取有效期，请联系管理员查明情况。');
  }
  update_option('wp2pcs_site_id',$data['site_id']);
  update_option('wp2pcs_site_code',$data['site_code']);
  update_option('wp2pcs_site_expire',$data['expire_time']);
  update_option('wp2pcs_site_expire_update_time',date('Y-m-d H:i:s'));
  wp_redirect(add_query_arg(array('time'=>time()),menu_page_url('wp2pcs-advance',false)));
}
