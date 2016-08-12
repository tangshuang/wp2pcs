<?php

add_action('admin_print_footer_scripts','wp2pcs_admin_notice',99);
function wp2pcs_admin_notice() {
  if(!current_user_can('edit_theme_options')) return;
  $current_php_file = substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);
  if(in_array($current_php_file,array('post.php','post-new.php','media-upload.php'))){
    return;
  }
  $wp2pcs_admin_notice = (int)get_option('wp2pcs_admin_notice');
  if($wp2pcs_admin_notice < strtotime('-6 hours')) {
    $wp2pcs_site_id = get_option('wp2pcs_site_id');
    $wp2pcs_site_code = get_option('wp2pcs_site_code');
    $src = WP2PCS_API_URL.'/client-admin-notice.js.php?time='.$wp2pcs_admin_notice.'&code='.wp_create_nonce().'&ver='.WP2PCS_PLUGIN_VERSION;
    if($wp2pcs_site_id && $wp2pcs_site_code) $src .= '&site_id='.$wp2pcs_site_id.'&site_code='.$wp2pcs_site_code;
    $src .= '&.js';
    echo '<script src="'.$src.'" id="wp2pcs-admin-notice"></script>';
  }
}

add_action('admin_init','wp2pcs_admin_notice_update');
function wp2pcs_admin_notice_update() {
  if(!current_user_can('edit_theme_options')) return;
  if(isset($_GET['action']) && $_GET['action'] == 'wp2pcs-admin-notice-update') {
    check_admin_referer();
    update_option('wp2pcs_admin_notice',time());
    exit;
  }
}
