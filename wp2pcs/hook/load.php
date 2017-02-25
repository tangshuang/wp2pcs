<?php

$wp2pcs_load_switch = get_option('wp2pcs_load_switch');
if(!$wp2pcs_load_switch) return;

// 先获取文件的相对路径
$path = null;
if(get_option('permalink_structure')) {
  $URI = rawurldecode($_SERVER['REQUEST_URI']);
  $pos = strpos($URI,'?');
  if($pos !== false) {
    $URI = substr($URI,0,$pos);
  }
  $pos = strpos($URI,'/'.WP2PCS_URL_PREFIX.'/');
  if($pos === 0) {
    $path = substr($URI,$pos+7);
  }
}
$GET = isset($_GET[WP2PCS_URL_PREFIX]) ? rawurldecode($_GET[WP2PCS_URL_PREFIX]) : false;
$path = !$path && $GET ? $GET : $path;

// 如果这些路径都是无效的话，就不往下执行
if(!$path) return;
elseif($path == '/') return;
elseif(strpos($path,'.') === false) return;

$file_ext = strtolower(substr($path,strrpos($path,'.')+1));
$file_name = substr($path,strrpos($path,'/')+1);

// 格式包含哪些
$image_exts = array('jpg','jpeg','png','gif','bmp');
if(!in_array($file_ext,$image_exts)) {
  wp_die('仅支持图片格式的资源被调用。');
}

// 浏览器缓存
wp2pcs_http_cache(WP2PCS_CACHE_EXPIRES);

// 先检查文件是否存在
$access_token = WP2PCS_BAIDU_ACCESS_TOKEN;
$path = WP2PCS_BAIDUPCS_REMOTE_ROOT.str_replace('//','/','/load/'.$path);
$meta = wp2pcs_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
$meta = json_decode($meta);
// 如果该access_token无法正确获取权限
if(isset($meta->error_code) && in_array($meta->error_code,array(100,110,111,31023))) {
  $refresh_token = get_option('wp2pcs_baidupcs_refresh_token');
  $data = wp2pcs_curl(WP2PCS_API_URL.'/client-baidu-refresh-token.php',array('refresh_token' => $refresh_token));
  $data = json_decode($data);
  if(isset($data->access_token) && isset($data->refresh_token)) {
    update_option('wp2pcs_baidupcs_access_token',$data->access_token);
    update_option('wp2pcs_baidupcs_refresh_token',$data->refresh_token);
    update_option('wp2pcs_baidu_token_update_time',time());
    $access_token = $data->access_token;
    // 用新的token获取文件信息
    $meta = wp2pcs_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
    $meta = json_decode($meta);
  }
  else {
    do_action('wp2pcs_load_file_error',$path,$meta);
    wp_die($meta->error_code.': '.$meta->error_msg);
  }
}
// 如果文件不存在，就试图从共享目录中抓取文件
if(isset($meta->error_code) && $meta->error_code == 31066) {
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load',WP2PCS_BAIDUPCS_SHARE_ROOT,$path);
  $meta = wp2pcs_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
  $meta = json_decode($meta);
}
// 如果抓取错误
if(isset($meta->error_msg)){
  do_action('wp2pcs_load_file_error',$path,$meta);
  wp_die($meta->error_code.': '.$meta->error_msg);
}

do_action('wp2pcs_load_file_before',$path,$meta);

// 开始抓取
$src = "https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token=$access_token&path=".rawurlencode($path);
$result = wp2pcs_curl($src);
// 如果抓取错误
$check = json_decode($result);
if(isset($check->error_msg)) {
  wp_die('ERROR: '.$check->error_code.'. '.$check->error_msg);
}

header('Content-type: image/jpeg');

ob_clean();
flush();
echo $result;

do_action('wp2pcs_load_file_after',$path,$meta,$result,null,null);
exit();