<?php

/*
 * 开发 by 否子戈 http://www.utubon.com
 */

require("libs/DbManage.class.php");
require("libs/WebZip.class.php");
require("libs/BaiduPCS.class.php");

if(!file_exists(dirname(__FILE__).'/config.php')) die('请创建config.php文件');
include("config.php");

// 如果目录不可写
if(!is_really_writable(dirname(__FILE__))) {
  die('当前目录不可写，请赋予可写权限。');
}


/*
 * 下面开始自动备份
 */

$run_times = 0;// 用来记录跑了多少次，通过这个变量来修改开关文件名，防止下次访问这个文件的时候再次执行
ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
ini_set("memory_limit","256M");// 提高内存限制
_showMsg('10分钟后自动进入备份程序，你现在可以关掉浏览器了。');
sleep(10*60);// 让程序自动执行，因此最开始不要执行，提示用户关掉浏览器
do{
    // 根据是否存在文件来判断是否继续执行
    $run_file = dirname(__FILE__).DIRECTORY_SEPARATOR.'run.open.'.$run_times;// $run_file是指run.open.x这个开关文件
    if(!file_exists($run_file)) { // 如果开光文件名对应不上，就停止执行，也就是说你可以通过修改开关文件名称来控制是否自动备份
      die('自动备份已经中断');
    }
    // 先执行一次备份
    $zip_file_path = run_backup();
    upload_baidupcs($zip_file_path);// 执行上传到百度云
    _showMsg('上传到百度云完成');
    sleep(30*60);// 过半个小时删除打包的文件，因为上传到百度云可能比较消耗时间，半个小时比较合理
    if(file_exists($zip_file_path))unlink($zip_file_path);// 删除打包文件，因为如果不删除，被别人发现了可以下载你全站的资料
    // 下面对时间进行处理，每晚2点执行一次，因为晚上2点的时候访问网站的人最少，资源消耗比较少
    date_default_timezone_set('PRC');// 把时区控制在中国，if you are not Chinese, change this to your nation
    $nowTime = time();
    if(date('His') >= 20000) { // 如果当前时间大于2点，那么就等到第二天两点再执行
      $doTime = strtotime(date('Y-m-d 02:00:00',strtotime('+'.RUN_RATE.' day')));
    }
    elseif(date('His') < 20000) { // 如果当前时间小于2点，那么下一个2点就马上会执行
      $doTime = strtotime(date('Y-m-d 02:00:00'));
    }
    $nextRunTime = $doTime - $nowTime;
    rename($run_file,str_replace('run.open.'.$run_times,'run.open.'.($run_times+1),$run_file));// 修改开关文件名，下一次执行的时候根据文件名来判定是否继续执行。为什么要修改文件名呢？因为我可以知道被执行了多少次。
    $run_times ++;
    sleep($nextRunTime);
}while(true);


/*
 * 以下是执行函数
 */

// 执行备份
function run_backup() {
  $zip_file_name = 'wp2pcs.'.time().rand(1000,9999).'.zip';
  $zip_file_path = dirname(__FILE__).DIRECTORY_SEPARATOR.$zip_file_name;
  // 备份数据库
  $db = new DBManage(DB_HOST,DB_USER,DB_PASS,DB_NAME,DB_CHAR);
  $db->backup();
  // 备份文件并生成
  $ZIP = new WebZip;
  if($ZIP->startfile($zip_file_path)) {//
    _showMsg('开始备份文件...');
    $totle_files = 0;
    $current_dir = realpath(dirname(__FILE__));
    $webroot_dir = realpath($current_dir.DIRECTORY_SEPARATOR.'..');
    $ZIP->exlude = array_merge(array($current_dir),unserialize(ZIP_EXLUDE));
    $totle_files += $ZIP->process($webroot_dir,$webroot_dir);
    $totle_files += $ZIP->process($current_dir.DIRECTORY_SEPARATOR.'database',$current_dir);
    $ZIP->createfile();
    _showMsg('文件备份完成');
    _showMsg("共备份了{$totle_files}个文件");
    _showMsg('删除备份的数据库文件...');
    remove_dir('./database');
    _showMsg('备份结束');
    /*
    $url = $_SERVER['PHP_SELF'];
    $zip = dirname($url);
    $zip = "$zip/$zip_file_name";
    _showMsg("<a href='$zip'>点击下载</a> <a href='$url?delete=$zip_file_name'>删除</a>");
    */
    return $zip_file_path;
  }
}

// 上传到百度云
function upload_baidupcs($zip_file_path){
  $file_blocks = array();//分片上传文件成功后返回的md5值数组集合
  $file_block_size = 2*1024*1024;// 2M
  $rmote_dir = '/apps/wp2pcs/'.REMOTE_ROOT.'/backup/';
  $file_name = basename($zip_file_path);
  $BaiduPCS = new BaiduPCS(BAIDU_TOKEN);
  // 开始分片上传
  $handle = @fopen($zip_file_path,'rb');
  while(!@feof($handle)){
    $file_block_content = fread($handle,$file_block_size);
    $temp = $BaiduPCS->upload($file_block_content,$remote_dir,$file_name,false,true);
    if(!is_array($temp)){
      $temp = json_decode($temp,true);
    }
    if(isset($temp['md5'])){
      array_push($file_blocks,$temp['md5']);
    }
  }
  fclose($handle);
  //@unlink($zip_file_path);
  if(count($file_blocks) > 1){
    $BaiduPCS->createSuperFile($remote_dir,$file_name,$file_blocks,'');
  }
}

// 递归删除目录
function remove_dir($dir) {
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
  rmdir($dir);
}

//  及时输出信息
function _showMsg($msg,$err=false){
  $err = $err ? "<span class='err'>ERROR:</span>" : '' ;
  echo "<p class='dbDebug'>".$err . $msg."</p>";
  flush();
}

// 通过curl获取
function curl($url,$post = false){
  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if($post){
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
  }
  curl_setopt($ch, CURLOPT_COOKIESESSION, true);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

// 判断目录或文件是否有可写权限
function is_really_writable($file){
  $file = trim($file);
  // WIN，是否开启安全模式
  if(DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == false){
    return is_writable($file);
  }
  // 如果是目录的话
  if(is_dir($file)){
    $file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));
    if(($fp = @fopen($file,'w+')) === false){
      return FALSE;  
    }
    fclose($fp);
    @chmod($file,'0755');
    @unlink($file);
    return true;
  }
  // 如果是不是文件，或文件打不开的话
  elseif(!is_file($file) OR ($fp = @fopen($file,'w+')) === false){
    return false;
  }
  fclose($fp);
  return true;
}
