<?php

// 强制将保存在本地的图片使用网盘中的资源，主要用在开启强制上传就备份的时候
define('WP2PCS_IMAGE_HD',get_option('wp_storage_to_pcs_image_hd'));

/*
 * 重要提示：
 * 要使用上述功能，你必须使用如下技巧：
 * 增量备份的网盘路径设置为 /apps/你的应用/yourdomain/
 * 网盘附件的存储路径设置为 /apps/你的应用/yourdomain/wp-content/uploads/
 * 因为下面的这些操作的路径替换都是基于这个替换规则的，否则肯定出错
 */

// 创建一个函数，用来在wordpress中打印图片地址
function wp2pcs_image_src($image_path = ''){
	// image_path是指相对于后台保存的存储目录的路径
	// 例如 $image_path = /test/test.jpg
	$image_perfix = trim(get_option('wp_storage_to_pcs_image_perfix'));
	$image_src = "/$image_perfix/".$image_path;
	$image_src = str_replace('//','/',$image_src);
	return home_url($image_src);
}
// 创建一个函数，通过传入本地的图片访问URL获取格式化后的URL
// 例如：yourblog.com/wp-content/uploads/2012/12/test.jpg => yourblog.com/?image/2012/12/test.jpg
function wp2pcs_image_src_by_local_url($url,$att_id = false){
	$att = wp_get_attachment_image_src($att_id,'full');
	if($att){
		$url = $att[0];
	}
	$upload_dir = wp_upload_dir();
	$upload_url = $upload_dir['baseurl'];
	if(strpos($url,$upload_url)===false){
		return $url;
	}
	$image_url = str_replace($upload_url,'',$url);
	$image_url = wp2pcs_image_src($image_url);
	return $image_url;
}
// 创建一个函数，通过传入本地访问图片地址，获取附件访问方式的资源图片地址
// 例如：yourblog.com/wp-content/uploads/2012/12/test.jpg => wp2pcs.duapp.com/img?site_id+token+path=/apps/wp2pcs/wp-content/uploads/2012/12/test.jpg
function wp2pcs_image_origin_src_by_local_url($url){
	$upload_dir = wp_upload_dir();
	$upload_url = $upload_dir['baseurl'];
	if(strpos($url,$upload_url)!==0){
		return $url;
	}
	$remote_dir = trailing_slash_path(get_option('wp_storage_to_pcs_remote_dir'));
	$image_path = str_replace($upload_url,$remote_dir,$url);
	$outlink_type = get_option('wp_storage_to_pcs_outlink_type');
	$site_id = get_option('wp_to_pcs_site_id');
	if($outlink_type == '200'){
		$image_origin = wp2pcs_image_src_by_local_url($url);
	}elseif($outlink_type == '302'){
		$image_origin = 'http://wp2pcs.duapp.com/img?'.$site_id.'+'.substr(WP2PCS_APP_TOKEN,0,10).'+path='.$image_path;
	}else{
		$image_origin = 'https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token='.WP2PCS_APP_TOKEN.'&path='.$image_path;
	}
	return $image_origin;
}
// 创建一个函数，通过传入格式化后的访问图片地址，获取附件访问方式的资源图片地址
// 例如：yourblog.com/?image/2012/12/test.jpg => wp2pcs.duapp.com/img?site_id+token+path=/apps/wp2pcs/wp-content/uploads/2012/12/test.jpg
function wp2pcs_image_origin_src_by_format_url($url){
	$format_dir = wp2pcs_image_src('/');
	if(strpos($url,$format_dir)!==0){
		return $url;
	}
	$remote_dir = trailing_slash_path(get_option('wp_storage_to_pcs_remote_dir'));
	$image_path = str_replace($format_dir,$remote_dir,$url);
	$outlink_type = get_option('wp_storage_to_pcs_outlink_type');
	$site_id = get_option('wp_to_pcs_site_id');
	if($outlink_type == '200'){
		$image_origin = wp2pcs_image_src_by_local_url($url);
	}elseif($outlink_type == '302'){
		$image_origin = 'http://wp2pcs.duapp.com/img?'.$site_id.'+'.substr(WP2PCS_APP_TOKEN,0,10).'+path='.$image_path;
	}else{
		$image_origin = 'https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token='.WP2PCS_APP_TOKEN.'&path='.$image_path;
	}
	return $image_origin;
}
// 将上面两个函数结合起来，创建一个自动判断的函数获取原资源地址
function wp2pcs_image_origin_src($url){
	$upload_dir = wp_upload_dir();
	$upload_url = $upload_dir['baseurl'];
	$format_dir = wp2pcs_image_src();
	if(strpos($url,$upload_url)===0){
		return wp2pcs_image_origin_src_by_local_url($url);
	}elseif(strpos($url,$format_dir)===0){
		return wp2pcs_image_origin_src_by_format_url($url);
	}else{
		return $url;
	}
}

if(WP2PCS_IMAGE_HD && !is_admin() && 0): // 关闭了该功能
	function wp2pcs_get_attachment_link_filter($content,$post_id,$size,$permalink){
		// http://oikos.org.uk/2011/09/tech-notes-using-resized-images-in-wordpress-galleries-and-lightboxes/
		// Only do this if we're getting the file URL
		if(!$permalink){
			// This returns an array of (url, width, height)
			$image = wp_get_attachment_image_src($post_id,'full');
			$image_new_src = wp2pcs_image_src_by_local_url($image[0]);
			$new_content = preg_replace('/href=\'(.*?)\'/','href="'.$image_new_src.'"',$content);
			return $new_content;
		}else{
			return $content;
		}
	}
	function wp2pcs_filter_attachment_url_in_post($content){ // 主要是为了增加data-origin属性
		preg_match_all('/href=\"(.*?)\"/is',$content,$matches);
		if(isset($matches[1]))foreach($matches[1] as $val){
			$content = str_replace('href="'.$val.'"','href="'.wp2pcs_image_src_by_local_url($val).'" data-origin="'.wp2pcs_image_origin_src($val).'"',$content);
		}
		return $content;
	}
	if(get_option('wp_diff_to_pcs_upload_backup')=='true' && get_option('wp2pcs_connect_too_slow')!='true'){
		// 将相册中的图片链接（href）转换为格式化后的图片地址
		if(WP2PCS_IMAGE_RB=='1' || WP2PCS_IMAGE_RB=='2' || WP2PCS_IMAGE_RB=='4'){
			add_filter('wp_get_attachment_link','wp2pcs_get_attachment_link_filter',10,4);
			add_filter('the_content','wp2pcs_filter_attachment_url_in_post',11);
		}
		// 将所有特色图片采用格式化后的SRC
		if(WP2PCS_IMAGE_RB=='2'){
			add_filter('wp_get_attachment_image_src','wp2pcs_image_src_by_local_url');
			add_filter('wp_get_attachment_image_url','wp2pcs_image_src_by_local_url');
			add_filter('post_thumbnail_html','wpse64763_post_thumbnail_fb',20,4);
			function wpse64763_post_thumbnail_fb($html,$post_id,$post_thumbnail_id,$size){
			// http://wordpress.stackexchange.com/questions/64763/the-post-thumbnail-fallback-using-hooks
				$att = wp_get_attachment_image_src($post_thumbnail_id,$size);
				$default_attr = array(
					'class'	=> "attachment-post-thumbnail attachment-$size thumbnail-$post_thumbnail_id parent-post-$post_id",
					'alt'   => trim(strip_tags( get_post_meta($post_thumbnail_id, '_wp_attachment_image_alt', true) )),
				);
				if($html){
					return '<img src="'.wp2pcs_image_src_by_local_url($att[0]).'" width="'.$att[1].'" height="'.$att[2].'" class="'.$default_attr['class'].'" alt="'.$default_attr['alt'].'" />';
				}else{
					return $html;
				}
			}
		}
		// 将所有图片附件都采用格式化后的SRC
		if(WP2PCS_IMAGE_RB=='3' || WP2PCS_IMAGE_RB=='4'){
			add_filter('wp_get_attachment_url','wp2pcs_image_src_by_local_url');
		}
	}
endif;

// 通过对URI的判断来获得图片远程信息
add_action('init','wp_storage_print_image',-1);
function wp_storage_print_image(){
	// 只用于前台打印图片
	if(is_admin()){
		return;
	}

	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$query_pos = strpos($current_uri,'?');
	// 如果URL中有参数
	if($query_pos !== false){
		$current_uri = substr($current_uri,0,$query_pos);
	}

	$image_perfix = get_option('wp_storage_to_pcs_image_perfix');
	$image_uri = $current_uri;
	$image_path = '';

	// 如果不存在前缀，就不执行了
	if(!$image_perfix){
		return;
	}

	// 当采用index.php/image时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($image_perfix,'index.php/')===0 && strpos($image_uri,'index.php/')===false){
		$image_perfix = str_replace_first('index.php/','',$image_perfix);
	}
	
	// 如果URI中根本不包含$image_perfix，那么就不用再往下执行了
	if(strpos($image_uri,$image_perfix)===false){
		return;
	}

	// 获取安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		$image_uri = str_replace_first($install_in_subdir,'',$image_uri);
	}

	// 返回真正有效的URI
	$image_uri = get_outlink_real_uri($image_uri,$image_perfix);

	// 如果URI中根本不包含$image_perfix，那么就不用再往下执行了
	if(strpos($image_uri,'/'.$image_perfix)!==0){
		return;
	}

	// 将前缀也去除，获取文件直接路径
	$image_path = str_replace_first('/'.$image_perfix,'',$image_uri);

	// 如果不存在image_path，也不执行了
	if(!$image_path){
		return;
	}

	// 获取图片路径
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	$image_path = trailing_slash_path($remote_dir).$image_path;
	$image_path = str_replace('//','/',$image_path);

	if(WP2PCS_IMAGE_HD == '301' && WP2PCS_OAUTH_CODE){
		$oauth_type = get_option('wp2pcs_oauth_type');
		if($oauth_type >= 1){
			$site_id = get_option('wp_to_pcs_site_id');
			$path = str_replace(WP2PCS_REMOTE_ROOT,'/',$image_path);
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

		// 将图片文件强制缓存到本地
		$file_local_path = ABSPATH.'wp2pcs_tmp/'.str_replace('/','_',$current_uri).'.tmp';
		$visit_key = 'WP2PCS_IMAGETMP_'.strtoupper(md5($file_local_path));
		$visit_value = get_option($visit_key);
		$visit_value = ($visit_value?$visit_value:0);
		$copy_value = get_option('wp_storage_to_pcs_image_copy');
		
		// 如果存在缓存文件，使用它
		if($copy_value != 0 && $copy_value != '' && file_exists($file_local_path)){
			$file = fopen($file_local_path,"r");
			$result = fread($file,filesize($file_local_path));
			fclose($file);
		}
		// 如果不存在缓存文件，就从PCS获取，并本地化
		else{
			global $baidupcs;
			$result = $baidupcs->downloadStream($image_path);
			/*
			$url = 'https://d.pcs.baidu.com/rest/2.0/pcs/file?method=download&access_token='.WP2PCS_APP_TOKEN.'&path='.$image_path;
			$result = file_get_contents($url);
			*/
			
			$meta = json_decode($result,true);
			if(isset($meta['error_msg'])){
				echo $meta['error_msg'];
				exit;
			}

			// 下面本地化文件
			if($copy_value != 0 && $copy_value != '' && $visit_value >= $copy_value){
				$fopen = fopen($file_local_path,"w+");
				if($fopen != false){
					fwrite($fopen,$result);
				}
				fclose($fopen);
			}
		}
		// 记录被访问的次数，这个次数可以用在今后对附件的评估上面
		$visit_value ++;
		update_option($visit_key,$visit_value);
		
		header('Content-type: image/jpeg');
		ob_clean();
		echo $result;
		exit;
	}
	/*else{
		$image_outlink = 'https://pcs.baidu.com/rest/2.0/pcs/stream?method=download&access_token='.WP2PCS_APP_TOKEN.'&path='.$image_path;
		header('Location:'.$image_outlink);
		exit;
	}*/
	exit;
}