<?php

/*
*
* # 这个文件是用来实现从百度网盘获取附件列表，并让站长可以选择插入到文章中
* # http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*

http://sumtips.com/2012/12/add-remove-tab-wordpress-3-5-media-upload-page.html
https://gist.github.com/Fab1en/4586865
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://cantina.co/2012/05/15/tutorial-writing-a-wordpress-plugin-using-the-media-upload-tab-2/
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://stackoverflow.com/questions/5671550/jquery-window-send-to-editor
http://wordpress.stackexchange.com/questions/50873/how-to-handle-multiple-instance-of-send-to-editor-js-function
http://codeblow.com/questions/jquery-window-send-to-editor/
http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*/

// 在新媒体管理界面添加一个选项
add_filter('media_upload_tabs','wp2pcs_insert_media_tab');
function wp2pcs_insert_media_tab($tabs){
  $newtab = array('wp2pcs' => 'WP2PCS');
  // 只有管理员才能使用该功能，普通用户只能上传本地图片
  if(current_user_can('edit_theme_options')) return array_merge($tabs,$newtab);
  else return $tabs;
}
// 这个地方需要增加一个中间介wp_iframe，这样就可以使用wordpress的脚本和样式
add_action('media_upload_wp2pcs','wp2pcs_insert_media_iframe');// media_upload_wp2pcs = [media_upload_] + [tab_key = wp2pcs]
function wp2pcs_insert_media_iframe(){
  wp_iframe('wp2pcs_insert_media_iframe_content');
}
// 去除媒体界面的多余脚本
add_action('admin_init','wp2pcs_insert_media_iframe_remove_actions');
function wp2pcs_insert_media_iframe_remove_actions(){
  global $hook_suffix;
  if(substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1) != 'media-upload.php'){
    return;
  }
  if(!isset($_GET['tab']) || $_GET['tab'] != 'wp2pcs'){
    return;
  }
  remove_all_actions('admin_head');
  remove_all_actions('in_admin_header');
  add_action('admin_enqueue_scripts','wp2pcs_insert_media_scripts');
}
function wp2pcs_insert_media_scripts() {
  wp_register_script('wp2pcs_insert_media_script',plugins_url('/assets/insert-media.js',WP2PCS_PLUGIN_NAME));
  wp_enqueue_script('wp2pcs_insert_media_script');
  wp_register_style('wp2pcs_insert_media_style',plugins_url('/assets/insert-media.css',WP2PCS_PLUGIN_NAME));
  wp_enqueue_style('wp2pcs_insert_media_style');
}
// 在上面产生的百度网盘选项中要显示出网盘内的文件
//add_action('media_upload_file_from_pcs','wp_storage_to_pcs_media_tab_box');
function wp2pcs_insert_media_iframe_content() {
  // 当前路径相关信息
  if(isset($_GET['dir']) && !empty($_GET['dir'])) {
    $dir_path = $_GET['dir'];
  }
  elseif(get_option('wp2pcs_load_remote_dir')) {
    $dir_path = '/apps/wp2pcs/share';
  }
  else{
    $dir_path = WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load';
  }
?>
<div id="wp2pcs-insert-media-iframe-buttons">
  <button class="button float-left" onclick="jQuery('html,body').animate({scrollTop:0},500)">返回顶部</button>
  <a href="<?php echo add_query_arg('refresh',1); ?>" class="button float-left" id="wp2pcs-insert-media-btn-refresh" data-loading="<?php echo plugins_url('assets/loading.gif',WP2PCS_PLUGIN_NAME); ?>">刷新界面</a>
  <?php if((is_multisite() && current_user_can('manage_network')) || (!is_multisite() && current_user_can('edit_theme_options'))): ?><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo $dir_path; ?>" class="button float-left" target="_blank" id="wp2pcs-insert-media-btn-upload">上传</a><?php endif; ?>
  <button class="button float-left" id="wp2pcs-insert-media-btn-help">帮助</button>
  <button class="button" id="wp2pcs-insert-media-btn-clear">清除选中</button>
  <button class="button-primary" id="wp2pcs-insert-media-btn-insert">插入选中</button>
  <div class="clear"></div>
</div>

<div id="wp2pcs-insert-media-iframe-topbar">
  <div id="wp2pcs-insert-media-iframe-place">
  当前位置：
  <a href="<?php echo add_query_arg('dir',WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load',remove_query_arg(array('paged','refresh'))); ?>" <?php if(strpos($dir_path,WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load') === false)echo 'style="color:#ccc;text-decoration:line-through"'; ?>>站点目录</a><?php
  if(strpos($dir_path,'/apps/wp2pcs/share') === false) {
    $current_path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load','',$dir_path);
    $current_path = array_filter(explode('/',$current_path));
    $place_path_arr = array();
    if(!empty($current_path)) foreach($current_path as $dir) {
      $place_path_arr[] = $dir;
      $place_path_link = remove_query_arg('refresh');
      $place_path_link = add_query_arg('dir',WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load/'.implode('/',$place_path_arr),$place_path_link);
      echo ' &rsaquo; <a href="'.$place_path_link.'">'.$dir.'</a>';
    }
  }
  ?>
  | <a href="<?php echo add_query_arg('dir','/apps/wp2pcs/share',remove_query_arg(array('refresh','paged'))); ?>" <?php if(strpos($dir_path,'/apps/wp2pcs/share') === false)echo 'style="color:#ccc;text-decoration:line-through"'; ?>>共享目录</a><?php
  if(strpos($dir_path,'/apps/wp2pcs/share') !== false) {
    $current_path = str_replace('/apps/wp2pcs/share','',$dir_path);
    $current_path = array_filter(explode('/',$current_path));
    $place_path_arr = array();
    if(!empty($current_path)) foreach($current_path as $dir) {
      $place_path_arr[] = $dir;
      $place_path_link = remove_query_arg(array('refresh','paged'));
      $place_path_link = add_query_arg('dir','/apps/wp2pcs/share/'.implode('/',$place_path_arr),$place_path_link);
      echo ' &rsaquo; <a href="'.$place_path_link.'">'.$dir.'</a>';
    }
  }
  ?>
  </div>
  <div id="wp2pcs-insert-media-iframe-check">
    <?php if(get_option('wp2pcs_load_imglink')) { ?><label><input type="checkbox" id="wp2pcs-insert-media-iframe-check-imglink" checked> 图片带链接</label><?php } ?>
    <?php if(get_option('wp2pcs_site_id') && get_option('wp2pcs_video_player')) { ?><label><input type="checkbox" id="wp2pcs-insert-media-iframe-check-videoplay" checked> 视频播放器</label><?php } ?>
    <?php if(strpos($dir_path,'/apps/wp2pcs/share') === 0) { ?><input type="hidden" id="wp2pcs-insert-media-iframe-check-root-dir" value="share"><?php } ?>
  </div>
  <div class="clear"></div>
</div>

<div id="wp2pcs-insert-media-iframe-content">
<div id="wp2pcs-insert-media-iframe-files">
<?php
  $paged = isset($_GET['paged']) && is_numeric($_GET['paged']) && $_GET['paged'] > 1 ? $_GET['paged'] : 1;
  $files_per_page = 3*5;// 每行7个，行数可以自己修改
  $begin = ($paged-1)*$files_per_page;
  $end = $paged*$files_per_page;
  // 下面通过SESSION来做超级简单的缓存
  if(isset($_GET['refresh']) && $_GET['refresh'] == 1) {
    wp2pcs_delete_cache($dir_path.'.dir');
  }
  $files_on_pcs = get_option('wp2pcs_load_cache') ? unserialize(wp2pcs_get_cache($dir_path.'.dir')) : false;
  if(!$files_on_pcs) {
    $files_on_pcs = wp2pcs_insert_media_list_files($dir_path,'0-');
    if(get_option('wp2pcs_load_cache')) {
      wp2pcs_set_cache($dir_path.'.dir',serialize($files_on_pcs));
    }
  }
  // 只有拥有文件列表才列出，防止没有上传文件的时候出现各种问题
  if(is_array($files_on_pcs) && !empty($files_on_pcs)) {
    $files_amount = count($files_on_pcs);
    $files_on_pcs = array_slice($files_on_pcs,$begin,$end-$begin);
    $files_total_page = ceil($files_amount/$files_per_page);
    foreach($files_on_pcs as $file) {
      $file_path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load','',$file->path);
      $file_path = str_replace('/apps/wp2pcs/share','',$file_path);
      $file_name = substr($file->path,strrpos($file->path,'/')+1);
      $file_type = $file->isdir === 0 ? strtolower(substr($file_name,strrpos($file_name,'.')+1)) : 'dir';
      if($file_type == 'dir') {
        $file_format = 'dir';
      }
      elseif(in_array($file_type,array('jpg','jpeg','png','gif','bmp'))) {
        $file_format = 'image';
      }
      elseif(in_array($file_type,array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','rm','rmvb'))) {
        $file_format = 'video';
      }
      elseif(in_array($file_type,array('mp3','ogg','wma','wav','mp3pro','mid','midi'))) {
        $file_format = 'music';
      }
      else {
        $file_format = 'file';
      }
      echo '<div class="file-on-pcs file-type-'.$file_type.' file-format-'.$file_format.'" data-file-size="'.$file->size.'" data-file-type="'.$file_type.'">';
      if($file_type == 'dir') {
        echo '<a href="'.add_query_arg('dir',$file->path,remove_query_arg(array('refresh','paged'))).'" title="目录 '.$file_name.'">'.$file_name.'</a>';
      }
      else {
        $load_linktype = get_option('wp2pcs_load_linktype');
        $site_id = get_option('wp2pcs_site_id');
        $file_url = $load_linktype > 0 ? home_url('/wp2pcs'.$file_path) : home_url('?wp2pcs='.$file_path);
        $file_url = $site_id && $load_linktype > 1 ? WP2PCS_APP_URL.'/'.$site_id.$file_path : $file_url;
        if($file_format == 'image') {
          echo '<input type="checkbox" value="'.$file_url.'">';
          echo '<img src="'.$file_url.'" title="图片 '.$file_name.'">';
        }
        elseif($file_format == 'video') {
          echo '<input type="checkbox" value="'.$file_url.'" data-video-path="'.$file_path.'" data-video-md5="'.$file->md5.'" data-site-id="'.$site_id.'">';
          echo '<a title="视频 '.$file_name.'">'.$file_name.'</a>';
        }
        elseif($file_format == 'music') {
          echo '<input type="checkbox" value="'.$file_url.'" data-site-id="'.$site_id.'">';
          echo '<a title="音乐 '.$file_name.'">'.$file_name.'</a>';
        }
        else {
          echo '<input type="checkbox" value="'.$file_url.'">';
          echo '<a title="文件 '.$file_name.'">'.$file_name.'</a>';
        }
      }
      echo '</div>';
    } // end foreach
  } // endif
?>
</div>
<div class="clear"></div>
<div id="wp2pcs-insert-media-iframe-pagenavi" data-loading="<?php echo plugins_url('assets/loading.gif',WP2PCS_PLUGIN_NAME); ?>">
  <?php
  if($paged > 1){
    echo '<a href="'.remove_query_arg('paged').'">第一页</a>
    <a href="'.add_query_arg('paged',$paged-1).'">上一页</a>';
  }
  if($files_amount >= $files_per_page && ($paged == 1 || $paged < $files_total_page)) {
    echo '<a href="'.add_query_arg('paged',$paged+1).'" class="next-page">下一页</a>';
    echo '<a href="'.add_query_arg('paged',$files_total_page).'">最后页</a>';
  }
  ?>
</div>
</div><!-- // end content area -->
<div id="wp2pcs-insert-media-iframe-help">
  <p>如何使用：点击列表中的文件以选择它们，点击插入按钮就可以将选中的文件插入。点击上传按钮会打开一个小窗口，显示你的网盘目录，你上传完文件之后，再点击刷新按钮就可以看到上传完成后的图片。每次上传完之后，都需要点击刷新按钮，否则无法显示新上传的文件。</p>
  <p>最后，强烈建议文件名、文件夹名使用常规的命名方法，不包含特殊字符，尽可能使用小写字母，使用-作为连接符，使用小写扩展名，由于命名特殊引起的问题，请自行排查。</p>
</div>
<div id="wp2pcs-insert-media-iframe-upload"></div>
<div class="clear body-bottom">&nbsp;</div>
<?php
}
// 用一个函数来列出PCS中某个目录下的所有文件（夹）
function wp2pcs_insert_media_list_files($dir_path,$limit){
  global $BaiduPCS;
  $order_by = 'time';
  $order = 'desc';
  $results = $BaiduPCS->listFiles($dir_path,$order_by,$order,$limit);
  $results = json_decode($results);
  $results = $results->list;
  return $results;
}
