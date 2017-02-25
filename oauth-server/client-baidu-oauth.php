<?php

error_reporting(0);
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

session_start();

$code = isset($_GET['code']) ? $_GET['code'] : null;
$url = isset($_GET['url']) ? $_GET['url'] : null;

//-------读取配置文件
//require("../wp-load.php");
require(dirname(__FILE__).'/config.php');
require(dirname(__FILE__).'/BaiduLibs/Baidu.php');
$baidu = new Baidu(API_BAIDU_APP_KEY,API_BAIDU_SECRET_KEY,API_BAIDU_OAUTH_CALLBACK, new BaiduCookieStore(API_BAIDU_APP_KEY));

// 第一步，接受从用户网站传过来的授权请求，并构建向百度的授权请求
if($url){
  $_SESSION['url'] = $url;
	$loginUrl = $baidu->getLoginUrl('netdisk','page');
	echo '<!DOCTYPE html>';
	echo '<html>';
	echo '<head>';
	echo '<title>WP2PCS</title>';
	echo '<meta charest="utf-8">';
	echo '<script>window.location.href="'.$loginUrl.'"</script>';
	echo '</head>';
	echo '<body></body>';
	echo '</html>';
	exit;
}
// 第二步，当向百度请求成功之后，回到本页，获得一个code，根据这个code获得我想要的信息，并把这些信息保存到数据库，同时返回给用户的网站
elseif($code) {
  $access_token = $baidu->getAccessToken();// 进行一道加密处理
  $refresh_token = $baidu->getRefreshToken();
  $url = $_SESSION['url'];
  if(strpos($url,'?') === false) {
    $url .= '?';
  }
  else {
    $url .= '&';
  }
  $url .= "action=update_token&access_token=$access_token&refresh_token=$refresh_token&oauth=baidupcs";
  session_unset();
  session_destroy();
  header('Location:'.$url);
}
else {
  echo 'No thing.';
}
