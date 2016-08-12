<?php

// 创建一个函数获取随机字符串
if(!function_exists('get_rand_string')) {
  function get_rand_string($length) {
    $str = null;
    $strPol = "0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol)-1;

    for($i=0;$i<$length;$i++) {
      $str .= $strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }

    return $str;
  }
}

// 执行备份
function run_backup($backup_file = true,$backup_data = true) {
  if(!$backup_file && !$backup_data) return null;
  global $DBZIP,$FileZIP;
  $zip_file_name = date('Y.m.d-H.i.s').'-'.(int)$backup_file.(int)$backup_data.'-'.get_rand_string(4).'.zip';
  $zip_file_path = WP2PCS_TEMP_DIR.DIRECTORY_SEPARATOR.$zip_file_name;
  $zip_data_path = WP2PCS_TEMP_DIR.DIRECTORY_SEPARATOR.'database-backup';
  $webroot_path = realpath(ABSPATH.'/../');
  remove_dir(WP2PCS_TEMP_DIR,false);// 清空临时目录
  // 备份文件并生成
  if($FileZIP->startfile($zip_file_path)) {
    // 备份数据
    if($backup_data) {
      $DBZIP->backup($zip_data_path,2000);
      $FileZIP->process($zip_data_path,WP2PCS_TEMP_DIR);
      remove_dir($zip_data_path);
    }
    // 备份文件
    if($backup_file) {
      $path_include = stripslashes(get_option('wp2pcs_backup_path_include'));
      $path_exclude = stripslashes(get_option('wp2pcs_backup_path_exclude'));
      $path_must = stripslashes(get_option('wp2pcs_backup_path_must'));
      // 白名单
      if($path_must) {
        $path_must = array_filter(explode("\r\n",$path_must));
        foreach($path_must as $path) {
          if($path) $FileZIP->include_path($path);
        }
      }
      // 排除黑名单
      if($path_exclude) {
        $path_exclude = array_filter(explode("\r\n",$path_exclude));
        foreach($path_exclude as $path) {
          if($path) $FileZIP->exclude_path($path);
        }
      }
      $FileZIP->exclude_path(WP2PCS_TEMP_DIR);
      $FileZIP->exclude_path(WP2PCS_CACHE_DIR);
      // 按照给定的路径进行打包
      if($path_include) {
        $path_include = array_filter(explode("\r\n",$path_include));
        foreach($path_include as $path) {
          $FileZIP->process($path,$webroot_path);
        }
      }
      else {
        $FileZIP->process(realpath(ABSPATH),$webroot_path);
      }
    }

    $FileZIP->createfile();
    return $zip_file_path;
  }
  else {
    return null;
  }
}

// 上传到百度云
function upload_baidupcs($zip_file_path){
  if(!file_exists($zip_file_path)) return;
  global $BaiduPCS;
  $file_blocks = array();//分片上传文件成功后返回的md5值数组集合
  $file_block_size = 2*1024*1024;// 2M
  $remote_dir = WP2PCS_BAIDUPCS_REMOTE_ROOT.'/backup/';
  $file_name = substr($zip_file_path,strrpos($zip_file_path,'/')+1);
  // 使用普通上传
  if(filesize($zip_file_path) <= 20*1024*1024) {
    $file_content = '';
    $handle = @fopen($zip_file_path,'rb');
    while(!@feof($handle)){
      $file_content .= fread($handle,$file_block_size);
    }
    $BaiduPCS->upload($file_content,$remote_dir,$file_name);
    return;
  }
  // 开始分片上传
  $handle = @fopen($zip_file_path,'rb');
  while(!@feof($handle)){
    $file_block_content = fread($handle,$file_block_size);
    $block = $BaiduPCS->upload($file_block_content,$remote_dir,$file_name,false,true);
    if(!is_array($block)){
      $block = json_decode($block,true);
    }
    if(isset($block['md5'])){
      array_push($file_blocks,$block['md5']);
    }
  }
  fclose($handle);
  if(count($file_blocks) > 1){
    $BaiduPCS->createSuperFile($remote_dir,$file_name,$file_blocks,'');
  }
}

// 递归删除目录,$self是指是否要删除文件夹本身
function remove_dir($dir,$self = true) {
  $dir = realpath($dir);
  if(is_file($dir)) {
    unlink($dir);
    return;
  }
  $handle = opendir($dir);
  while($file = readdir($handle)) {
    if($file == '.' || $file == '..')continue;
    if(is_dir($dir.DIRECTORY_SEPARATOR.$file)) {
      remove_dir($dir.DIRECTORY_SEPARATOR.$file);
    }
    else {
      unlink($dir.DIRECTORY_SEPARATOR.$file);
    }
  }
  closedir($handle);
  if($self)rmdir($dir);
}

