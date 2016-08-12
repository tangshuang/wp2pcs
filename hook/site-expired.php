<?php

if(get_option('wp2pcs_site_id') && get_option('wp2pcs_site_expire') < date('Y-m-d H:i:s')) {
  delete_option('wp2pcs_load_linktype');
  delete_option('wp2pcs_video_m3u8');
  delete_option('wp2pcs_video_player');
}
