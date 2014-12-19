<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8"/>
<title>WP2PCS视频播放</title>
<?php
// 判断来路，如果不是当前网站，不显示任何内容
$host = $_SERVER["HTTP_HOST"];
$host = strpos($host,':') === false ? $host : substr($host,0,strpos($host,':'));
$referer = isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;
if($referer && strpos($_SERVER["HTTP_REFERER"],$host) !== false) {
?>
<base href="http://pan.baidu.com"/>
<link href="/res/static/thirdparty/guanjia/css/guanjia_video_all.css?t=201412054621" rel="stylesheet"/>
</head>
<body>
<div class="guanjia_panl" id="guanjia_panl"></div>
<script src="/res/static/thirdparty/guanjia/js/guanjia_video_all.js?t=201412054621" type="text/javascript"></script>
<script src="/res/static/thirdparty/flashvideo/js/cyberplayer.min.js" type="text/javascript"></script>
<script type="text/javascript">/*<![CDATA[*/$(document).ready(function(){var D=function(){disk.ui.VideoFlash.prototype.getVideoPath=function(){return"/api/streaming?path="+disk.getParam("path")+"&type=M3U8_AUTO_480";};C=decodeURIComponent(C);var A=disk.ui.VideoFlash.obtain(),_={path:C,target:"guanjia_panl",type:2,md5:E,isGuanJia:true,onSeek:function(){},onTime:function(){}};A.play(_);disk.ui.VideoFlash.getStorageItem(E,function(_){if(disk.ui.VideoFlash.flashPlayer){disk.ui.VideoFlash.flashPlayer.seek(_);}});disk.ui.GuanJiaVideo.installFuncTips();if(disk.DEBUG){}},C=disk.getParam("path"),E=disk.getParam("md5")||"",B=$("#guanjia_panl"),_=function(){B.html('<div class="nofile">\u6587\u4ef6\u52a0\u8f7d\u5931\u8d25</div>');};if(!C){_();return;}if(parseInt(disk.getParam("safebox"),10)===1){D();$.get("/api/streaming?path="+disk.getParam("path")+"&type=M3U8_AUTO_480",function(_){try{_=$.parseJSON(_);if(_.errno===27){try{BDHScript.throwEvent("LockSafebox",{lock:1});}catch(A){}}else{}}catch(A){}});}else{C=decodeURIComponent(C);var F=disk.ui.VideoFlash.obtain(),A={path:C,target:"guanjia_panl",type:2,md5:E,isGuanJia:true,onSeek:function(){},onTime:function(){}};F.play(A);disk.ui.VideoFlash.getStorageItem(E,function(_){if(disk.ui.VideoFlash.flashPlayer){disk.ui.VideoFlash.flashPlayer.seek(_);}});disk.ui.GuanJiaVideo.installFuncTips();if(disk.DEBUG){}}});/*]]>*/</script>
<script>
jQuery(function($){
  $('.video-functions-tips').remove();
  $(document).bind("contextmenu",function(e){
    return false;   
  });
});
</script>
<?php }else{ ?>
</head>
<body>
<?php } ?>
</body>
</html>