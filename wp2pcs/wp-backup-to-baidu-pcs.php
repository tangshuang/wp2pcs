<?php

/*
*
* # 定时任务使用到wp-corn，所以需要一些准备
* http://dhblog.org/28.html
* http://www.neoease.com/wordpress-cron/
*
*/

// 增加schedule,自定义的时间间隔循环的时间间隔 每周一次和每两周一次
add_filter('cron_schedules','wp2pcs_more_reccurences_for_backup');
function wp2pcs_more_reccurences_for_backup($schedules){
	$add_array = wp2pcs_more_reccurences_for_backup_array();
	return array_merge($schedules,$add_array);
}
function wp2pcs_more_reccurences_for_backup_array(){
	return array(
		'daily' => array('interval' => 3600*24, 'display' => '每天一次'),
		'doubly' => array('interval' => 3600*24*2, 'display' => '两天一次'),
		'weekly' => array('interval' => 3600*24*7, 'display' => '每周一次'),
		'biweekly' => array('interval' => 3600*24*7*2, 'display' => '两周一次'),
		'monthly' => array('interval' => 3600*24*30, 'display' => '每月一次'),
		'yearly' => array('interval' => 3600*24*30*12, 'display' => '每年一次'),
		'never' => array('interval' => false, 'display' => '永不备份')
	);
}

// 添加处理
add_action('admin_init','wp_backup_to_pcs_action');
function wp_backup_to_pcs_action(){
	// 判断执行权限
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	// 删除压缩下载产生的压缩包
	if(@$_GET['action'] == 'delete_zip_file' && isset($_GET['path']) && file_exists($_GET['path'])){
		$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
		$zip_file_name = WP2PCS_SITE_DOMAIN.'_backup_by_wp2pcs.zip';
		if($zip_dir.$zip_file_name == $_GET['path']){
			if(!unlink($_GET['path'])){
				wp_die('删除这个文件失败，可能是由于wp-content目录没有可写权限。');
				exit;
			}
		}
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&time='.time().'#wp-to-pcs-backup-area');
		exit;
	}
	// 备份到百度网盘
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_backup_to_pcs_send_file'){
		check_admin_referer();
		set_php_ini('timezone');
		// 更新备份到的网盘目录
		$remote_dir = trim($_POST['wp_backup_to_pcs_remote_dir']);
		if(!$remote_dir || empty($remote_dir)){
			wp_die('请填写备份到网盘的哪个目录！');
			exit;
		}
		$remote_dir = WP2PCS_REMOTE_ROOT.trailing_slash_path($remote_dir);
		update_option('wp_backup_to_pcs_remote_dir',$remote_dir);
		// 更新网站的日志目录
		$log_dir = trim($_POST['wp_backup_to_pcs_log_dir']);
		if($log_dir != ''){
			$log_dir = trailing_slash_path($log_dir,WP2PCS_IS_WIN);
			if(WP2PCS_IS_WIN){
				$log_dir = str_replace('\\\\','\\',$log_dir);
			}
			update_option('wp_backup_to_pcs_log_dir',$log_dir);
		}else{
			delete_option('wp_backup_to_pcs_log_dir');
		}
		// 更新定时日周期
		$run_rate = $_POST['wp_backup_to_pcs_run_rate'];
		update_option('wp_backup_to_pcs_run_rate',$run_rate);
		// 更新定时时间点
		$run_time = $_POST['wp_backup_to_pcs_run_time'];
		update_option('wp_backup_to_pcs_run_time',$run_time);
		// 要备份的目录列表
		$local_paths = trim($_POST['wp_backup_to_pcs_local_paths']);
		if(!empty($local_paths)){
			if(WP2PCS_IS_WIN){
				$local_paths = str_replace('\\\\','\\',$local_paths);
			}
			$local_paths = array_filter(explode("\n",$local_paths));
			update_option('wp_backup_to_pcs_local_paths',$local_paths);
		}else{
			delete_option('wp_backup_to_pcs_local_paths');
			$local_paths = array(ABSPATH);
		}
		// 压缩下载
		if(isset($_POST['wp_backup_to_pcs_zip']) && $_POST['wp_backup_to_pcs_zip'] == '压缩下载'){
			if(!WP2PCS_IS_WRITABLE){
				wp_die('主机没有可写权限，不能打包。');
			}
			$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
			// 备份数据库
			$database_file = $zip_dir.'database.sql';
			if(file_exists($database_file))@unlink($database_file);
			$database_content = "\xEF\xBB\xBF".get_database_backup_all_sql();
			$handle = @fopen($database_file,"w+");
			if(fwrite($handle,$database_content) === false){
				wp_die("写入文件 $database_file 失败");
				exit();
			}
			fclose($handle);
			// 备份日志
			if($log_dir){
				$log_file = zip_files_in_dirs($log_dir,$zip_dir.'logs.zip',$log_dir);
			}
			// 备份网站
			if(is_array($local_paths)){
				$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www.zip',ABSPATH);
			}
			if($log_file || $www_file){
				$zip_file_name = WP2PCS_SITE_DOMAIN.'_backup_by_wp2pcs.zip';
				if($log_file && $www_file){
					$zip_file = zip_files_in_dirs(array($database_file,$log_file,$www_file),$zip_dir.$zip_file_name,$zip_dir);
				}elseif($log_file){
					$zip_file = zip_files_in_dirs(array($database_file,$log_file),$zip_dir.$zip_file_name,$zip_dir);
				}elseif($www_file){
					$zip_file = zip_files_in_dirs(array($database_file,$www_file),$zip_dir.$zip_file_name,$zip_dir);
				}
				/*
				set_php_ini('limit');
				header("Content-type: application/octet-stream");
				header("Content-disposition: attachment; filename=".basename($zip_file));
				$file_content = '';
				$handle = @fopen($zip_file,'rb');
				while(!@feof($handle)){
					$file_content .= fread($handle,20*1024);
				}
				fclose($handle);
				@unlink($zip_file);
				echo $file_content;
				*/
				if(file_exists($log_file))@unlink($log_file);
				if(file_exists($www_file))@unlink($www_file);
				@unlink($database_file);
				$zip_file_url = home_url(str_replace(ABSPATH,'',WP2PCS_TMP_DIR).'/'.$zip_file_name);
				$zip_delete_url = add_query_arg(array('action'=>'delete_zip_file','path'=>$zip_file));
				wp_die("<p>点击下载 <a href='$zip_file_url'>$zip_file_name</a></p><p>注意，下载后你需要手动 <a href='$zip_delete_url'>删除</a> 这个文件。注意，这是绝对保密的，里面包含了你的网站数据，一定要删除！</p>");
				exit;
			}else{
				header("Content-type: application/octet-stream");
				header("Content-disposition: attachment; filename=".basename($database_file));
				echo $database_content;
				@unlink($database_file);
				exit;
			}
		}
		// 立即备份
		if(isset($_POST['wp_backup_to_pcs_now']) && $_POST['wp_backup_to_pcs_now'] == '马上备份'){
			$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
			global $baidupcs;
			
			// 备份数据库
			$file_content = "\xEF\xBB\xBF".get_database_backup_all_sql();
			$file_name = 'database_'.date('Y.m.d_H.i.s').'.sql';
			$baidupcs->upload($file_content,$remote_dir,$file_name);
			
			// 备份日志
			if($log_dir && WP2PCS_IS_WRITABLE){
				$log_file = zip_files_in_dirs($log_dir,$zip_dir.'logs_'.date('Y.m.d_H.i.s').'.zip',$log_dir);
				if($log_file){
					wp_backup_to_pcs_send_file($log_file,$remote_dir);
				}
			}
			
			// 备份网站内的所有文件
			if(is_array($local_paths) && WP2PCS_IS_WRITABLE){
				$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www_'.date('Y.m.d_H.i.s').'.zip',ABSPATH);
				if($www_file){
					wp_backup_to_pcs_send_file($www_file,$remote_dir);
				}
			}
		}
		// 定时备份，需要和下面的wp_backup_to_pcs_corn_task_function函数结合起来
		if(isset($_POST['wp_backup_to_pcs_future'])){
			update_option('wp_backup_to_pcs_future',$_POST['wp_backup_to_pcs_future']);
			if($_POST['wp_backup_to_pcs_future'] == '开启定时'){
				// 开启定时任务
				if(date('Y-m-d '.$run_time.':00') < date('Y-m-d H:i:s')){
					$run_time = date('Y-m-d '.$run_time.':00',strtotime('+1 day'));				
				}else{
					$run_time = date('Y-m-d '.$run_time.':00');
				}
				$run_time = strtotime($run_time);
				foreach($run_rate as $task => $date){
					if($date != 'never'){
						if($task == 'logs' && $log_dir == '')continue;
						if($task == 'www' && $local_paths == '')continue;
						wp_schedule_event($run_time,$date,'wp_backup_to_pcs_corn_task_'.$task);
					}
				}
			}else{
				// 关闭定时任务
				if(wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))
					wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_database');
				if(wp_next_scheduled('wp_backup_to_pcs_corn_task_logs'))
					wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_logs');
				if(wp_next_scheduled('wp_backup_to_pcs_corn_task_www'))
					wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_www');
			}
		}
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&time='.time().'#wp-to-pcs-backup-area');
		exit;
	}
}

// 函数wp_backup_to_pcs_corn_task_function按照规定的时间执行备份动作
add_action('wp_backup_to_pcs_corn_task_database','wp_backup_to_pcs_corn_task_function_database');
add_action('wp_backup_to_pcs_corn_task_logs','wp_backup_to_pcs_corn_task_function_logs');
add_action('wp_backup_to_pcs_corn_task_www','wp_backup_to_pcs_corn_task_function_www');
function wp_backup_to_pcs_corn_task_function_database() {
	if(get_option('wp_backup_to_pcs_future') != '开启定时')
		return;
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['database']) || $run_rate['database'] == 'never')
		return;
	set_php_ini('limit');
	set_php_ini('timezone');
	$remote_dir = trailing_slash_path(get_option('wp_backup_to_pcs_remote_dir'));
	global $baidupcs;
	
	// 备份数据库
	$file_content = "\xEF\xBB\xBF".get_database_backup_all_sql();
	$file_name = 'database_'.date('Y.m.d_H.00').'.sql';
	$result = $baidupcs->upload($file_content,$remote_dir,$file_name);

}
function wp_backup_to_pcs_corn_task_function_logs(){
	if(!WP2PCS_IS_WRITABLE){
		return;
	}
	if(get_option('wp_backup_to_pcs_future') != '开启定时')
		return;
	$log_dir = get_option('wp_backup_to_pcs_log_dir');
	if(!$log_dir)
		return;
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['logs']) || $run_rate['logs'] == 'never')
		return;

	set_php_ini('timezone');
	$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
	$remote_dir = trailing_slash_path(get_option('wp_backup_to_pcs_remote_dir'));

	// 备份日志
	$log_file = zip_files_in_dirs($log_dir,$zip_dir.'logs_'.date('Y.m.d_H.00').'.zip',$log_dir);
	if($log_file){
		wp_backup_to_pcs_send_file($log_file,$remote_dir);
	}
}
function wp_backup_to_pcs_corn_task_function_www(){
	if(!WP2PCS_IS_WRITABLE){
		return;
	}
	if(trim(get_option('wp_backup_to_pcs_future')) != '开启定时')
		return;
	$local_paths = get_option('wp_backup_to_pcs_local_paths');
	if(!$local_paths || empty($local_paths)){
		$local_paths = array(ABSPATH);
	}
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['www']) || $run_rate['www'] == 'never')
		return;
	
	set_php_ini('limit');
	set_php_ini('timezone');
	$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
	$remote_dir = trailing_slash_path(get_option('wp_backup_to_pcs_remote_dir'));

	// 备份网站内的所有文件
	$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www_'.date('Y.m.d_H.00').'.zip',ABSPATH);
	if($www_file){
		wp_backup_to_pcs_send_file($www_file,$remote_dir);
	}

}

// 每天早上6:30定时清理可能由于备份失败导致的文件未删除的文件
function wp_backup_to_pcs_clear_files_task(){
	$run_time = date('Y-m-d 06:30');
	if($run_time < date('Y-m-d H:i:s')){
		$run_time = date('Y-m-d '.$run_time.':00',strtotime('+1 day'));				
	}else{
		$run_time = date('Y-m-d '.$run_time.':00');
	}
	$run_time = strtotime($run_time);	
	wp_schedule_event($run_time,'daily','wp_backup_to_pcs_corn_task_clear_files');
	add_action('wp_backup_to_pcs_corn_task_clear_files','wp_backup_to_pcs_corn_task_function_clear_files');
}
function wp_backup_to_pcs_corn_task_function_clear_files(){
	$zip_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);
	$zip_www = $zip_dir.'www.zip';
	$zip_logs = $zip_dir.'logs.zip';
	$zip_database = $zip_dir.'database.sql';
	$zip_all = WP2PCS_SITE_DOMAIN.'_backup_by_wp2pcs.zip';
	if(file_exists($zip_www))@unlink($zip_www);
	if(file_exists($zip_logs))@unlink($zip_logs);
	if(file_exists($zip_database))@unlink($zip_database);
	if(file_exists($zip_all))@unlink($zip_all);
}

// 创建一个函数直接将单个文件送到百度盘
function wp_backup_to_pcs_send_single_file($local_path,$remote_dir){
	global $baidupcs;
	$file_name = basename($local_path);
	$file_size = filesize($local_path);
	$handle = @fopen($local_path,'rb');
	$file_content = fread($handle,$file_size);
	$baidupcs->upload($file_content,trailing_slash_path($remote_dir),$file_name);
	fclose($handle);
	@unlink($local_path);
}

// 超大文件分片上传函数
function wp_backup_to_pcs_send_super_file($local_path,$remote_dir,$file_block_size){
	global $baidupcs;
	$file_name = basename($local_path);
	
	// 文件大于200M时，使用离线下载功能，可以更快的传输文件，不需要在执行fopen等操作，也可以节省资源了
	if(get_real_filesize($local_path)>WP2PCS_BACKUP_OFFLINE_SIZE && 0):// 百度离线下载效果非常差，因此关闭它
		$result = $baidupcs->addOfflineDownloadTask(trailing_slash_path($remote_dir),home_url('/wp-content/'.$file_name),10*1024*1024,2*3600,'');
		// 离线下载之后增加一个定时任务，将打包文件删除
		set_php_ini('timezone');
		wp_schedule_single_event(time()+(2*3600),'wp_backup_to_pcs_corn_task_delete_file_offline');
		$result = json_decode($result,true);
		if(!isset($result['error_msg'])){
			return;
		}
	endif;
	
	// 如果离线下载功能失效，那么就接着往下执行
	$file_blocks = array();//分片上传文件成功后返回的md5值数组集合
	$handle = @fopen($local_path,'rb');
	while(!@feof($handle)){
		$file_block_content = fread($handle,$file_block_size);
		$temp = $baidupcs->upload($file_block_content,trailing_slash_path($remote_dir),$file_name,false,true);
		if(!is_array($temp)){
			$temp = json_decode($temp,true);
		}
		if(isset($temp['md5'])){
			array_push($file_blocks,$temp['md5']);
		}
	}
	fclose($handle);
	@unlink($local_path);
	if(count($file_blocks) > 1){
		$baidupcs->createSuperFile(trailing_slash_path($remote_dir),$file_name,$file_blocks,'');
	}
}

// 创建一个函数来确定采取什么上传方式，并执行这种方式的上传
function wp_backup_to_pcs_send_file($local_path,$remote_dir){
	$file_name = basename($local_path);
	$file_size = get_real_filesize($local_path);
	$file_max_size = 2*1024*1024;
	if($file_size > $file_max_size){
		wp_backup_to_pcs_send_super_file($local_path,$remote_dir,$file_max_size);
	}else{
		wp_backup_to_pcs_send_single_file($local_path,$remote_dir);
	}
}

// 删除这些离线文件必须在晚上结束的时候执行
/*
add_action('wp_backup_to_pcs_corn_task_delete_file_offline','wp_backup_to_pcs_delete_file_offline');
function wp_backup_to_pcs_delete_file_offline(){
	wp_backup_to_pcs_delete_file_offline_by_filename('www.zip');
	wp_backup_to_pcs_delete_file_offline_by_filename('logs.zip');
}
// 创建一个函数，用来查询离线下载是否已经完成，如果完成了，就把要上传的文件都给删除了
function wp_backup_to_pcs_delete_file_offline_by_filename($file_name){
	global $baidupcs;
	$result = $baidupcs->listOfflineDownloadTask(0,1,0,home_url('/wp-content/'.$file_name),'','','',1);
	$result = json_decode($result,true);
	if(!isset($result['task_info'][0])){
		return;
	}
	$result = $result['task_info'][0];
	if($result['status'] == 1){
		$file = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN).$file_name;
		if(file_exists($file))@unlink($file);
	}
}
*/

// WP2PCS菜单中，使用下面的函数，打印与备份有关的控制面板
function wp_backup_to_pcs_panel(){
	global $baidupcs;
	set_php_ini('timezone');
	$app_key = get_option('wp_to_pcs_app_key');
	$app_token = WP2PCS_APP_TOKEN;
	$remote_dir = get_option('wp_backup_to_pcs_remote_dir');
	$run_rate_arr = get_option('wp_backup_to_pcs_run_rate');
	$run_time = get_option('wp_backup_to_pcs_run_time');
	$log_dir = get_option('wp_backup_to_pcs_log_dir');
	$btn_text = (get_option('wp_backup_to_pcs_future') == '开启定时' ? '已经开启定时备份，现在关闭' : '开启定时');
	$btn_class = ($btn_text == '开启定时' ? 'button-primary' : 'button');
	$timestamp_database = wp_next_scheduled('wp_backup_to_pcs_corn_task_database');
	$timestamp_database = ($timestamp_database ? date('Y-m-d H:i',$timestamp_database) : false);
	$timestamp_logs = wp_next_scheduled('wp_backup_to_pcs_corn_task_logs');
	$timestamp_logs = ($timestamp_logs ? date('Y-m-d H:i',$timestamp_logs) : false);
	$timestamp_www = wp_next_scheduled('wp_backup_to_pcs_corn_task_www');
	$timestamp_www = ($timestamp_www ? date('Y-m-d H:i',$timestamp_www) : false);
	$local_paths = get_option('wp_backup_to_pcs_local_paths');
	$local_paths = (is_array($local_paths) ? implode("\n",$local_paths) : '');
	$backup_rate = wp2pcs_more_reccurences_for_backup_array();
	$is_turned_on = ($timestamp_database || $timestamp_logs || $timestamp_www);
?>
<div class="postbox" id="wp-to-pcs-backup-area">
	<h3>PCS备份设置 <a href="javascript:void(0)" class="tishi-btn">+</a></h3>
	<form method="post" id="wp-to-pcs-backup-form">
	<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
		<?php if($is_turned_on): ?>
		<p>下一次自动备份时间：
			<?php echo ($timestamp_database ? '数据库：'.$timestamp_database : ''); ?>
			<?php echo ($timestamp_logs && $log_dir!='' ? '日志：'.$timestamp_logs : ''); ?>
			<?php echo ($timestamp_www && $local_paths!='' ? '网站：'.$timestamp_www : ''); ?>
			<br />
			要重新规定备份时间，必须先关闭定时备份。
		</p>
		<?php endif; ?>
		<p id="wp-backup-to-pcs-run-area">定时备份：
			数据库<select name="wp_backup_to_pcs_run_rate[database]"><?php $run_rate = $run_rate_arr['database']; ?>
				<?php foreach($backup_rate as $rate => $info) : ?>
				<option value="<?php echo $rate; ?>" <?php selected($run_rate,$rate); ?>><?php echo $info['display']; ?></option>
				<?php endforeach; ?>
			</select> 
			<?php if(WP2PCS_IS_WRITABLE) : ?>
			<span <?php if($log_dir=='')echo 'class="tishi hidden"'; ?>>
			日志<select name="wp_backup_to_pcs_run_rate[logs]"><?php $run_rate = $run_rate_arr['logs']; ?>
				<?php foreach($backup_rate as $rate => $info) : ?>
				<option value="<?php echo $rate; ?>" <?php selected($run_rate,$rate); ?>><?php echo $info['display']; ?></option>
				<?php endforeach; ?>
			</select> </span>
			<span <?php if($local_paths=='')echo 'class="tishi hidden"'; ?>>
			网站<select name="wp_backup_to_pcs_run_rate[www]"><?php $run_rate = $run_rate_arr['www']; ?>
				<?php foreach($backup_rate as $rate => $info) : ?>
				<option value="<?php echo $rate; ?>" <?php selected($run_rate,$rate); ?>><?php echo $info['display']; ?></option>
				<?php endforeach; ?>
			</select> </span>
			<?php endif; ?>
			时间：<select name="wp_backup_to_pcs_run_time">
				<option <?php selected($run_time,'00:00'); ?>>00:00</option>
				<option <?php selected($run_time,'01:00'); ?>>01:00</option>
				<option <?php selected($run_time,'02:00'); ?>>02:00</option>
				<option <?php selected($run_time,'03:00'); ?>>03:00</option>
				<option <?php selected($run_time,'04:00'); ?>>04:00</option>
				<option <?php selected($run_time,'05:00'); ?>>05:00</option>
				<option <?php selected($run_time,'06:00'); ?>>06:00</option>
			</select>
			<?php if($is_turned_on): ?><script>jQuery('#wp-backup-to-pcs-run-area option:not([selected=selected])').remove();</script><?php endif; ?>
		</p>
		<p class="tishi hidden">定时功能：选“永不”则不备份。建议你使用一款名为<a href="http://wordpress.org/plugins/wp-crontrol/" target="_blank">wp-crontrol</a>的插件来管理所有的定时任务。</p>
		<p>备份到网盘目录：<?php echo WP2PCS_REMOTE_ROOT; ?><input type="text"  class="regular-text" name="wp_backup_to_pcs_remote_dir"  value="<?php echo str_replace(WP2PCS_REMOTE_ROOT,'',$remote_dir); ?>" <?php if($timestamp_database || $timestamp_logs || $timestamp_www)echo 'readonly="readonly"';?> /></p>
		<p class="tishi hidden">你会在百度网盘的“我的应用数据”中看到“wp2pcs”这个目录。你在这里填写“backup/”，就会在你的网盘目录“我的应用数据/wp2pcs/<?php echo WP2PCS_SITE_DOMAIN; ?>/backup/”中找到自己的备份数据。</p>
		<?php if(WP2PCS_IS_WRITABLE) : ?>
		<p class="tishi hidden">网站的日志文件夹路径：<input type="text" name="wp_backup_to_pcs_log_dir" class="regular-text" value="<?php echo $log_dir; ?>" <?php if($is_turned_on)echo 'readonly="readonly"';?> /></p>
		<p class="tishi hidden">在上面填写日志文件夹的路径，留空则不备份日志。这个路径不是访问URL，而是相对于服务器的文件路径。你的网站的根路径是“<?php echo ABSPATH; ?>”，一般日志文件都存放在<?php echo ABSPATH; ?>logs/或和public_html目录同一个级别，你需要填写成你自己的。</p>
		<p class="tishi hidden">
			只备份下列文件或目录：（务必阅读下方说明，根路径为：<?php echo ABSPATH; ?>）<br />
			<textarea name="wp_backup_to_pcs_local_paths" class="large-text code" style="height:90px;" <?php if($is_turned_on)echo 'readonly="readonly"';?>><?php echo $local_paths; ?></textarea>
		</p>
		<p class="tishi hidden">只备份特定目录或文件：每行一个，当前年月日分别用{year}{month}{day}代替，不能有空格，末尾带/，必须为网站目录路径（包含路径头<?php echo ABSPATH; ?>）。<b>注意，上级目录将包含下级目录，如<?php echo ABSPATH; ?>wp-content/将包含<?php echo ABSPATH; ?>wp-content/uploads/，因此务必不要重复，两个只能填一个，否则会报错。</b>填写了目录或文件列表之后，只备份填写的列表中的目录或文件。不填，则不备份任何网站目录下的文件。</p>
		<?php endif; ?>
		<p>
			<?php if(!($timestamp_database || $timestamp_logs || $timestamp_www)): ?><input type="submit" value="确定" class="button-primary" />
			&nbsp;&nbsp;&nbsp;&nbsp;<?php endif; ?>
			<input type="submit" name="wp_backup_to_pcs_future" value="<?php echo $btn_text; ?>" class="<?php echo $btn_class; ?>" />
			<input type="submit" name="wp_backup_to_pcs_now" value="马上备份" class="button-primary" onclick="if(confirm('马上备份会备份整站或所填写的目录或文件列表，而且现在备份会花费大量的服务器资源，建议在深夜的时候进行！点击“确定”现在备份，点击“取消”则不备份') == false)return false;" />
			<?php if(WP2PCS_IS_WRITABLE) : ?>
			<input type="submit" name="wp_backup_to_pcs_zip" value="压缩下载" class="button-primary" onclick="if(confirm('压缩下载会花费大量的服务器资源，建议在深夜的时候进行！点击“确定”现在下载，点击“取消”则不备份') == false){return false;}" />
			<?php if(!class_exists('ZipArchive')){echo '<b>当前服务器不支持ZipArchive(请联系主机商)，只有数据库可以被备份。</b>';} ?>
			<?php endif; ?>
		</p>
		<?php if(!file_exists(WP2PCS_TMP_DIR)) : ?>
		<p style="color:red">请先手动在你的网站根目录下创建<?php echo str_replace(ABSPATH,'',WP2PCS_TMP_DIR); ?>目录，并赋予可写权限！</p>
		<?php elseif(!WP2PCS_IS_WRITABLE) : ?>
		<p style="color:red">当前环境下<?php echo WP2PCS_TMP_DIR; ?>目录没有可写权限，不能备份网站，请赋予这个目录可写权限！</p>
		<?php endif; ?>
		<input type="hidden" name="action" value="wp_backup_to_pcs_send_file" />
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		<?php wp_nonce_field(); ?>
	</div>
	<div class="inside tishi hidden" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
		<?php if(WP2PCS_IS_WRITABLE) : ?>
		<p class="tishi hidden" style="color:red;font-weight:bold;">注意：由于备份时需要创建压缩文件，并把压缩文件上传到百度网盘，因此一方面需要你的网站空间有可写权限和足够的剩余空间，另一方面可能会消耗你的网站流量，因此请你一定要注意选择合理的备份方式，以免造成空间塞满或流量耗尽等问题。</p>
		<p class="tishi hidden">境外主机受网络限制，使用马上备份功能可能面临失败的情况，请谨慎使用。<p>
		<?php endif; ?>
	</div>
	</form>
</div>
<?php
}