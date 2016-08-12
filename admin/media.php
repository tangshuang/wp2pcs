<?php
// 用一个函数来列出PCS中某个目录下的所有文件（夹）
function wp2pcs_media_list_files($dir_path,$limit){
  global $BaiduPCS;
  $order_by = 'time';
  $order = 'desc';
  $results = $BaiduPCS->listFiles($dir_path,$order_by,$order,$limit);
  $results = json_decode($results);
  $results = $results->list;
  return $results;
}
// 当前路径相关信息
if(isset($_GET['dir']) && !empty($_GET['dir'])){
  $dir_path = $_GET['dir'];
}
else{
  $dir_path = WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load';
}
?>
<link rel="stylesheet" href="<?php echo plugins_url('/assets/manage-media.css',WP2PCS_PLUGIN_NAME); ?>">
<script src="<?php echo plugins_url('/assets/manage-media.js',WP2PCS_PLUGIN_NAME); ?>"></script>
<div class="wp2pcs-manage-media-page">
<div id="wp2pcs-manage-media-page-top-bar">
  <div id="wp2pcs-manage-media-page-btns">
    <button class="button float-left" onclick="jQuery('html,body').animate({scrollTop:0},500)">返回顶部</button>
    <a href="<?php echo add_query_arg('refresh',1); ?>" class="button float-left" id="wp2pcs-manage-media-btn-refresh" data-loading="<?php echo plugins_url('assets/loading.gif',WP2PCS_PLUGIN_NAME); ?>">刷新界面</a>
    <?php if(!is_multisite() && current_user_can('edit_theme_options')): ?><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo $dir_path; ?>" class="button float-left" target="_blank" id="wp2pcs-manage-media-btn-upload">上传</a><?php endif; ?>
  </div>
  <div id="wp2pcs-manage-media-page-place">
  当前位置：
  <a href="<?php echo remove_query_arg(array('dir','paged','refresh')); ?>" <?php if(strpos($dir_path,WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load') === false)echo 'style="color:#999;text-decoration:line-through;"'; ?>>站点目录</a><?php
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
  | <a href="<?php echo add_query_arg('dir','/apps/wp2pcs/share'); ?>" <?php if(strpos($dir_path,'/apps/wp2pcs/share') === false)echo 'style="color:#999;text-decoration:line-through;"'; ?>>共享目录</a><?php
  if(strpos($dir_path,'/apps/wp2pcs/share') !== false) {
    $current_path = str_replace('/apps/wp2pcs/share','',$dir_path);
    $current_path = array_filter(explode('/',$current_path));
    $place_path_arr = array();
    if(!empty($current_path)) foreach($current_path as $dir) {
      $place_path_arr[] = $dir;
      $place_path_link = remove_query_arg('refresh');
      $place_path_link = add_query_arg('dir','/apps/wp2pcs/share/'.implode('/',$place_path_arr),$place_path_link);
      echo ' &rsaquo; <a href="'.$place_path_link.'">'.$dir.'</a>';
    }
  }
  ?>
  </div>
  <?php if(strpos($dir_path,'/apps/wp2pcs/share') === 0) { ?><input type="hidden" id="wp2pcs-manage-media-page-check-root-dir" value="share"><?php } ?>
  <?php if(get_option('wp2pcs_site_id')) { ?><input type="hidden" id="wp2pcs-manage-media-page-check-vip" value="1"><?php } ?>
  <div id="wp2pcs-manage-media-page-file-info">
    <a href="javascirpt:void(0);" class="close">&times;</a>
    <div class="thumb"></div>
    <div class="format"></div>
    <div class="name"></div>
    <div class="size"></div>
    <div class="path"></div>
    <div class="url"></div>
    <textarea class="code" readonly></textarea>
  </div>
  <div class="clear"></div>
</div>

<div id="wp2pcs-manage-media-page-content">
<div id="wp2pcs-manage-media-page-files">
<?php
  $paged = isset($_GET['paged']) && is_numeric($_GET['paged']) && $_GET['paged'] > 1 ? $_GET['paged'] : 1;
  $files_per_page = 3*6;// 每行7个，行数可以自己修改
  $begin = ($paged-1)*$files_per_page;
  $end = $paged*$files_per_page;
  // 缓存
  if(isset($_GET['refresh']) && $_GET['refresh'] == 1) {
    wp2pcs_delete_cache($dir_path.'.dir');
  }
  $files_on_pcs = get_option('wp2pcs_load_cache') ? unserialize(wp2pcs_get_cache($dir_path.'.dir')) : false;
  if(!$files_on_pcs) {
    $files_on_pcs = wp2pcs_media_list_files($dir_path,'0-');
    if(get_option('wp2pcs_load_cache')) wp2pcs_set_cache($dir_path.'.dir',serialize($files_on_pcs));
  }
  // 只有拥有文件列表才列出，防止没有上传文件的时候出现各种问题
  if(is_array($files_on_pcs) && !empty($files_on_pcs)) {
    $files_amount = count($files_on_pcs);
    $files_on_pcs = array_slice($files_on_pcs,$begin,$end-$begin);
    $files_total_page = ceil($files_amount/$files_per_page);
    foreach($files_on_pcs as $file) {
      $file_path = str_replace(WP2PCS_BAIDUPCS_REMOTE_ROOT.'/load','',str_replace(' ','%20',$file->path));
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
      echo '<div class="file-on-pcs file-type-'.$file_type.' file-format-'.$file_format.'" data-file-type="'.$file_type.'" data-file-format="'.$file_format.'" data-file-size="'.$file->size.'" data-file-name="'.$file_name.'" data-file-path="'.$file->path.'">';
      if($file_type == 'dir') {
        echo '<a href="'.remove_query_arg(array('refresh','paged'),add_query_arg('dir',$file->path)).'" title="目录 '.$file_name.'">'.$file_name.'</a>';
      }
      else {
        $load_linktype = get_option('wp2pcs_load_linktype');
        $site_id = get_option('wp2pcs_site_id');
        $file_url = $load_linktype > 0 ? home_url('/wp2pcs'.$file_path) : home_url('?wp2pcs='.$file_path);
        $file_url = $site_id && $load_linktype > 1 ? WP2PCS_APP_URL.'/'.$site_id.$file_path : $file_url;
        if($file_format == 'image') {
          echo '<img src="'.$file_url.'" title="图片 '.$file_name.'" data-url="'.$file_url.'">';
        }
        elseif($file_format == 'video') {
          echo '<a title="视频 '.$file_name.'" data-url="'.$file_url.'" data-video-path="'.$file_path.'" data-video-md5="'.$file->md5.'" data-site-id="'.$site_id.'">'.$file_name.'</a>';
        }
        elseif($file_format == 'music') {
          echo '<a title="音乐 '.$file_name.'" data-url="'.$file_url.'" data-site-id="'.$site_id.'">'.$file_name.'</a>';
        }
        else {
          echo '<a title="文件 '.$file_name.'" data-url="'.$file_url.'">'.$file_name.'</a>';
        }
      }
      echo '</div>';
    } // end foreach
  } // endif
?>
</div>
<div class="clear"></div>
<div id="wp2pcs-manage-media-page-pagenavi" data-loading="<?php echo plugins_url('assets/loading.gif',WP2PCS_PLUGIN_NAME); ?>">
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
<div id="wp2pcs-manage-media-page-upload"></div>
<div class="clear body-bottom">&nbsp;</div>
</div>
