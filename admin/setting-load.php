<div class="wrap">

<h2 class="nav-tab-wrapper">
  <a href="<?php menu_page_url('wp2pcs-setting'); ?>" class="nav-tab">基本信息</a>
  <a href="<?php echo add_query_arg('tab','backup',menu_page_url('wp2pcs-setting',false)); ?>" class="nav-tab">定时备份</a>
  <a href="javascript:void(0)" class="nav-tab nav-tab-active">资源调用</a>
</h2>

<div class="metabox-holder"><div class="meta-box-sortables">
<form method="post" autocomplete="off">

<?php if(!WP2PCS_BAIDU_ACCESS_TOKEN) { ?>
<div class="error"><p><strong>提示</strong>：还没有百度授权。</p></div>
<?php } ?>

<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">云端路径</h3>
  <div class="inside">
    <p>所有资源请放在百度网盘<code><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo urlencode(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load'); ?>" target="_blank"><?php echo WP2PCS_BAIDUPCS_REMOTE_ROOT; ?>/load</a></code>目录中</p>
  </div>
</div>

<?php include('tpl/set-linktype.php'); ?>

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

<?php $wp2pcs_load_cache = (int)get_option('wp2pcs_load_cache');  ?>
<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">本地缓存</h3>
  <div class="inside">
    <p><select name="wp2pcs_load_cache">
      <option value="0" <?php selected($wp2pcs_load_cache,0); ?>>关闭</option>
      <option value="1" <?php selected($wp2pcs_load_cache,1); ?>>开启</option>
    </select> 一个文件再被访问<?php echo WP2PCS_CACHE_COUNT; ?>次后会被缓存在本地。媒体列表缓存在本地。</p>
    <p><a href="<?php echo add_query_arg(array('action'=>'clean-cache','_wpnonce'=>wp_create_nonce())); ?>" class="button">清空所有缓存</a></p>
  </div>
</div>

<?php $wp2pcs_image_watermark = get_option('wp2pcs_image_watermark'); ?>
<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">图片水印</h3>
  <div class="inside">
    <p>水印图片的路径：<?php echo realpath(ABSPATH); ?><input type="text" name="wp2pcs_image_watermark" value="<?php echo str_replace(realpath(ABSPATH),'',$wp2pcs_image_watermark); ?>" class="regular-text"></p>
    <p>请先<a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">上传</a>一张用来作为水印的图片，然后把图片的相对路径填写在这里（网站的根路径已经给出），注意填写的路径以/或\开头。</p>
    <p><small>注意：1.仅在选择第一种或第二种附件格式时有效；2.你的主机必须支持GD库；3.使用该功能会占用更多主机资源；4.仅支持jpg,jpeg,png,gif,bmp这几种格式；5.默认水印在右下角，50%透明度，请使用尺寸较小的水印图片。</small></p>
  </div>
</div>

<button type="submit" class="button-primary">确定</button>
<input type="hidden" name="action" value="update-load-setting">
<?php wp_nonce_field(); ?>

</form>
</div></div><!-- // -->

</div>
