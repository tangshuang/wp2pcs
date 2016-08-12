<div class="wrap">

<h2 class="nav-tab-wrapper">
  <a href="<?php menu_page_url('wp2pcs-setting'); ?>" class="nav-tab">基本信息</a>
  <a href="javascript:void(0);" class="nav-tab nav-tab-active">定时备份</a>
  <a href="<?php echo add_query_arg('tab','load',menu_page_url('wp2pcs-setting',false)); ?>" class="nav-tab">资源调用</a>
</h2>

<div class="metabox-holder"><div class="meta-box-sortables">
<form method="post" autocomplete="off">

<?php if(!WP2PCS_BAIDU_ACCESS_TOKEN) { ?>
<div class="error"><p><strong>提示</strong>：还没有百度授权。</p></div>
<?php } ?>

<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">定时开关</h3>
  <div class="inside">
    <p>
      <?php
      $wp2pcs_backup_file = get_option('wp2pcs_backup_file');
      $wp2pcs_backup_data = get_option('wp2pcs_backup_data');
      $wp2pcs_backup_time = get_option('wp2pcs_backup_time');
      $reccurences_array = wp2pcs_more_reccurences_for_backup_array();
      $backup_timestamp = wp_next_scheduled('wp2pcs_backup_cron_task');
      ?>
      文件：<select name="wp2pcs_backup_file">
        <?php
        foreach($reccurences_array as $key => $info) {
          echo '<option value="'.$key.'" '.selected($wp2pcs_backup_file,$key,false).'>'.$info['display'].'</option>';
        }
        ?>
      </select>
      数据：<select name="wp2pcs_backup_data">
        <?php
        foreach($reccurences_array as $key => $info) {
          echo '<option value="'.$key.'" '.selected($wp2pcs_backup_data,$key,false).'>'.$info['display'].'</option>';
        }
        ?>
      </select>
      时间：<select name="wp2pcs_backup_time">
        <option <?php selected($wp2pcs_backup_time,'01:00'); ?>>01:00</option>
        <option <?php selected($wp2pcs_backup_time,'02:00'); ?>>02:00</option>
        <option <?php selected($wp2pcs_backup_time,'03:00'); ?>>03:00</option>
      </select>
    </p>
    <p>所有备份文件将被保存在百度云盘的<code><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo urlencode(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/backup'); ?>" target="_blank"><?php echo WP2PCS_BAIDUPCS_REMOTE_ROOT; ?>/backup</a></code>路径中</p>
    <?php if($backup_timestamp) echo '<p>下次运行备份时间：'.date('Y-m-d H:i:s',$backup_timestamp).'</p>'; ?>
  </div>
</div>

<div class="postbox">
  <div class="handlediv" title="点击以切换"><br></div>
  <h3 class="hndle">路径设置</h3>
  <div class="inside">
    <h4>只备份下面路径：</h4>
    <p><textarea class="large-text" name="wp2pcs_backup_path_include"><?php echo stripslashes(get_option('wp2pcs_backup_path_include')); ?></textarea></p>
    <p>每行填写一个路径，填写包含<code><?php echo ABSPATH; ?></code>的绝对路径</p>
  </div>
  <div class="inside">
    <h4>黑名单路径（不备份的路径，即使被包含在上面路径中）：</h4>
    <p><textarea class="large-text" name="wp2pcs_backup_path_exclude"><?php echo stripslashes(get_option('wp2pcs_backup_path_exclude')); ?></textarea></p>
  </div>
  <div class="inside">
    <h4>白名单路径（强制备份，即使在黑名单中）：</h4>
    <p><textarea class="large-text" name="wp2pcs_backup_path_must"><?php echo stripslashes(get_option('wp2pcs_backup_path_must')); ?></textarea></p>
  </div>
</div>

<button type="submit" class="button-primary">确定</button>
<input type="hidden" name="action" value="update-backup-setting">
<a href="<?php echo add_query_arg(array('action'=>'backup-now','_wpnonce'=>wp_create_nonce())); ?>" class="button">立即备份</a>
<?php wp_nonce_field(); ?>

</form>
</div></div><!-- // -->

</div>