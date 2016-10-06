<?php

add_action('wp_footer','wp2pcs_footer_copyright');
function wp2pcs_footer_copyright() {
  echo "\n".'<!-- 本站由WP2PCS驱动，自动备份网站到云盘，调用云盘资源 http://www.tangshuang.net/wp2pcs -->'."\n";
}