<div class="wrap">

<h2 class="nav-tab-wrapper">
  <a href="javascript:void(0)" class="nav-tab nav-tab-active">验证</a>
  <a href="<?php echo add_query_arg('tab','outlink',menu_page_url('wp2pcs-advance',false)); ?>" class="nav-tab">外链</a>
  <a href="<?php echo add_query_arg('tab','video',menu_page_url('wp2pcs-advance',false)); ?>" class="nav-tab">视频</a>
  <small style="font-size:12px;float:right;">付费用户如遇问题请发邮件到<code>476206120@qq.com</code>。不支持qq（群）提问。</small>
</h2>

<div class="metabox-holder"><div class="meta-box-sortables">

<?php include('tpl/advance-setup.php'); ?>

<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">开启站点服务</h3>
  <div class="inside">
    <h4>第一步 成为用户</h4>
    <p>注册成为<a href="http://www.wp2pcs.com" target="_blank">WP2PCS官网</a>用户，点击<a href="http://www.wp2pcs.com/wp-admin/admin.php?page=work-manager-service" target="_blank">这里</a>进入站长平台。</p>
    <h4>第二步 申请和付费</h4>
    <p>在上一步“WP2PCS服务-站长平台”，根据提示申请对应的服务（付费会员或单站点服务，可在下方了解），并根据提示付费，等待人工审核。</p>
    <h4>第三步 使用付费功能</h4>
    <p>在审核通过之后，获取“站点码”，填写在下方。</p>
    <p>当前站点：<input type="text" value="<?php echo WP2PCS_SITE_URL; ?>" class="regular-text" readonly></p>
    <form method="post" autocomplete="off">
    <p>
      站点码：<input type="password" name="wp2pcs_site_code" value="<?php echo $wp2pcs_site_code; ?>">
      <button type="submit" class="button-primary">确定</button>
      <a href="http://www.wp2pcs.com/wp-admin/admin.php?page=work-manager-service" target="_blank" class="button">获取站点码</a>
      <input type="hidden" name="action" value="update-site-code">
      <?php wp_nonce_field(); ?>
    </p>
    </form>
    <?php if($wp2pcs_site_id) { ?>
    <p>
      站点号：<?php echo $wp2pcs_site_id; ?>
      到期时间：<?php echo $wp2pcs_site_expire; ?>
      <strong><?php if(date('Y-m-d H:i:s') > $wp2pcs_site_expire) echo '已到期';else echo '已成功开启'; ?></strong>
    </p>
    <?php }else{ ?>
    <p style="color:red">当前尚未通过站点验证。</p>
    <?php } ?>
    <p>更多使用说明和技巧，点击<a href="http://www.wp2pcs.com/?cat=3" target="_blank">这里</a>了解。下列付费服务可到<a href="http://www.wp2pcs.com/?page_id=730" target="_blank">这里</a>了解。</p>
  </div>
</div>

<div class="postbox closed">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">付费会员</h3>
  <div class="inside">
    <p>没必要列出一大堆，就这几个核心权利。</p>
    <ol>
      <li>论坛<a href="http://www.utubon.com/bbs/forum.php?mod=forumdisplay&fid=37" target="_blank">专享板块</a>权限，可获得更多WP2PCS资源。</li>
      <li>可以获得我的一对一服务。</li>
      <li>可以获得外链服务。</li>
      <li>可以获得视频m3u8服务（仅限视频数量和流量一般的站点）。</li>
      <li>可以获得今后我开发的基于WP2PCS的付费会员专享功能。</li>
    </ol>
    <p>以下不包含在付费服务中：</p>
    <ol>
      <li>简单的操作和使用。</li>
      <li>超出WP2PCS本身的功能。</li>
      <li>开发功能。</li>
    </ol>
    <p>会员费用108元/年，可免费获20个站点外链服务。</p>
  </div>
</div>
<div class="postbox closed">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">单站点</h3>
  <div class="inside">
    <p>付费会员拥有20个免费的站点申请外链的资格，但你可能不想要那么多，就只需要一个或两个站点使用外链服务。</p>
    <p>每个站点20元/年，请发送邮件到476206120@qq.com沟通申请。</p>
  </div>
</div>
<div class="postbox closed">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">自助外链服务</h3>
  <div class="inside">
    <p>对于一些站长而言，不想在网站内安装插件。自助外链服务类似于图床，利用wp2pcs官方服务，为你提供稳定的图片、音乐、视频外链。</p>
    <p>服务费用79元/年。</p>
  </div>
</div>
<div class="postbox closed">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">去除版权</h3>
  <div class="inside">
    <p>WP2PCS的基本功能是免费提供使用的，在网页源代码底部增加了一个HTML注释的版权信息。如果你想去除，请支付版权费用。</p>
    <p>版权费用29元，终身受用，不限制子域名。</p>
  </div>
</div>


</div></div><!-- // -->

</div>
