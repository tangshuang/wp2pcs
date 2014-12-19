<?php

/*

这个文件是用来调用资源的，例如你想调用你网盘 
/我的应用数据/wp2pcs/images/1.jpg
那么你只需要使用
yoursiteurl/wp2pcs/?/images/1.jpg
就可以显示这张图片

注意，? 后面不能有其他参数，必须是准确的文件相对 REMOTE_ROOT 的路径
当你开启了重写，还可以通过重写规则，实现直接用yoursiteurl/images/1.jpg访问图片

否子戈 http://www.utubon.com

*/

require("libs/BaiduPCS.class.php");
require("config.php");

$path = null;
$URI = str_replace('+','{plus}',$_SERVER['REQUEST_URI']);
$URI = urldecode($URI);
$URI = str_replace('{plus}','+',$URI);
$path = substr($URI,strpos($URI,'?') + 1);

// 如果URL根本就没得东西，就跳转到wp2pcs官方主页，算是给我们打个小广告吧
if(!$path || $path == '/'){
  header("Location: http://www.wp2pcs.com ");
  exit;
}

// 利用上面拿到的path构建远程目录中的文件
$path = str_replace('//','/',REMOTE_ROOT.'/'.$path);
$file_ext = strtolower(substr($path,strrpos($path,'.')+1));
$file_name = substr($path,strrpos($path,'/')+1);

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
if( in_array($file_ext,array('jpg','jpeg','png','gif','bmp')) ) {
  $result = $BaiduPCS->downloadStream($path);
  $meta = json_decode($result,true);
  if(isset($meta['error_msg'])){
    header("Content-Type: text/html; charset=utf8");
    echo $meta['error_msg'];
    exit;
  }

  header('Content-type: image/jpeg');
}
elseif( in_array($file_ext,array('mp3','ogg','wma','wav','mp3pro','mid','midi')) ) {
  $result = $BaiduPCS->downloadStream($path);
  $meta = json_decode($result,true);
  if(isset($meta['error_msg'])){
    header("Content-Type: text/html; charset=utf8");
    echo $meta['error_msg'];
    exit;
  }

  if($file_ext == 'mp3' || $file_ext == 'mp3pro') header("Content-Type: audio/mpeg");
  elseif($file_ext == 'ogg') header('Content-Type: application/ogg');
  elseif($file_ext == 'wma') header('Content-Type: audio/x-ms-wma');
  elseif($file_ext == 'wav') header('Content-Type: audio/x-wav');
  elseif($file_ext == 'mid' || $file_ext == 'midi') header('Content-Type: audio/midi');
  else header('Content-Type: application/octet-stream');
  header('Content-Length: '.strlen($result));
  header('Content-Disposition: inline; filename="'.$file_name.'"');
  header('Accept-Ranges: bytes');
  header('X-Pad: avoid browser bug');
}
elseif( in_array($file_ext,array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','rm','rmvb','qt')) ) {
  $meta = $BaiduPCS->getMeta($path);
  $meta = json_decode($meta,true);
  if(isset($meta['error_msg'])){
    header("Content-Type: text/html; charset=utf8");
    echo $meta['error_msg'];
    exit;
  }
  $meta = $meta['list'][0];
  $meta = $meta['block_list'];
  $meta = json_decode($meta);
  $meta = $meta[0];
  header("Location: video-player.php?path=".urlencode($path)."&md5=".$meta);
  exit;
}
else{
  $result = $BaiduPCS->download($path);
  $meta = json_decode($result,true);
  if(isset($meta['error_msg'])){
    header("Content-Type: text/html; charset=utf8");
    echo $meta['error_msg'];
    exit;
  }

  header("Content-Type: application/octet-stream");
  header('Content-Disposition:inline;filename="'.$file_name.'"');
  header('Accept-Ranges: bytes');
}

ob_clean();
echo $result;
flush();
exit;