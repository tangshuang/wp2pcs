<?php

// 先获取文件的相对路径
$path = null;
if(get_option('permalink_structure')) {
  $URI = rawurldecode($_SERVER['REQUEST_URI']);
  $pos = strpos($URI,'?');
  if($pos !== false) {
    $URI = substr($URI,0,$pos);
  }
  $pos = strpos($URI,'/wp2pcs/');
  if($pos === 0) {
    $path = substr($URI,$pos+7);
  }
}
$GET = isset($_GET['wp2pcs']) ? rawurldecode($_GET['wp2pcs']) : false;
$path = !$path && $GET ? $GET : $path;

// 如果这些路径都是无效的话，就不往下执行
if(!$path) return;
elseif($path == '/') return;
elseif(strpos($path,'.') === false) return;

$file_ext = strtolower(substr($path,strrpos($path,'.')+1));
$file_name = substr($path,strrpos($path,'/')+1);
// 格式包含哪些
$video_exts = array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','rm','rmvb','qt','ogv','webm');
$image_exts = array('jpg','jpeg','png','gif','bmp');
$audio_exts = array('mp3','ogg','wma','wav','mp3pro','mid','midi');

// 浏览器缓存
wp2pcs_http_cache();

// 先检查文件是否存在
$access_token = WP2PCS_BAIDU_ACCESS_TOKEN;
$path = WP2PCS_BAIDUPCS_REMOTE_ROOT.str_replace('//','/','/load/'.$path);
$meta = get_by_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
$meta = json_decode($meta);
// 如果该access_token无法正确获取权限
if(isset($meta->error_code) && in_array($meta->error_code,array(100,110,111,31023))) {
  $refresh_token = get_option('wp2pcs_baidupcs_refresh_token');
  $data = get_by_curl(WP2PCS_API_URL.'/client-baidu-refresh-token.php',array('refresh_token' => $refresh_token));
  $data = json_decode($data);
  if(isset($data->access_token) && isset($data->refresh_token)) {
    update_option('wp2pcs_baidupcs_access_token',$data->access_token);
    update_option('wp2pcs_baidupcs_refresh_token',$data->refresh_token);
    $access_token = $data->access_token;
    // 用新的token获取文件信息
    $meta = get_by_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
    $meta = json_decode($meta);
  }
  else {
    do_action('wp2pcs_load_file_error',$path,$meta);
    wp_die($meta->error_code.': '.$meta->error_msg);
  }
}
// 如果文件不存在，就试图从共享目录中抓取文件
if(isset($meta->error_code) && $meta->error_code == 31066) {
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load/','/apps/wp2pcs/share/',$path);
  $meta = get_by_curl("https://pcs.baidu.com/rest/2.0/pcs/file?method=meta&access_token=$access_token&path=".rawurlencode($path));
  $meta = json_decode($meta);
}
// 如果抓取错误
if(isset($meta->error_msg)){
  do_action('wp2pcs_load_file_error',$path,$meta);
  wp_die($meta->error_code.': '.$meta->error_msg);
}

$wp2pcs_cache_count = (int)get_option('WP2PCS_CACHE_'.$path);
$wp2pcs_load_cache = (int)get_option('wp2pcs_load_cache');
$result = null;
// 获取缓存
if($wp2pcs_cache_count >= WP2PCS_CACHE_COUNT && $wp2pcs_load_cache) {
  $result = wp2pcs_get_cache($path);
}

do_action('wp2pcs_load_file_before',$path,$meta);
if(in_array($file_ext,$image_exts)) {
  if(!$result) {
    $src = "https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token=$access_token&path=".rawurlencode($path);
    $result = get_by_curl($src);
  }
  // 如果抓取错误
  $check = json_decode($result);
  if(isset($check->error_msg)) {
    show_msg('ERROR: '.$check->error_code.'. '.$check->error_msg);
  }

  // 如果开启了水印功能
  $image_watermark = get_option('wp2pcs_image_watermark');
  $watermark_ext = strtolower(substr($image_watermark,strrpos($image_watermark,'.')+1));
  if($image_watermark && in_array($watermark_ext,$image_exts)) {
    // 加载图片
    $im = imagecreatefromstring($result);
    // 加载水印
    if($watermark_ext == 'png') {
      $stamp = imagecreatefrompng($image_watermark);
    }
    elseif($watermark_ext == 'jpg' || $watermark_ext == 'jpeg') {
      $stamp = imagecreatefromjpeg($image_watermark);
    }
    elseif($watermark_ext == 'gif') {
      $stamp = imagecreatefromgif($image_watermark);
    }
    elseif($watermark_ext == 'bmp') {
      $stamp = imagecreatefromwbmp($image_watermark);
    }

    // 设置水印图像的外边距，并且获取水印图像的尺寸
    $marge_right = 10;
    $marge_bottom = 10;
    $sx = imagesx($stamp);
    $sy = imagesy($stamp);

    // 以 50% 的透明度合并水印和图像
    imagecopymerge($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp), 50);

    // 将图像保存到文件，并释放内存
    ob_start();
    imagejpeg($im);
    $result = ob_get_contents();
    ob_end_clean();
    imagedestroy($im);
  }

  header('Content-type: image/jpeg');
}
elseif((in_array($file_ext,$video_exts) || in_array($file_ext,$audio_exts))) {
  if(!$result) {
    $src = "https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token=$access_token&path=".rawurlencode($path);
    $result = get_by_curl($src);
  }
  // 如果抓取错误
  $check = json_decode($result);
  if(isset($check->error_msg)) {
    show_msg('ERROR: '.$check->error_code.'. '.$check->error_msg);
  }
  header('Content-Type: application/octet-stream');
  header("Content-Length: $size");
}
else{
  if(!$result) {
    global $BaiduPCS;
    $result = $BaiduPCS->download($path);
  }
  // 如果抓取错误
  $check = json_decode($result);
  if(isset($check->error_msg)) {
    show_msg('ERROR: '.$check->error_code.'. '.$check->error_msg);
  }
  header('Content-Type: application/octet-stream');
  header("Content-Length: $size");
}

ob_clean();
flush();
echo $result;

// 缓存起来
if($wp2pcs_load_cache && !is_admin()) {
  if($wp2pcs_cache_count < WP2PCS_CACHE_COUNT) {
    update_option('WP2PCS_CACHE_'.$path,$wp2pcs_cache_count ++);
  }
  elseif(!wp2pcs_has_cache($path)) {
    wp2pcs_set_cache($path,$result);
  }
}

do_action('wp2pcs_load_file_after',$path,$meta,$result,null,null);
exit();