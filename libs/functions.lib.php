<?php

/*
 * 利用原生的PHP和WP函数写的相关函数
 */

// 兼容的scandir
function wp2pcs_scandir($dir) {
  if(function_exists('scandir')) {
    return scandir($dir);
  }
  else {
    $handle = @opendir($dir);
    $arr = array();
    while(($arr[] = @readdir($handle)) !== false) {}
    @closedir($handle);
    $arr = array_filter($arr);
    return $arr;
  }
}


// 读取http缓存
function wp2pcs_http_cache() {
  header("Cache-Control: private, max-age=10800, pre-check=10800");
  header("Pragma: private");
  header("Expires: " . date(DATE_RFC822,strtotime("+10 days")));
  if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
    header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'],true,304);
    exit;
  }
}

// 判断cache是否存在
function wp2pcs_has_cache($path) {
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT,'',$path);
  if(!file_exists(WP2PCS_CACHE_DIR.DIRECTORY_SEPARATOR.$path)) {// 该缓存文件不存在
    return false;
  }
  return true;
}
// 获取cache
function wp2pcs_get_cache($path) {
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT,'',$path);
  $cache_file = realpath(WP2PCS_CACHE_DIR.DIRECTORY_SEPARATOR.$path);
  if(!$cache_file) {// 该缓存文件不存在
    return null;
  }
  $handle = fopen($cache_file,'rb');
  $content = '';
  while(!feof($handle)){
    $content .= fread($handle, 1024*8);
  }
  fclose($handle);
  return $content;
}

// 添加cache
function wp2pcs_set_cache($path,$content) {
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT,'',$path);
  $path = str_replace('/apps/wp2pcs','',$path);
  $cache_file = WP2PCS_CACHE_DIR.'/'.$path;
  if(DIRECTORY_SEPARATOR == '\\') {
    $cache_file = str_replace('/','\\',$cache_file);
    $cache_file = str_replace('\\\\','\\',$cache_file);
  }
  else {
    $cache_file = str_replace('//','/',$cache_file);
  }
  // 创建目录层级
  $pathdir = array();
  $pathdir[] = $dir = dirname($cache_file);
  while($dir && $dir != '/' && $dir != DIRECTORY_SEPARATOR && $dir != '.' && strpos(realpath(ABSPATH),$dir) === false) {
	  $pathdir[] = $dir = dirname($dir);
	}
	@end($pathdir);
	do{
		$dir = @current($pathdir);
		if(!file_exists($dir)) @mkdir($dir);
	} while(@prev($pathdir));
	// 写入文件
  $handle = fopen($cache_file,'wb');
  fwrite($handle,$content);
  fclose($handle);
  return $path;
}

// 删除一个缓存
function wp2pcs_delete_cache($path) {
  global $wpdb;
  $path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT,'',$path);
  $cache_file = realpath(WP2PCS_CACHE_DIR.DIRECTORY_SEPARATOR.$path);
  if(!$cache_file) {
    return null;
  }
  unlink($cache_file);
  delete_option('WP2PCS_CACHE_'.$path);
  return true;
}

// 清空缓存
function wp2pcs_clean_cache($dir = WP2PCS_CACHE_DIR) {
  global $wpdb;
  $dir = realpath($dir);
  $handle = opendir($dir);
  while($file = readdir($handle)) {
    if($file == '.' || $file == '..')continue;
    if(is_dir($dir.DIRECTORY_SEPARATOR.$file)) {
      wp2pcs_clean_cache($dir.DIRECTORY_SEPARATOR.$file);
    }
    else {
      unlink($dir.DIRECTORY_SEPARATOR.$file);
    }
  }
  closedir($handle);
  $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'WP2PCS_CACHE_%'");
  return true;
}


// 使用get_by_curl来执行curl抓取
if(!function_exists('get_by_curl')) :
function get_by_curl($url,$post = false,$ssl = true,$referer = false,$headers = null){
  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//安全模式下无法使用
  if($referer) {
    curl_setopt ($ch,CURLOPT_REFERER,$referer);
  }
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if($ssl) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  }
  if($post){
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
  }
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}
endif;

// 获取当前访问的URL的文件名
if(!function_exists('get_url_file_name')) :
function get_url_file_name() {
  $file_name = substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);
  return $file_name;
}
endif;
