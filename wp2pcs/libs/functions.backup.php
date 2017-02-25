<?php

// 上传到百度云
function wp2pcs_upload_to_baidupcs($local_file,$remote_file){
  if(!file_exists($local_file)) {
    return;
  }
  global $BaiduPCS;
  $file_blocks = array();//分片上传文件成功后返回的md5值数组集合
  $file_block_size = 2*1024*1024;// 2M
  $remote_file = WP2PCS_BAIDUPCS_REMOTE_ROOT.str_replace('//','/','/'.$remote_file);
  // 使用普通上传
  if(filesize($local_file) <= 10*1024*1024) {
    $file_content = '';
    $handle = @fopen($local_file,'rb');
    while(!@feof($handle)){
      $file_content .= fread($handle,$file_block_size);
    }
    $BaiduPCS->upload($file_content,$remote_file,'');
    return;
  }
  // 开始分片上传
  $handle = @fopen($local_file,'rb');
  while(!@feof($handle)){
    $file_block_content = fread($handle,$file_block_size);
    $block = $BaiduPCS->upload($file_block_content,$remote_file,'',false,true);
    if(!is_array($block)){
      $block = json_decode($block,true);
    }
    if(isset($block['md5'])){
      array_push($file_blocks,$block['md5']);
    }
  }
  fclose($handle);
  if(count($file_blocks) > 1){
    $BaiduPCS->createSuperFile($remote_file,'',$file_blocks,'');
  }
}

// 执行备份
function wp2pcs_backup_process($backup_file = true,$backup_data = true) {
  if(!$backup_file && !$backup_data) return null;
  
  $DBZIP = new DatabaseTool(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
  $FileZIP = new FileZIP;
  $zip_file_name = date('Y.m.d-H.i.s').'-'.(int)$backup_file.(int)$backup_data.wp2pcs_rand(8).'.zip';
  $zip_file_path = WP2PCS_TEMP_DIR.DIRECTORY_SEPARATOR.$zip_file_name;
  $zip_data_path = WP2PCS_TEMP_DIR.DIRECTORY_SEPARATOR.'database-backup';
  $webroot_path = realpath(ABSPATH.'/../');
  wp2pcs_rmdir(WP2PCS_TEMP_DIR,false);// 清空临时目录
  // 备份文件并生成
  if($FileZIP->startfile($zip_file_path)) {
    // 备份数据
    if($backup_data) {
      $DBZIP->backup($zip_data_path,2000);
      $FileZIP->process($zip_data_path,WP2PCS_TEMP_DIR);
      wp2pcs_rmdir($zip_data_path);
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

function wp2pcs_backup_to_baidupcs($backup_file = true,$backup_data = true) {
  $zip_file = wp2pcs_backup_process($backup_file,$backup_data);
  $file_name = basename($zip_file);
  wp2pcs_upload_to_baidupcs($zip_file,'/backup/'.$file_name);
  wp2pcs_rmdir(WP2PCS_TEMP_DIR,false);// 清空临时目录
}

function wp2pcs_reccurences(){
  return array(
    'never' => array('interval' => 0, 'display' => '永不备份'),
    'daily' => array('interval' => 3600*24, 'display' => '每天一次'),
    'weekly' => array('interval' => 3600*24*7, 'display' => '每周一次'),
    'monthly' => array('interval' => 3600*24*30, 'display' => '每月一次')
  );
}