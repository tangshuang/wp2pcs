<?php

/*
 * 这个文件专门为调试WP2PCS准备，如果你的网站在使用WP2PCS中存在什么问题，那么修改wp2pcs.php中WP2PCS_DEBUG为true即可知道具体是什么问题了。
 */


	// 只在前台进行调试，否则连后台都进不去了
	if(is_admin() || !current_user_can('edit_theme_options')){
		return;
	}

	// 查看服务器信息
	if(isset($_GET['phpinfo'])){
		phpinfo();
		exit;
	}

	// 显示运行错误
	error_reporting(E_ALL); 
	ini_set("display_errors", 1);

	// 输出文字
	header("Content-Type: text/html; charset=utf-8");
	
	// 测试session是否可以用
	session_start();
	echo "如果在这句话之前没有看到错误，说明session可以正常使用<br />";
	session_destroy();
	
	// 输出当前插件信息
	if(!function_exists('get_plugin_data')){
		include(ABSPATH.'wp-admin/includes/plugin.php');
	}
	$plugin_data = get_plugin_data(WP2PCS_PLUGIN_NAME);
	$plugin_version = $plugin_data['Version'];
	$version = WP2PCS_PLUGIN_VER;
	$user_type = get_option('wp_to_pcs_app_key')==='false'?'托管在WP2PCS官方':'保存在自己的网盘';
	echo "你当前使用的是个人标准版 [$user_type] 版本号：$plugin_version 最后更新时间：$version <br />";
		
	// 首先检查php环境
	echo "你的网站搭建在 ".PHP_OS." 操作系统的服务器上<br />";
	$software = get_blog_install_software();
	echo "你的网站运行在 $software 服务器上，不同的服务器重写功能会对插件的运行有影响<br />";
	echo "当前的php版本为 ".PHP_VERSION."<br />";
	if(class_exists('ZipArchive')){
		echo "你的PHP支持ZipArchive类，可以正常打包压缩<br />";
	}else{
		echo "PHP不存在ZipArchive类，不能正常备份网站的文件<br />";
	}
	echo '<a href="?phpinfo" target="_blank">点击查看PHPINFO</a><br />';
	

	// 检查是否安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		echo "你的WordPress安装在子目录 $install_in_subdir 中，注意重写规则<br />";
	}else{
		echo "你的网站安装在根目录下<br />";
	}

	// 检查重写情况
	$is_rewrite = is_wp_rewrited();
	if($is_rewrite){
		echo "你的网站重写状况如下： $is_rewrite ，先关闭调试模式，<a href='".home_url('/?p=1')."' target='_blank'>随意阅读一篇文章</a>，看看是否能够被正常访问<br />";
	}else{
		echo "你尚没有修改固定链接形式，插件后台图片等访问前缀不能修改为 image 等形式， ?image 这种形式则可以<br />";
	}

	// 检查是否开启了多站点功能
	if(is_multisite()){
		echo "你的WordPress开启了群站（多站点），可能不能充分发挥本插件的功能，使用如出现问题请及时反馈<br />";
	}

	// 测试创建文件及其相关
	$file = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN).'wp2pcs-debug.txt';
	$handle = fopen($file,"w+");
	$words_count = fwrite($handle,'你的服务器支持创建和写入文件');
	if($words_count > 0){
		echo "创建和写入文件成功，你的服务器支持文件创建和写入<br />";
	}
	$file_content = fread($handle,10);
	$read_over = feof($handle);
	if($file_content){
		echo "读取文件成功，你的服务器支持文件读取<br />";
		echo "读取结果为 $read_over ";
	}
	fclose($handle);
	unlink($file);

	// 检查content目录的写入权限
	if(DIRECTORY_SEPARATOR=='/' && @ini_get("safe_mode")==FALSE){
		echo "没有开启安全模式，".(is_really_writable(WP2PCS_TMP_DIR) ? '缓存目录可写' : '缓存目录不可写')."<br />";
	}else{
		echo "开启了安全模式，";
		$file = rtrim(WP2PCS_TMP_DIR,'/').'/'.md5(mt_rand(1,100).mt_rand(1,100));
		if(($fp = @fopen($file,'w+'))===FALSE){
			echo "缓存目录不可写";
		}else{
			echo "缓存目录可写";
		}
		fclose($fp);
		@chmod($file,'0755');
		@unlink($file);
		echo "<br />";
	}

	// 检查是否存在crossdomain.xml
	$install_root = home_url();
	$domain_root = $install_root;
	if($install_in_subdir){
		$domain_root = str_replace_last($install_in_subdir,'',$install_root);
	}
	if(file_exists(trim($domain_root).'crossdomain.xml')){
		echo "存在crossdomain.xml，<a href='http://".WP2PCS_SITE_DOMAIN."/crossdomain.xml' target='_blank'>检查一下它是否可以被正常访问</a>，并显示出xml结果<br />";
	}else{
		echo "不存在<a href='http://".WP2PCS_SITE_DOMAIN."/crossdomain.xml' target='_blank'>crossdomain.xml</a>文件，网盘中的视频将不能被正常播放<br />";
	}


	// 检查是否授权通过
	global $baidupcs;
	$quota = json_decode($baidupcs->getQuota());
	if(!$baidupcs || !$quota || isset($quota->error_code)){
		if(get_option('wp_to_pcs_site_id')){
			echo '<p style="color:red;"><b>连接失败，有可能你的网站服务器和百度PCS通信不良！</b></p>';
		}else{
			echo '<p style="color:red;"><b>授权失败，无法连接到百度网盘，点击“更新授权”再授权！</b></p>';
		}
	}else{
		echo '百度PCS授权成功 ID:'.get_option('wp_to_pcs_site_id').' ';
		echo '<p>当前网盘总'.number_format(($quota->quota/(1024*1024)),2).'MB，剩余'.number_format((($quota->quota - $quota->used)/(1024*1024)),2).'MB。</p>';
	}

	// 运行时间，可以看出和百度PCS连接的运行状况
	echo "运行了：".get_php_run_time()."<br />";

	// 下面检查配置：
	if(get_option('wp2pcs_connect_too_slow')=='true')echo "当前开启了简易加速<br />";

	echo "<br /><br />目前该测试文件只在linux appache上通过测试，如果你使用的是win主机，或者其他主机，请与我联系。<br /><br />";

	/*
	 * 查看图片调试结果
	 */
	$image_perfix = get_option('wp_storage_to_pcs_image_perfix');
	$audio_perfix = get_option('wp_storage_to_pcs_audio_perfix');
	$video_perfix = get_option('wp_storage_to_pcs_video_perfix');
	$media_perfix = get_option('wp_storage_to_pcs_media_perfix');
	$download_perfix = get_option('wp_storage_to_pcs_download_perfix');

	echo "图片前缀： $image_perfix <br />";
	echo "音乐前缀： $audio_perfix <br />";
	echo "视频前缀： $audio_perfix <br />";
	echo "媒体前缀： $media_perfix <br />";
	echo "下载前缀： $download_perfix <br />";
	
	$image_link = home_url('/'.$image_perfix.'/test.jpg');
	echo "<a href='$image_link'>点击查看图片调试完整结果<a><br />";
	
	$image_perfix = get_option('wp_storage_to_pcs_image_perfix');
	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$image_uri = $current_uri;
	$image_path = '';

	echo "正常访问，下面开始测试图片：<br />";
	echo "1.当前的URI为 $current_uri <br />";

	// 如果不存在前缀，就不执行了
	if(!$image_perfix){
		echo "<b>当前插件配置中图片前缀没有填写</b>";
		exit;
	}

	echo "2.前缀设置判断通过： $image_perfix <br />";

	// 当采用index.php/image时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($image_perfix,'index.php/')===0 && strpos($image_uri,'index.php/')===false){
		$image_perfix = str_replace_first('index.php/','',$image_perfix);
	}
	
	// 如果URI中根本不包含$image_perfix，那么就不用再往下执行了
	if(strpos($image_uri,$image_perfix)===false){
		echo "<b>当前URL中不存在图片访问前缀</b>";
		exit;
	}

	echo "3.URI包含前缀判断通过：当前的URI为 $image_uri ，包含 $image_perfix <br />";

	// 获取安装在子目录
	if($install_in_subdir){
		echo "4.子目录安装通过：wordpress被安装在子目录 $install_in_subdir 中，它将被从URI中删除<br />";
		$image_uri = str_replace_first($install_in_subdir,'',$image_uri);
	}else{
		echo "4.子目录安装判断结果：你的wordpress安装在根目录下 <br />";
	}
	echo "子目录判断后，当前的IMAGE URI为 $image_uri <br />";

	// 返回真正有效的URI
	$image_uri = get_outlink_real_uri($image_uri,$image_perfix);
	echo "5.IIS上需要对index.php段进行删除，处理后的IMAGE URI为 $image_uri<br />";

	// 如果URI中根本不包含$image_perfix，那么就不用再往下执行了
	if(strpos($image_uri,'/'.$image_perfix)!==0){
		echo "<b>IMAGE URI中不以图片访问前缀开头，很有可能是重写机制或其他环境原因造成的，也有可能是你的访问路径本身有问题</b>";
		exit;
	}
	
	// 将前缀也去除，获取文件直接路径
	$image_path = str_replace_first('/'.$image_perfix,'',$image_uri);

	// 如果不存在image_path，也不执行了
	if(!$image_path){
		echo "<b>图片地址不能为空</b>";
		exit;
	}

	echo "6.去除了前缀后，当前的IMAGE URI为 $image_uri 获取到的文件路径为 $image_path<br />";

	// 获取图片路径
	$root_dir = get_option('wp_storage_to_pcs_root_dir');
	$image_path = trailing_slash_path($root_dir).$image_path;
	$image_path = str_replace('//','/',$image_path);
	$outlink_type = get_option('wp_storage_to_pcs_outlink_type');
	if($outlink_type == 200){
		$outlink_type = "直链";
	}elseif($outlink_type == 301){
		$outlink_type = "保护授权信息的外链方式，只有在开发者版本中才使用";
	}else{
		$outlink_type = "外链";
	}

	echo "7.图片最终路径为 $image_path ，附件访问方式为： $outlink_type <br />";
	if(get_option('wp_storage_to_pcs_outlink_protact')){
		echo "8.防盗链功能已开启<br />";
	}else{
		echo "8.没有开启防盗链，任何人都可以使用你的附件<br />";
	}
	echo "<b>如果你能看到这里，说明你的图片（也包括其他附件）应该是可以正常显示的。</b>";

	// 结束调试
	exit;