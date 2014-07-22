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
	if(@$_GET['action'] == 'delete_zip_file' && isset($_GET['path'])){
    $file_path = $_GET['path'];
    if(strpos($file_path,WP2PCS_TMP_PATH) == 0 && file_exists($file_path)) {
      unlink($_GET['path']);
    }
    wp2pcs_clean_redirect('#wp-to-pcs-backup-area');
	}
  if(!isset($_POST['page'])) {
    return;
  }
	// 更新备份到百度网盘设置
	elseif(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp_backup_to_pcs_update_options'){
		check_admin_referer();
		// 更新定时日周期
		$run_rate = $_POST['wp_backup_to_pcs_run_rate'];
		update_option('wp_backup_to_pcs_run_rate',$run_rate);
		// 更新定时时间点
		$run_time = $_POST['wp_backup_to_pcs_run_time'];
		update_option('wp_backup_to_pcs_run_time',$run_time);
		// 更新网站的日志目录
		$local_logs_path = trim($_POST['wp_backup_to_pcs_local_logs_path']);
		if($local_logs_path != ''){
			$local_logs_path = trailing_slash_path($local_logs_path,IS_WIN);
      $local_logs_path = get_real_path($local_logs_path);
			update_option('wp_backup_to_pcs_local_logs_path',$local_logs_path);
		}else{
			delete_option('wp_backup_to_pcs_local_logs_path');
		}
		// 要备份的目录列表
		$local_paths = trim($_POST['wp_backup_to_pcs_local_paths']);
		if($local_paths){
			if(IS_WIN){
				$local_paths = str_replace('\\\\','\\',$local_paths);
			}
			$local_paths = array_filter(explode("\n",$local_paths));
			update_option('wp_backup_to_pcs_local_paths',$local_paths);
		}else{
			delete_option('wp_backup_to_pcs_local_paths');
			$local_paths = array(get_real_path(ABSPATH));
		}
    wp2pcs_clean_redirect('#wp-to-pcs-backup-area');
  }
	// 压缩下载
  elseif(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp_backup_to_pcs_download'){
		if(!CAN_WRITE){
			wp_die('主机没有可写权限，不能打包。');
		}
		$zip_dir = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN);
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
		$local_logs_path = get_option('wp_backup_to_pcs_local_logs_path');
    if($local_logs_path){
			$log_file = zip_files_in_dirs($local_logs_path,$zip_dir.'logs.zip',$local_logs_path);
		}
		// 备份网站
    $local_paths = get_option('wp_backup_to_pcs_local_paths');
		if(is_array($local_paths)){
			$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www.zip',get_real_path(ABSPATH));
		}
    // 合并文件
		if($log_file || $www_file){
			$zip_file_name = WP2PCS_SITE_DOMAIN.'_backup_by_wp2pcs.zip';
			if($log_file && $www_file){
				$zip_file = zip_files_in_dirs(array($database_file,$log_file,$www_file),$zip_dir.$zip_file_name,$zip_dir);
			}elseif($log_file){
				$zip_file = zip_files_in_dirs(array($database_file,$log_file),$zip_dir.$zip_file_name,$zip_dir);
			}elseif($www_file){
				$zip_file = zip_files_in_dirs(array($database_file,$www_file),$zip_dir.$zip_file_name,$zip_dir);
			}
			if(file_exists($log_file))@unlink($log_file);
			if(file_exists($www_file))@unlink($www_file);
			@unlink($database_file);
			$zip_file_url = home_url(str_replace(get_real_path(ABSPATH),'',WP2PCS_TMP_PATH).'/'.$zip_file_name);
			$zip_delete_url = add_query_arg(array('action'=>'delete_zip_file','path'=>$zip_file));
			wp_die("<p>点击下载 <a href='$zip_file_url'>$zip_file_name</a></p><p>注意，下载后你需要手动 <a href='$zip_delete_url'>删除</a> 这个文件。注意，这是绝对保密的，里面包含了你的网站数据，一定要删除！</p>");
			exit;
		}
    else{
			header("Content-type: application/octet-stream");
			header("Content-disposition: attachment; filename=".basename($database_file));
			echo $database_content;
			@unlink($database_file);
			exit;
		}
	}
	// 立即备份
	elseif(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp_backup_to_pcs_send_now'){
		global $BaiduPCS;
		set_php_ini('timezone');
    
		// 备份数据库
		$file_content = "\xEF\xBB\xBF".get_database_backup_all_sql();
		$file_name = 'database_'.date('Y.m.d_H.i.s').'.sql';
		$BaiduPCS->upload($file_content,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH),$file_name);
			
		$zip_dir = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN);
		$run_rate = get_option('wp_backup_to_pcs_run_rate');
    
    // 备份日志
		$local_logs_path = get_option('wp_backup_to_pcs_local_logs_path');
    if($local_logs_path && CAN_WRITE && $run_rate['logs']){
			$log_file = zip_files_in_dirs($local_logs_path,$zip_dir.'logs_'.date('Y.m.d_H.i.s').'.zip',$local_logs_path);
			if($log_file){
				wp_backup_to_pcs_send_file($log_file,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH));
			}
		}
			
		// 备份网站内的所有文件
    $local_paths = get_option('wp_backup_to_pcs_local_paths');
		if(is_array($local_paths) && CAN_WRITE && $run_rate['www']){
			$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www_'.date('Y.m.d_H.i.s').'.zip',get_real_path(ABSPATH));
			if($www_file){
				wp_backup_to_pcs_send_file($www_file,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH));
			}
		}
    wp2pcs_clean_redirect('#wp-to-pcs-backup-area');
	}
	// 定时备份，需要和下面的wp_backup_to_pcs_corn_task_function函数结合起来
	elseif(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp_backup_to_pcs_open_send_future'){
		update_option('wp_backup_to_pcs_send_future_status',1);
		// 开启定时任务
    set_php_ini('timezone');
    $run_time = get_option('wp_backup_to_pcs_run_time');
		if(date('Y-m-d '.$run_time.':00') < date('Y-m-d H:i:s')){
			$run_time = date('Y-m-d '.$run_time.':00',strtotime('+1 day'));
		}else{
			$run_time = date('Y-m-d '.$run_time.':00');
		}
		$run_time = strtotime($run_time);
    $run_rate = get_option('wp_backup_to_pcs_run_rate');
    $log_dir = get_option('wp_backup_to_pcs_local_logs_path');
    $local_paths = get_option('wp_backup_to_pcs_local_paths');
		foreach($run_rate as $task => $date){
			if($date != 'never'){
				if($task == 'logs' && $log_dir == '')continue;
				if($task == 'www' && $local_paths == '')continue;
				wp_schedule_event($run_time,$date,'wp_backup_to_pcs_corn_task_'.$task);
			}
		}
    wp2pcs_clean_redirect('#wp-to-pcs-backup-area');
	}
  elseif(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp_backup_to_pcs_close_send_future') {
    update_option('wp_backup_to_pcs_send_future_status',0);
    // 关闭定时任务
	  if(wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_database');
		if(wp_next_scheduled('wp_backup_to_pcs_corn_task_logs'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_logs');
		if(wp_next_scheduled('wp_backup_to_pcs_corn_task_www'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_www');
    wp2pcs_clean_redirect('#wp-to-pcs-backup-area');
  }
}

// 函数wp_backup_to_pcs_corn_task_function按照规定的时间执行备份动作
add_action('wp_backup_to_pcs_corn_task_database','wp_backup_to_pcs_corn_task_function_database');
add_action('wp_backup_to_pcs_corn_task_logs','wp_backup_to_pcs_corn_task_function_logs');
add_action('wp_backup_to_pcs_corn_task_www','wp_backup_to_pcs_corn_task_function_www');
function wp_backup_to_pcs_corn_task_function_database() {
	if(get_option('wp_backup_to_pcs_send_future_status') != 1)
		return;
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['database']) || $run_rate['database'] == 'never')
		return;

	global $BaiduPCS;
	set_php_ini('limit');
	set_php_ini('timezone');
	// 备份数据库
	$file_content = "\xEF\xBB\xBF".get_database_backup_all_sql();
	$file_name = 'database_'.date('Y.m.d_H.i').'.sql';
  $BaiduPCS->upload($file_content,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH),$file_name);

}
function wp_backup_to_pcs_corn_task_function_logs(){
	if(!CAN_WRITE){
		return;
	}
	if(get_option('wp_backup_to_pcs_send_future_status') != 1)
		return;
  $local_logs_path = get_option('wp_backup_to_pcs_local_logs_path');
	if(!$local_logs_path)
		return;
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['logs']) || $run_rate['logs'] == 'never')
		return;

	set_php_ini('timezone');
	$zip_dir = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN);

	// 备份日志
	$log_file = zip_files_in_dirs($local_logs_path,$zip_dir.'logs_'.date('Y.m.d_H.i').'.zip',$local_logs_path);
	if($log_file){
		wp_backup_to_pcs_send_file($log_file,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH));
	}
}
function wp_backup_to_pcs_corn_task_function_www(){
	if(!CAN_WRITE){
		return;
	}
	if(get_option('wp_backup_to_pcs_send_future_status') != 1)
		return;
	$run_rate = get_option('wp_backup_to_pcs_run_rate');
	if(!isset($run_rate['www']) || $run_rate['www'] == 'never')
		return;

	$local_paths = get_option('wp_backup_to_pcs_local_paths');
	if(!$local_paths || empty($local_paths)){
		$local_paths = array(ABSPATH);
	}
	
	set_php_ini('limit');
	set_php_ini('timezone');
	$zip_dir = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN);

	// 备份网站内的所有文件
	$www_file = zip_files_in_dirs($local_paths,$zip_dir.'www_'.date('Y.m.d_H.i').'.zip',get_real_path(ABSPATH));
	if($www_file){
		wp_backup_to_pcs_send_file($www_file,trailing_slash_path(WP2PCS_REMOTE_BACKUP_PATH));
	}

}

// 每天早上6:30定时清理可能由于备份失败导致的文件未删除的文件
function wp_backup_to_pcs_clear_files_task(){
	set_php_ini('timezone');
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
	$zip_dir = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN);
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
	global $BaiduPCS;
	$file_name = basename($local_path);
	$file_size = filesize($local_path);
	$handle = @fopen($local_path,'rb');
	$file_content = fread($handle,$file_size);
	$BaiduPCS->upload($file_content,trailing_slash_path($remote_dir),$file_name);
	fclose($handle);
	@unlink($local_path);
}

// 超大文件分片上传函数
function wp_backup_to_pcs_send_super_file($local_path,$remote_dir){
	global $BaiduPCS;
	$file_name = basename($local_path);
	
	$file_blocks = array();//分片上传文件成功后返回的md5值数组集合
	$handle = @fopen($local_path,'rb');
	while(!@feof($handle)){
		$file_block_content = fread($handle,2*1024*1024);
		$temp = $BaiduPCS->upload($file_block_content,trailing_slash_path($remote_dir),$file_name,false,true);
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
		$BaiduPCS->createSuperFile(trailing_slash_path($remote_dir),$file_name,$file_blocks,'');
	}
}

// 创建一个函数来确定采取什么上传方式，并执行这种方式的上传
function wp_backup_to_pcs_send_file($local_path,$remote_dir){
	$file_name = basename($local_path);
	$file_size = get_real_filesize($local_path);
	$file_max_size = 20*1024*1024;
	if($file_size > $file_max_size){
		wp_backup_to_pcs_send_super_file($local_path,$remote_dir);
	}else{
		wp_backup_to_pcs_send_single_file($local_path,$remote_dir);
	}
}

// WP2PCS菜单中，使用下面的函数，打印与备份有关的控制面板
function wp_backup_to_pcs_panel(){
	set_php_ini('timezone');
	$baidu_app_token = WP2PCS_BAIDU_APP_TOKEN;
	$run_rate_arr = get_option('wp_backup_to_pcs_run_rate');
	$run_time = get_option('wp_backup_to_pcs_run_time');
	$log_dir = get_option('wp_backup_to_pcs_local_logs_path');
  $btn_option = get_option('wp_backup_to_pcs_send_future_status');
  $btn_value = ($btn_option == 1 ? 'wp_backup_to_pcs_close_send_future' : 'wp_backup_to_pcs_open_send_future');
	$btn_text = ($btn_option == 1 ? '已经开启定时备份，现在关闭' : '开启定时');
	$btn_class = ($btn_option == 1 ? 'button' : 'button-primary');
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
			<?php if(CAN_WRITE) : ?>
			<span <?php if($log_dir=='')echo 'class="tishi hidden"'; ?>>
			日志<select name="wp_backup_to_pcs_run_rate[logs]"><?php $run_rate = $run_rate_arr['logs']; ?>
				<?php foreach($backup_rate as $rate => $info) : ?>
				<option value="<?php echo $rate; ?>" <?php selected($run_rate,$rate); ?>><?php echo $info['display']; ?></option>
				<?php endforeach; ?>
			</select> </span>
			网站<select name="wp_backup_to_pcs_run_rate[www]"><?php $run_rate = $run_rate_arr['www']; ?>
				<?php foreach($backup_rate as $rate => $info) : ?>
				<option value="<?php echo $rate; ?>" <?php selected($run_rate,$rate); ?>><?php echo $info['display']; ?></option>
				<?php endforeach; ?>
			</select>
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
		<p>备份到网盘目录：<a href="http://pan.baidu.com/disk/home#path=<?php echo urlencode(WP2PCS_REMOTE_BACKUP_PATH); ?>" target="_blank"><?php echo WP2PCS_REMOTE_BACKUP_PATH; ?></a></p>
		<p class="tishi hidden">你会在百度网盘的“我的应用数据”中看到“wp2pcs”这个目录。点击上面的链接直接进入网盘查看。</p>
		<?php if(CAN_WRITE) : ?>
		<p class="tishi hidden">网站的日志文件夹路径：<input type="text" name="wp_backup_to_pcs_local_logs_path" class="regular-text" value="<?php echo $log_dir; ?>" <?php if($is_turned_on)echo 'readonly="readonly"';?> /></p>
		<p class="tishi hidden">在上面填写日志文件夹的路径，留空则不备份日志。这个路径不是访问URL，而是相对于服务器的文件路径。你的网站的根路径是“<?php echo get_real_path(ABSPATH); ?>”，一般日志文件都存放在<?php echo get_real_path(ABSPATH.'logs/'); ?>或和public_html目录同一个级别，你需要填写成你自己的。</p>
		<p <?php if(!$local_paths)echo 'class="tishi hidden"'; ?>>
			只备份下列文件或目录：（务必阅读下方说明，根路径为：<?php echo get_real_path(ABSPATH); ?>）<br />
			<textarea name="wp_backup_to_pcs_local_paths" class="large-text code" style="height:90px;" <?php if($is_turned_on)echo 'readonly="readonly"';?>><?php echo $local_paths; ?></textarea>
		</p>
		<p class="tishi hidden">只备份特定目录或文件：每行一个，当前年月日分别用{year}{month}{day}代替，不能有空格，目录末尾带<?php echo DIRECTORY_SEPARATOR; ?>，必须为网站目录路径（包含路径头<?php echo get_real_path(ABSPATH); ?>）。<b>注意，上级目录将包含下级目录，如<?php echo get_real_path(ABSPATH.'wp-content/'); ?>将包含<?php echo get_real_path(ABSPATH.'wp-content/uploads/'); ?>，因此务必不要重复，两个只能填一个，否则会报错。</b>填写了目录或文件列表之后，只备份填写的列表中的目录或文件。不填，则备份网站的所有文件。</p>
		<?php endif; ?>
		<p>
			<?php if(!$is_turned_on) : ?>
      <button type="submit" name="action" value="wp_backup_to_pcs_update_options" class="button-primary">更新</button>
			&nbsp;&nbsp;&nbsp;&nbsp;
      <?php endif; ?>
      <button type="submit" name="action" value="<?php echo $btn_value; ?>" class="<?php echo $btn_class; ?>"><?php echo $btn_text; ?></button>
      <button type="submit" name="action" value="wp_backup_to_pcs_send_now" class="button-primary">马上备份</button>
			<?php if(CAN_WRITE) : ?>
      <button type="submit" name="action" value="wp_backup_to_pcs_download" class="button-primary">压缩下载</button>
			<?php endif; ?>
		</p>
		<?php if(!file_exists(WP2PCS_TMP_PATH)) : ?>
		<p style="color:red">请先手动在你的网站根目录下创建<?php echo str_replace(ABSPATH,'',WP2PCS_TMP_PATH); ?>目录，并赋予可写权限！</p>
		<?php elseif(!CAN_WRITE) : ?>
		<p style="color:red">当前环境下<?php echo WP2PCS_TMP_PATH; ?>目录没有可写权限，不能备份网站，请赋予这个目录可写权限！</p>
		<?php endif; ?>
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		<?php wp_nonce_field(); ?>
	</div>
	<div class="inside tishi hidden" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
		<p class="tishi hidden" style="color:red;font-weight:bold;">注意：由于备份时需要创建压缩文件，并把压缩文件上传到百度网盘，因此一方面需要你的网站空间有可写权限和足够的剩余空间，另一方面可能会消耗你的网站流量，因此请你一定要注意选择合理的备份方式，以免造成空间塞满或流量耗尽等问题。</p>
		<p class="tishi hidden">境外主机受网络限制，使用马上备份功能可能面临失败的情况，请谨慎使用。<p>
	</div>
	</form>
</div>
<?php
}