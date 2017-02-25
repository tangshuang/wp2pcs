<?php

require(dirname(__FILE__).'/config.php');

// 使用curl抓取
function _curl($url,$post = false,$ssl = true,$referer = false,$headers = null){
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

$refresh_token = $_POST['refresh_token'];

$post = array(
  'grant_type' => 'refresh_token',
  'refresh_token' => $refresh_token,
  'client_id' => API_BAIDU_APP_KEY,
  'client_secret' => API_BAIDU_SECRET_KEY,
  'scope' => 'netdisk'
);
$data = _curl('https://openapi.baidu.com/oauth/2.0/token',$post);

ob_clean();
echo $data;
exit;
