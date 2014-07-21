<?php

// true强制采用外链，false则根据后台的设置来，音乐会消耗大量流量，且受网速影响
// 但另外一个问题是，如果音乐文件太大，则外链受到BAE的影响，会泄露token信息，故不建议使用超过10M的外链音乐（直链没有token问题）
// 由于百度网盘对音乐文件的解码也不怎么好，故建议只使用mp3格式音乐文件
define('WP2PCS_AUDIO_HD',get_option('wp_storage_to_pcs_audio_hd'));

// 创建一个函数，用来在wordpress中打印图片地址
function wp2pcs_audio_src($audio_path = false){
	// audio_path是指相对于后台保存的存储目录的路径
	// 例如 $file_path = /test/test.avi
	// 注意最前面加/
	$audio_perfix = get_option('wp_storage_to_pcs_audio_perfix');
	$audio_src = "/$audio_perfix/".$audio_path;
	$audio_src = str_replace('//','/',$audio_src);
	return home_url($audio_src);
}

// 创建短代码来打印音乐
function wp2pcs_audio_shortcode($atts){
	extract(shortcode_atts(array(
		'src' => '',
		'name' => 'Powered by WP2PCS',
		'autostart' => '0',
		'loop' => 'no'
	),$atts));

	global $post;
	static $audio_id = 0,$post_id = 0;
	if($post_id!=$post->ID){
		$audio_id = 1;
		$post_id = $post->ID;
	}
	$player_id = $post->ID.'-'.$audio_id;
	$audio_id ++;

	$name = $name ? $name : 'Powered by WP2PCS';
	$autostart = $autostart ? $autostart : '0';
	$loop = $loop ? $loop : 'no';

	// 处理SRC中存在空格和中文的情况
	if(preg_match('/[一-龥]/u',$src)){
		$record_key = '/wp2pcs-music-'.$player_id.'.mp3';
		$record_value = $src;
		update_option($record_key,$record_value);
		$src = wp2pcs_audio_src($record_key);
	}

	$player = '<div id="audioplayer-'.$player_id.'" class="wp2pcs-audio"></div><script type="text/javascript">AudioPlayer.embed("audioplayer-'.$player_id.'",{titles:"'.$name.'",loop:"'.$loop.'",autostart:"'.$autostart.'",soundFile:"'.$src.'"});</script>';

	return $player;
}
add_shortcode('audio','wp2pcs_audio_shortcode');

// 在网页头部输出音乐播放要使用到的javascript
add_action('wp_head','wp2pcs_audio_player_script');
function wp2pcs_audio_player_script(){
	// 如果你不打算让播放器出现在除了文章页之外的页面，如首页、列表页等，那么可以加上if(!is_singular())return;
	global $wp_query;
	$has_audio = false;
	if($wp_query->posts){
		$count = count($wp_query->posts);
		for($i=0;$i<$count;$i++){
			if(preg_match('/\[audio([^\]]+)?\]/',$wp_query->posts[$i]->post_content)){
				$has_audio = true;
				break;
			}
		}
	}
	if($has_audio)
		echo '<script type="text/javascript" src="'.plugins_url("asset/audio-player.js",WP2PCS_PLUGIN_NAME).'"></script><script type="text/javascript">AudioPlayer.setup("'.plugins_url("asset/player.swf",WP2PCS_PLUGIN_NAME).'",{width:"320",animation:"yes",encode:"no",initialvolume:"60",remaining:"yes",noinfo:"no",buffer:"5",checkpolicy:"no",rtl:"no",bg:"f3f3f3",text:"333333",leftbg:"CCCCCC",lefticon:"333333",volslider:"666666",voltrack:"FFFFFF",rightbg:"B4B4B4",rightbghover:"999999",righticon:"333333",righticonhover:"FFFFFF",track:"FFFFFF",loader:"009900",border:"CCCCCC",tracker:"DDDDDD",skip:"666666",pagebg:"none",transparentpagebg:"no"});</script>';
}

// 通过对URI的判断来获得图片远程信息
add_action('init','wp_storage_print_audio',-1);
function wp_storage_print_audio(){
	// 只用于前台使用音乐
	if(is_admin()){
		return;
	}

	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$query_pos = strpos($current_uri,'?');
	// 如果URL中有参数
	if($query_pos !== false){
		$current_uri = substr($current_uri,0,$query_pos);
	}

	$audio_perfix = get_option('wp_storage_to_pcs_audio_perfix');
	$audio_uri = $current_uri;
	$audio_path = '';

	// 如果不存在前缀，就不执行了
	if(!$audio_perfix){
		return;
	}

	// 获取文件扩展名
	$file_ext = strtolower(substr($audio_uri,strrpos($audio_uri,'.')+1));
	if(!in_array($file_ext,array('ogg','mp3','wma','wav','mp3pro','ape','module','midi','vqf'))){
		return;
	}

	// 当采用index.php/audio时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($audio_perfix,'index.php/')===0 && strpos($audio_uri,'index.php/')===false){
		$audio_perfix = str_replace_first('index.php/','',$audio_perfix);
	}

	// 如果URI中根本不包含$audio_perfix，那么就不用再往下执行了
	if(strpos($audio_uri,$audio_perfix)===false){
		return;
	}

	// 获取安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		$audio_uri = str_replace_first($install_in_subdir,'',$audio_uri);
	}

	// 返回真正有效的URI
	$audio_uri = get_outlink_real_uri($audio_uri,$audio_perfix);

	// 如果URI中根本不包含$audio_perfix，那么就不用再往下执行了
	if(strpos($audio_uri,'/'.$audio_perfix)!==0){
		return;
	}
	
	// 将前缀也去除，获取文件直接路径
	$audio_path = str_replace_first('/'.$audio_perfix,'',$audio_uri);

	// 如果不存在audio_path，也不执行了
	if(!$audio_path){
		return;
	}

	// 如果存在一个经过转化的记录的话
	if(strpos($audio_path,'/wp2pcs-music-')===0){
		$audio_src = get_option($audio_path);
		header("Location:$audio_src");
		exit;
	}

	// 获取视频路径
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	$audio_path = trailing_slash_path($remote_dir).$audio_path;
	$audio_path = str_replace('//','/',$audio_path);
	
	if(WP2PCS_AUDIO_HD == '301' && WP2PCS_OAUTH_CODE){
		$oauth_type = get_option('wp2pcs_oauth_type');
		if($oauth_type >= 1){
			$site_id = get_option('wp_to_pcs_site_id');
			$path = str_replace(WP2PCS_REMOTE_ROOT,'/',$audio_path);
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
		// 打印音乐到浏览器
		global $baidupcs;
		$result = $baidupcs->downloadStream($audio_path);
		
		$meta = json_decode($result,true);
		if(isset($meta['error_msg'])){
			echo $meta['error_msg'];
			exit;
		}

		header("Content-Type: audio/mpeg");
		header('Content-Disposition: inline; filename="'.basename($audio_path).'"');
		header("Content-Transfer-Encoding: binary");
		header('Content-length: '.strlen($result));
		ob_clean();
		echo $result;
		exit;
	}
	exit;
}