<script>
// 防止被iframe，特别是刚刚升级后
if(self != top) {
  top.location.href = self.location.href;
  window.stop ? window.stop() : document.execCommand("Stop");
}
</script>

<style>
.update-about-feature {
  padding:20px 0;
}
.update-about-feature h3 {
  text-align: center;
  margin: 20px 0;
}
.update-about-feature h4 {
  text-align: center;
}
.update-about-feature img {
  max-width: 100%;
  height: auto;
}
.headline-feature {
  text-align: center;
}
.headline-feature h2 {
  margin: 30px 0;
}
.feature-section .col {
  float: left;
}
.feature-section.two-col .col {
  width: 48%;
  margin-right: 4%;
}
.feature-section.three-col .col {
  width: 30%;
  margin-right: 5%;
}
.feature-section .last-col {
  margin-right: 0 !important;
}
.feature-section ul {
  padding-left: 1em;
}
.feature-section ul li {
  list-style: disc;
}
.feature-section small {
  font-size: .9em;
  color: #999;
}
</style>

<div class="wrap">

<h2 class="nav-tab-wrapper">
  <a href="<?php menu_page_url('wp2pcs-setting'); ?>" class="nav-tab">基本信息</a>
  <a href="<?php echo add_query_arg('tab','backup',menu_page_url('wp2pcs-setting',false)); ?>" class="nav-tab">定时备份</a>
  <a href="<?php echo add_query_arg('tab','load',menu_page_url('wp2pcs-setting',false)); ?>" class="nav-tab">资源调用</a>
  <a href="javascript:void(0);" class="nav-tab nav-tab-active">关于</a>
</h2>

<div class="update-about-feature headline-feature">
  <h2>版本1.5.x，更稳定，注重视频调用！</h2>
  <div class="featured-image">
    <img class="about-overview-img" src="<?php echo plugins_url('assets/about.png',WP2PCS_PLUGIN_NAME); ?>" width="640" height="360" />
  </div>
  <div class="clear"></div>
</div>

<hr />

<div class="update-about-feature headline-feature">
  <h2>1.5.5的变化</h2>
  <div class="featured-image">
    <p><strong>修复了PHP7不支持老的mysql_connect的问题</strong></p>
    <p>PHP7不再支持原来老版本的mysql_connect等操作，导致升级PHP版本后wp2pcs无法使用。<br>为了解决这个问题，我替换了mysql的操作第三方类，该类使用了pdo方式操作mysql。</p>
    <p>为了兼容其他为升级为PHP7的主机，原来的类继续保留，老用户仍然可以继续使用。</p>
  </div>
  <div class="clear"></div>
  <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
  <ins class="adsbygoogle" style="display:inline-block;width:728px;height:90px" data-ad-client="ca-pub-0625745788201806" data-ad-slot="7099159194"></ins>
  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>

<hr />

<div class="update-about-feature">
  <div class="feature-section two-col">
    <div class="col col-1">
      <h3>备份网站数据和文件到云端</h3>
      <p>后台简单设置，即可规定自动备份时间、路径、黑名单和白名单，简化一切复杂操作。</p>
      <ul>
        <li>定时备份，不再担心错过重要的数据</li>
        <li>自动备份，无需每天守着</li>
        <li>定义备份选项，根据需要备份网站</li>
        <li>备份数据库和文件，统统都在云端</li>
      </ul>
      <p><small>要求：1.临时目录的可写权限；2.网站空间性能不至于太差。</small></p>
    </div>
    <div class="col col-2 last-col">
      <img src="<?php echo plugins_url('assets/about-1.jpg',WP2PCS_PLUGIN_NAME); ?>" />
    </div>
  </div>
  <div class="clear"></div>
</div>

<hr />

<div class="update-about-feature">
  <div class="feature-section two-col">
    <div class="col col-1">
      <img src="<?php echo plugins_url('assets/about-2.jpg',WP2PCS_PLUGIN_NAME); ?>" />
    </div>
    <div class="col col-2 last-col">
      <h3>调用云盘内的附件资源到网站内使用</h3>
      <p>媒体插入界面，或者直接引用对应的URL，去除繁复，留下最熟悉的界面。</p>
      <ul>
        <li>操作方便，与WordPress媒体插入和管理统一界面</li>
        <li>使用对应的URL，任何页面都可以使用附件</li>
        <li>支持图片、小文件、mp3、mp4</li>
        <li>支持url重写</li>
      </ul>
      <p><small>要求：网站拥有完整的curl模块，能够很好的实现远程通信。</small></p>
    </div>
  </div>
  <div class="clear"></div>
</div>

<hr />

<div class="update-about-feature">
  <div class="feature-section two-col">
    <div class="col col-1">
      <h3>付费扩展其他功能</h3>
      <p>外链，瞬间加速；基于WP2PCS的插件，各种酷酷的功能；成为付费用户，享受专享服务。</p>
      <ul>
        <li>付费站长，在你的站点开启外链URL形式，加速附件调用</li>
        <li>自助外链服务，无需站点，随处都可以调用资源</li>
        <li>功能扩展，酷酷的感觉~</li>
      </ul>
      <p>具体的付费服务，请点击<a href="http://www.wp2pcs.com/?page_id=730" target="_blank">这里</a>阅读</p>
    </div>
    <div class="col col-2 last-col">
      <img src="<?php echo plugins_url('assets/about-3.jpg',WP2PCS_PLUGIN_NAME); ?>" />
    </div>
  </div>
  <div class="clear"></div>
</div>

<hr />

<div class="update-about-feature">
  <h3>一句话总结</h3>
  <div class="feature-section" style="text-align: center;">
    <p>省去繁复，专注产品，追求酷酷的WEB~</p>
    <p style="padding: 20px 0;">
      <a href="<?php menu_page_url('wp2pcs-setting'); ?>" class="button-primary">立即开始</a>
      <a href="http://www.wp2pcs.com/?cat=3" class="button" target="_blank">使用指南</a>
    </p>
  </div>
  <div class="clear"></div>
</div>

</div>
