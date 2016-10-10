<script>
// 防止被iframe，特别是刚刚升级后
if(self != top) {
  top.location.href = self.location.href;
  document.write('<style>.wrap{display:none;}</style>');
}
</script>

<style>
.update-about-feature {
  padding:20px 0;
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
.update-about-feature .center {
  width: 640px;
  margin: auto;
  text-align: left;
  color: #999;
}
</style>

<div class="wrap">
  <h2 class="nav-tab-wrapper">
    <a href="<?php echo add_query_arg('tab','general',$page_url); ?>" class="nav-tab">基本信息</a>
    <a href="<?php echo add_query_arg('tab','backup',$page_url); ?>" class="nav-tab">定时备份</a>
    <a href="<?php echo add_query_arg('tab','load',$page_url); ?>" class="nav-tab">资源调用</a>
    <a href="javascript:void(0);" class="nav-tab nav-tab-active">关于</a>
  </h2>
  <div class="update-about-feature headline-feature">
    <h2>版本<?php echo WP2PCS_PLUGIN_VERSION; ?>，极简！</h2>
    <div class="featured-image">
      <img class="about-overview-img" src="<?php echo WP2PCS_PLUGIN_URL.'/assets/about.png'; ?>" width="640" height="360">
    </div>
    <ul class="center">
      <li>删除广告</li>
      <li>不再支持付费服务</li>
      <li>菜单移到“设置-WP2PCS”，不再抢眼球</li>
      <li>不再弹出更新信息</li>
      <li>提供资源开关选项，不再任意消耗资源</li>
      <li>取消本地缓存，减少空间占用</li>
      <li>禁止视频调用，保障插件长期可用。</li>
      <li>-----------------------</li>
      <li>这个版本还修改了一些bug，使得插件在不同的主机上表现更多兼容性</li>
    </ul>
    <div class="center">
      <p>WP2PCS官网不再提供服务，如果在插件使用过程中有什么问题，可以进入<a href="http://github.com/tangshuang/WP2PCS">GitHub</a>提交issue。</p>
      <p>如果你不喜欢这个版本，上一个稳定版可以在<a href="https://github.com/tangshuang/WP2PCS/releases/tag/v1.5.5">这里</a>下载。</p>
    </div>
    <div class="clear"></div>
  </div>
</div>
