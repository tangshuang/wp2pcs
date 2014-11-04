<?php

/*
 * 这个文件是用来调用资源的，例如你想调用你网盘 /我的应用数据/wp2pcs/images/1.jpg，那么你只需要使用yoursiteurl/wp2pcs/?images/1.jpg就可以显示这张图片
 * 当你开启了重写，还可以通过重写规则，实现直接用yoursiteurl/images/1.jpg访问图片
 * 访问视频比较特殊，1.必须使用.m3u8作为视频的访问后缀，如yoursiteurl/wp2pcs/index.php/video/1.mp4.m3u8，2.要播放视频必须使用wp2pcs官方提供的播放器代码，因为百度云规定必须要加入开发者的接口id信息，否则无法播放，播放器代码请到www.wp2pcs.com查找，下次我会把地址写在这里。
 * 注意，不再支持其他参数，例如你不要用yoursiteurl/images/1.jpg?test=1这样的URL来显示图片，如果你一定要这样做，请联系我深入开发
 * 联系 否子戈 http://www.utubon.com
 */

require("libs/BaiduPCS.class.php");

if(!file_exists(dirname(__FILE__).'/config.php')) die('请创建config.php文件');
include("config.php");

$current_uri = urldecode($_SERVER["REQUEST_URI"]);
$uri_arr = explode('/',$current_uri);
$uri_arr = array_values(array_filter($uri_arr));

// 如果是使用默认的URL模式，也就是yoursiteurl/wp2pcs/?images/1.jpg，那么要去掉wp2pcs
if($uri_arr[0] == 'wp2pcs') {
  array_shift($uri_arr);
}

// 把URI连接起来
$path = implode('/',$uri_arr);
////// 访问的形式多种多样，例如重写开启之后可以domain/image/test.jpg，也可能是domain/wp2pcs/?image/test.jpg，也可能是domain/wp2pcs/index.php/video/test.mp4.m3u8，还有可能是domain/wp2pcs/index.php?images/test.jpg等等形式，总之千奇百怪都可能出现。
// 如果开始使用了index.php那么要去掉
if(strpos($post,'index.php') == 0) {
  $path = substr($path,9);
}
// 如果去除了index.php还有/呢
if(strpos($path,'/') == 0) {
  $path = substr($path,1);
}
// 如果是使用默认的URL模式，也就是yoursiteurl/wp2pcs/?images/1.jpg，那么要去掉?，最终得到了文件在远程目录中的路径
if(strpos($path,'?') == 0) {
  $path = substr($path,1);
}

// 如果URL根本就没得东西，就跳转到wp2pcs官方主页，算是给我们打个小广告吧
if(!$path || $path == '/'){
  header("Location: http://www.wp2pcs.com ");
  exit;
}

// 利用上面拿到的path构建远程目录中的文件
$path = '/apps/wp2pcs/'.REMOTE_ROOT.'/'.$path;
// 获取文件的扩展名，根据这个扩展名确定文件是图片还是音乐还是视频
$file_ext = strtolower(substr($path,strrpos($path,'.')+1));

// 缓存啦
header("Cache-Control: private, max-age=10800, pre-check=10800");
header("Pragma: private");
header("Expires: " . date(DATE_RFC822,strtotime(" 10 day")));
if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
  header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'],true,304);
  exit;
}

// 开始打印远程文件的内容
$BaiduPCS = new BaiduPCS(BAIDU_TOKEN);
if($file_ext != 'm3u8'){
  $result = $BaiduPCS->downloadStream($path);
  $meta = json_decode($result,true);
  
  // 如果远程文件抓取过程中出错，打印错误
  if(isset($meta['error_msg'])){
    header("Content-Type: text/html; charset=utf8");
    echo $meta['error_msg'];
    exit;
  }

  // 如果抓取图片
  if(in_array($file_ext,array('jpg','png','gif','bmp'))){
    header('Content-type: image/jpeg');
  }
  // 如果抓取mp3
  else if($file_ext == 'mp3'){
    header("Content-Type: audio/mpeg");
    header('Content-Length: '.strlen($result));
    header('Content-Disposition: inline; filename="'.basename($path).'"');
    header('X-Pad: avoid browser bug');
    header('Cache-Control: no-cache');
  }
  // 如果抓取普通文档
  else{
    header("Content-Type: application/octet-stream");
    header('Content-Disposition:inline;filename="'.basename($path).'"');
    header('Accept-Ranges: bytes');
  }
}
// 如果抓取视频
else{
  $path = substr($path,0,strrpos($path,'.'));
  $result = $BaiduPCS->streaming($path,'M3U8_854_480');
}

ob_clean();
echo $result;
exit;