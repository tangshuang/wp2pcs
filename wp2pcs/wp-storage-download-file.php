<?php

define('WP2PCS_DOWNLOAD_HD',get_option('wp_storage_to_pcs_download_hd'));

// 创建一个函数，用来在wordpress中打印下载地址
function wp2pcs_download_link($file_path = false){
	// file_path是指相对于后台保存的存储目录的路径
	// 例如 $file_path = /test/test.jpg ，就是使用你的网盘目录 /apps/wp2pcs/...../test/test.jpg
	// 其中.....是指你填写的用于保存文件的网盘目录，/test/是你在这个目录下随意创建的一个目录，test.jpg就是要打印的图片
	// 注意最前面加/
	$download_perfix = trim(get_option('wp_storage_to_pcs_download_perfix'));
	$download_link = "/$down_perfix/".$file_path;
	$download_link = str_replace('//','/',$download_link);
	return home_url($download_link);
}

// 通过对URI的判断来确定是否是下载文件的链接
add_action('init','wp_storage_download_file',-1);
function wp_storage_download_file(){
	// 只用于前台下载文件
	if(is_admin()){
		return;
	}

	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$query_pos = strpos($current_uri,'?');
	// 如果URL中有参数
	if($query_pos !== false){
		$current_uri = substr($current_uri,0,$query_pos);
	}

	$download_perfix = trim(get_option('wp_storage_to_pcs_download_perfix'));
	$file_uri = $current_uri;
	$file_path = '';

	// 如果不存在前缀，就不执行了
	if(!$download_perfix){
		return;
	}

	// 当采用index.php/download时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($download_perfix,'index.php/')===0 && strpos($download_uri,'index.php/')===false){
		$download_perfix = str_replace('index.php/','',$download_perfix);
	}

	// 如果URI中根本不包含$download_perfix，那么就不用再往下执行了
	if(strpos($file_uri,$download_perfix) === false){
		return;
	}

	// 获取安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		$file_uri = str_replace_first($install_in_subdir,'',$file_uri);
	}

	// 返回真正有效的URI
	$file_uri = get_outlink_real_uri($file_uri,$file_perfix);

	// 如果URI中根本不包含$download_perfix，那么就不用再往下执行了
	if(strpos($file_uri,'/'.$download_perfix) !== 0){
		return;
	}
	
	// 将前缀也去除，获取文件直接路径
	$file_path = str_replace_first('/'.$download_perfix,'',$file_uri);

	// 如果不存在file_path，也不执行了
	if(!$file_path){
		return;
	}

	// 获取文件真实路径
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	$file_path = trailing_slash_path($remote_dir).$file_path;
	$file_path = str_replace('//','/',$file_path);

	if(WP2PCS_DOWNLOAD_HD == '301' && WP2PCS_OAUTH_CODE){
		$oauth_type = get_option('wp2pcs_oauth_type');
		if($oauth_type >= 1){
			$site_id = get_option('wp_to_pcs_site_id');
			$path = str_replace(WP2PCS_REMOTE_ROOT,'/',$file_path);
			$app_dir = get_option('wp_to_pcs_remote_aplication');
			$url = WP2PCS_STATIC.$site_id.$path;
			if($app_dir != 'wp2pcs')$url .= "?root=$app_dir";
			header("Location:$url");
		}else{
			wp_die('你的Oauth Code被禁用。不要勾选外链。');
		}
		exit;
	}
	else{
		set_wp2pcs_cache();
		// 打印图片到浏览器
		global $baidupcs;
		$result = $baidupcs->download($file_path);

		$meta = json_decode($result,true);
		if(isset($meta['error_msg'])){
			echo $meta['error_msg'];
			exit;
		}
		
		$file_name = basename($file_path);
		header('Content-Disposition:attachment;filename="'.$file_name.'"');
		header('Content-Type:application/octet-stream');
		ob_clean();
		echo $result;
		exit;
	}
	exit;
}