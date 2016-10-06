<?php

// 更新配置
if(isset($_POST['action']) && $_POST['action'] == 'update-load-setting') {
  check_admin_referer();
  // 开关
  update_option('wp2pcs_load_switch',$_POST['wp2pcs_load_switch']);
  // 更新链接
  $linktype = (int)$_POST['wp2pcs_load_linktype'];
  if($linktype == 1) {
    global $wp_rewrite;
    if(!$wp_rewrite->permalink_structure) {
      $linktype = 0;
    }
  }
  update_option('wp2pcs_load_linktype',$linktype);
  // 更新是否插入图片链接
  update_option('wp2pcs_load_imglink',$_POST['wp2pcs_load_imglink']);
  // 更新采用站点目录还是共享目录作为默认目录
  update_option('wp2pcs_load_remote_dir',$_POST['wp2pcs_load_remote_dir']);
  wp_redirect(add_query_arg(array('tab'=>$page_name,'time'=>time()),$page_url));
}