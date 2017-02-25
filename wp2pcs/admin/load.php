<div class="wrap">

<h2 class="nav-tab-wrapper">
  <a href="<?php echo add_query_arg('tab','general',$page_url); ?>" class="nav-tab">基本信息</a>
  <a href="<?php echo add_query_arg('tab','backup',$page_url); ?>" class="nav-tab">定时备份</a>
  <a href="javascript:void(0)" class="nav-tab nav-tab-active">资源调用</a>
</h2>

<div class="metabox-holder"><div class="meta-box-sortables">
<form method="post" autocomplete="off">

<?php if(!WP2PCS_BAIDU_ACCESS_TOKEN) { ?>
<div class="error"><p><strong>提示</strong>：还没有百度授权。</p></div>
<?php } ?>

<?php $wp2pcs_load_switch = get_option('wp2pcs_load_switch'); ?>
<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">资源调用开关</h3>
  <div class="inside">
    <p>
      <select name="wp2pcs_load_switch">
        <option value="0" <?php selected($wp2pcs_load_switch,0); ?>>关闭</option>
        <option value="1" <?php selected($wp2pcs_load_switch,1); ?>>开启</option>
      </select>
      资源调用功能
    </p>
    <p><small>如果你只希望使用备份功能，可以关闭资源调用功能，这样可以节省一些资源。但是关闭之后，将无法显示你之前插入的百度云图片。<br>资源调用会先从百度云抓取资源到你的服务器，然后才显示给用户看，所以要消耗两边的流量。<br>默认设置了一个月的本地浏览器缓存时间，你可以在config.php中修改。</small></p>
  </div>
</div>

<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">云端路径</h3>
  <div class="inside">
    <p>站点目录：<code><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo urlencode(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load'); ?>" target="_blank"><?php echo WP2PCS_BAIDUPCS_REMOTE_ROOT; ?>/load</a></code></p>
    <p>共享目录：<code><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo urlencode(WP2PCS_BAIDUPCS_SHARE_ROOT); ?>" target="_blank"><?php echo WP2PCS_BAIDUPCS_SHARE_ROOT; ?></a></code></p>
  </div>
</div>

<?php
$wp2pcs_load_linktype = (int)get_option('wp2pcs_load_linktype');
global $wp_rewrite;
?>
<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">链接形式</h3>
  <div class="inside">
    <p>
      <label>
      <input type="radio" name="wp2pcs_load_linktype" value="0" <?php checked($wp2pcs_load_linktype,0); ?>>
      <?php echo home_url('/?'.WP2PCS_URL_PREFIX.'=/img/test.jpg'); ?> （兼容性好，推荐）
      </label>
    </p>
    <p>
      <label>
      <input type="radio" name="wp2pcs_load_linktype" value="1" <?php checked($wp2pcs_load_linktype,1); ?> <?php if(!$wp_rewrite->permalink_structure) echo 'disabled'; ?>>
      <?php echo home_url('/'.WP2PCS_URL_PREFIX.'/img/test.jpg'); ?> <?php if(!$wp_rewrite->permalink_structure) echo '（重写未开）'; ?>
      （需要重写支持）
      </label>
    </p>
    <p>
      <label>
      <input type="radio" name="wp2pcs_load_linktype" value="2" <?php checked($wp2pcs_load_linktype,2); ?>>
      <?php echo WP2PCS_APP_URL.'/img/test.jpg'; ?> （需要你在<?php echo WP2PCS_APP_URL; ?>架设static服务器）
      </label>
    </p>
  </div>
</div>

<?php
$wp2pcs_load_imglink = (int)get_option('wp2pcs_load_imglink');
$wp2pcs_load_remote_dir = (int)get_option('wp2pcs_load_remote_dir');
?>
<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">媒体插入</h3>
  <div class="inside">
    <p>插入图片时插入其链接？<select name="wp2pcs_load_imglink">
      <option value="0" <?php selected($wp2pcs_load_imglink,0); ?>>关闭</option>
      <option value="1" <?php selected($wp2pcs_load_imglink,1); ?>>开启</option>
    </select></p>
  </div>
  <div class="inside">
    <p>插入面板默认目录：<select name="wp2pcs_load_remote_dir">
      <option value="0" <?php selected($wp2pcs_load_remote_dir,0); ?>>站点目录</option>
      <option value="1" <?php selected($wp2pcs_load_remote_dir,1); ?>>分享目录</option>
    </select></p>
  </div>
</div>

<button type="submit" class="button-primary">确定</button>
<input type="hidden" name="action" value="update-load-setting">
<?php wp_nonce_field(); ?>

</form>
</div></div><!-- // -->

</div>
