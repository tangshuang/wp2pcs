<?php

$wp2pcs_site_code = get_option('wp2pcs_site_code');
$wp2pcs_site_id = get_option('wp2pcs_site_id');
$wp2pcs_site_expire = get_option('wp2pcs_site_expire');

if(!WP2PCS_BAIDU_ACCESS_TOKEN) {
  echo '<div class="error"><p><strong>提示</strong>：还没有百度授权。</p></div>';
}
elseif($wp2pcs_site_id && $wp2pcs_site_expire < date('Y-m-d H:i:s')) {
  echo '<div class="error"><p><strong>提示</strong>：付费用户已过期，付费功能不可用，请尽快续费。</p></div>';
}
elseif($wp2pcs_site_id && $wp2pcs_site_expire < date('Y-m-d H:i:s',strtotime('+10 days'))) {
  echo '<div class="error"><p><strong>提示</strong>：付费用户快到期，请及时续费，否则付费功能将不可用。</p></div>';
}