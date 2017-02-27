<div class="wrap">

  <h2 class="nav-tab-wrapper">
    <a href="javascript:void(0)" class="nav-tab nav-tab-active">基本信息</a>
    <a href="<?php echo add_query_arg('tab','backup',$page_url); ?>" class="nav-tab">定时备份</a>
    <a href="<?php echo add_query_arg('tab','load',$page_url); ?>" class="nav-tab">资源调用</a>
  </h2>

  <div class="metabox-holder"><div class="meta-box-sortables">
    <div class="postbox">
      <div class="handlediv" title="点击以切换"><br></div>
      <h3 class="hndle">授权信息</h3>
      <div class="inside">
        <?php
          if(!function_exists('curl_init') || !function_exists('curl_exec')) {
            echo '<p style="color:red">主机对cURL模块支持不完整，请联系主机商咨询。</p>';
          }
          elseif(!WP2PCS_BAIDU_ACCESS_TOKEN) {
            $baidupcs_btn_class = 'button-primary';
          }
          else {
            $baidupcs_btn_class = 'button';
            global $BaiduPCS;
            $quota = json_decode($BaiduPCS->getQuota());
            if(isset($quota->error_code) && in_array($quota->error_code,array(100,110,111,31023))) {
              echo '<p>百度账号授权信息可能已经过期，点击下方按钮更新授权试试。</p>';
              $baidupcs_btn_class = 'button-primary';
            }
            elseif(isset($quota->error_code)){
              echo '<p>获取百度网盘信息错误，'.$quota->error_code.'：'.$quota->error_msg.'。</p>';
              $baidupcs_btn_class = 'button-primary';
            }
            elseif((int)$quota->quota == 0) {
              echo '<p>获取百度网盘信息错误。可能是因为你的主机不支持curl，不建议使用。</p>';
              $baidupcs_btn_class = 'button-primary';
            }
            else{
              echo '<p>当前百度网盘总'.number_format(($quota->quota/(1024*1024)),2).'MB，剩余'.number_format((($quota->quota - $quota->used)/(1024*1024)),2).'MB。授权成功后请尽量不要切换账号授权，不同网盘之间切换会导致附件失效。</p>';
              echo '<p>ACCESS TOKEN: '.WP2PCS_BAIDU_ACCESS_TOKEN.'</p>';
              echo '<p>REFRESH TOKEN: '.WP2PCS_BAIDU_REFRESH_TOKEN.'</p>';
            }
          }
        ?>
        <p>
          <a class="<?php echo $baidupcs_btn_class; ?>" onclick="window.location.href = '<?php echo WP2PCS_API_URL; ?>/client-baidu-oauth.php?url=' + encodeURI(window.location.href);">百度授权</a>
        </p>
      </div>
    </div>

    <div class="postbox">
      <div class="handlediv" title="点击以切换"><br></div>
      <h3 class="hndle">简要说明</h3>
      <div class="inside">
        <p>WP2PCS当前版本：<?php echo WP2PCS_PLUGIN_VERSION; ?></p>
        <p>PHP版本：<?php echo PHP_VERSION; ?>（注：PHP必须支持PDO方式操作MySQL）</p>
        <p>交流QQ群：<a href="http://shang.qq.com/wpa/qunwpa?idkey=97278156f3def92eef226cd5b88d9e7a463e157655650f4800f577472c219786" target="_blank">292172954</a></p>
        <p>作者：<a href="http://weibo.com/hz184" target="_blank">@否子戈</a>，点击<a href="http://www.tangshuang.net/wp2pcs" target="_blank">这里</a>获得支持</p>
        <p>向作者捐赠：支付宝<code>476206120@qq.com</code>，财付通<code>476206120</code>，只有你的支持，才能维持WP2PCS继续正常使用。</p>
      </div>
    </div>
  </div></div><!-- // -->

</div>
