<?php

if(isset($_POST['action']) && $_POST['action'] == 'update-video-setting') {
  check_admin_referer();
  $wp2pcs_video_m3u8 = get_option('wp2pcs_site_id') && get_option('wp2pcs_site_expire') > date('Y-m-d H:i:s') ? (int)$_POST['wp2pcs_video_m3u8'] : 0;
  $wp2pcs_video_player = $wp2pcs_video_m3u8 ? (int)$_POST['wp2pcs_video_player'] : 0;
  update_option('wp2pcs_video_m3u8',$wp2pcs_video_m3u8);
  update_option('wp2pcs_video_player',$wp2pcs_video_player);
  wp_redirect(add_query_arg(array('tab'=>'video','time'=>time()),menu_page_url('wp2pcs-advance',false)));
}