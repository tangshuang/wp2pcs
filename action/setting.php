<?php

if(isset($_GET['access_token']) && !empty($_GET['access_token'])) {
  $access_token = $_GET['access_token'];
  if(isset($_GET['oauth']) && $_GET['oauth'] == 'baidupcs' && isset($_GET['refresh_token']) && !empty($_GET['refresh_token'])) {
    $refresh_token = $_GET['refresh_token'];
    $wp2pcs_site_id = get_option('wp2pcs_site_id');
    $wp2pcs_site_code = get_option('wp2pcs_site_code');
    $wp2pcs_site_expire = get_option('wp2pcs_site_expire');
    update_option('wp2pcs_baidu_access_token',$access_token);
    update_option('wp2pcs_baidu_refresh_token',$refresh_token);
    update_option('wp2pcs_baidu_token_update_time',date('Y-m-d H:i:s'));
    // 更新应用端的数据，这种情况有可能是付费用户切换了百度账号
    if($wp2pcs_site_id && $wp2pcs_site_code && $wp2pcs_site_expire > date('Y-m-d H:i:s')) {
      $result = get_by_curl(WP2PCS_APP_URL.'/api',array(
        'method' => 'update_site_data',
        'site_id' => $wp2pcs_site_id,
        'code' => md5($wp2pcs_site_code.date('Y.m.d')),
        'site_scheme' => array_shift(explode('://',home_url())),
        'baidu_access_token' => $access_token,
        'baidu_refresh_token' => $refresh_token
      ),false);
      // 如果通知app端时返回的数据为空
      if(!$result || trim($result) == '') {
        wp_die('更新远端数据时，远端无反应，可能你的主机不支持curl，或外链可能被损坏，请稍后再试。<a href="javascript:history.go(-1);">返回</a>');
      }
      $data = json_decode($result,true);
      if(is_null($data)) {
        wp_die('更新远端数据时，错误：'.$result);
      }
      elseif(isset($data['error_code'])) {
        wp_die("更新远端数据时，错误： {$data['error_code']}. {$data['error_msg']}");
      }
      elseif(!isset($data['success']) || $data['success'] != 'ok') {
        wp_die('更新远端数据时，没有成功更新数据');
      }
    }
  }
  elseif(isset($_GET['oauth']) && $_GET['oauth'] == 'weiyun' && isset($_GET['open_id']) && !empty($_GET['open_id'])) {
    update_option('wp2pcs_tencent_access_token',$access_token);
    update_option('wp2pcs_tencent_open_id',$_GET['open_id']);
    update_option('wp2pcs_tencent_app_id','101161347');
  }
  wp_redirect(menu_page_url('wp2pcs-setting',false));
}