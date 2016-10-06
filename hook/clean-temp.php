<?php

add_action('wp2pcs_hourly_check_cron_task','wp2pcs_clean_temp_dir');
function wp2pcs_clean_temp_dir() {
	wp2pcs_rmdir(WP2PCS_TEMP_DIR,false);
}