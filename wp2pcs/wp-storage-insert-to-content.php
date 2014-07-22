<?php

/*
*
* # 这个文件是用来实现从百度网盘获取附件列表，并让站长可以选择插入到文章中
* # http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*

http://sumtips.com/2012/12/add-remove-tab-wordpress-3-5-media-upload-page.html
https://gist.github.com/Fab1en/4586865
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://cantina.co/2012/05/15/tutorial-writing-a-wordpress-plugin-using-the-media-upload-tab-2/
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://stackoverflow.com/questions/5671550/jquery-window-send-to-editor
http://wordpress.stackexchange.com/questions/50873/how-to-handle-multiple-instance-of-send-to-editor-js-function
http://codeblow.com/questions/jquery-window-send-to-editor/
http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*/

// 在新媒体管理界面添加一个百度网盘的选项
add_filter('media_upload_tabs', 'wp_storage_to_pcs_media_tab' );
function wp_storage_to_pcs_media_tab($tabs){
	if(!is_wp_to_pcs_active())return;
	$newtab = array('file_from_pcs' => '百度网盘');
    return array_merge($tabs,$newtab);
}
// 这个地方需要增加一个中间介wp_iframe，这样就可以使用wordpress的脚本和样式
add_action('media_upload_file_from_pcs','media_upload_file_from_pcs_iframe');
function media_upload_file_from_pcs_iframe(){
	wp_iframe('wp_storage_to_pcs_media_tab_box');
}
// 去除媒体界面的多余脚本
add_action('admin_init','wp_storage_to_pcs_media_iframe_remove_actions');
function wp_storage_to_pcs_media_iframe_remove_actions(){
	global $wp_version,$hook_suffix;
	if(!$hook_suffix!='media-upload.php'){
		return;
	}
	if(!isset($_GET['tab']) || $_GET['tab'] != 'file_from_pcs'){
		return;
	}
	remove_all_actions('admin_head');
	remove_all_actions('in_admin_header');
}
// 在上面产生的百度网盘选项中要显示出网盘内的文件
//add_action('media_upload_file_from_pcs','wp_storage_to_pcs_media_tab_box');
function wp_storage_to_pcs_media_tab_box() {
	// 当前路径相关信息
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');	
	if(isset($_GET['dir']) && !empty($_GET['dir'])){
		$dir_pcs_path = $_GET['dir'];
	}else{
		$dir_pcs_path = $remote_dir;
	}
	$app_key = get_option('wp_to_pcs_app_key');
?>
<style>
html,body{background-color:#fff;background-attachment:fixed;}
#opt-on-pcs-tabs{padding:0 1em 0 1em;border-bottom:1px solid #dedede;margin-bottom:1em;font-size:1.1em;
	width:100%;
	position:fixed;
	_position:absolute;
	left:0;
	top:0;
	_top:expression(documentElement.scrollTop);
	background:#fff;
}
#opt-on-pcs-tabs .right{margin-right:3em;}
#files-on-pcs{margin:10px;padding-top:90px;}
.file-on-pcs{width:120px;height:120px;overflow:hidden;float:left;margin:5px;padding:2px;}
.file-thumbnail{width:120px;height:96px;overflow:hidden;background-color:#ccc;}
.file-type-dir .file-thumbnail{background-color:#FDCE5F;}
.file-type-video .file-thumbnail{background-color:#000000;}
.file-type-audio .file-thumbnail{background-color:#8A285C;}
.file-thumbnail img{max-width:100%;height:auto;}
.file-name{line-height:1em;margin-top:3px;}
.selected{background-color:#008000;color:#fff;}
.selected-file{background-color:#A30000;color:#fff;}
.selected-video{background-color:#2E2EFF;color:#fff;}
.selected-audio{background-color:#FF00FF;color:#000000;}
.opt-area{margin:0 10px;padding-bottom:20px;}
.alert{color:#D44B25;margin:0 10px;padding-bottom:20px;}
.hidden{display:none;}
#upload-to-pcs{text-align:center;padding-top:150px;}
.page-navi{font-size:14px;text-align:center;background-color:#E62114;}
.page-navi a{color:#fff;text-decoration:none;}
#prev-page{padding:5px;}
#next-page a{padding:5px;display:block;}
#next-page a:hover{background-color:#1BA933;}
#rename-file{width:118px;height:16px;line-height:16px;border:0;background:#fff;padding:0;}
</style>
<script>
jQuery(function($){
	// 选择要插入的附件
	$('#files-on-pcs div.can-select').live('click',function(e){
		var $this = $(this),
			$file_type = $this.attr('data-file-type');
		if($('#rename-file').is(":visible"))return;
		$this.toggleClass('selected');
		if($file_type == 'file'){
			$(this).toggleClass('selected-file');
		}else if($file_type == 'video'){
			$(this).toggleClass('selected-video');
		}else if($file_type == 'audio'){
			$(this).toggleClass('selected-audio');
		}
	});
	// 调整图片信息
	$('.selected .file-name').live('click',function(){
		var $this = $(this),
			$text = $this.text();
		if($('#rename-file').is(":visible"))$text = $('#rename-file').val();
		$this.html('<input type="text" value="'+$text+'" id="rename-file" />');
		$('#rename-file').focus();
	});
	$('#rename-file').live('focusout',function(){
		var $this = $('#rename-file'),
			$fileName = $this.parent(),
			$text = $this.val();
		if($text==''){
			$text = $fileName.parent().attr('data-file-name');
		}else{
			$fileName.parent().attr('data-file-name',$text);
		}
		$fileName.text($text);
	}).live('keypress',function(e){
		var e = document.all ? window.event : e;
		if(e.keyCode == "13"){
			$(this).trigger('focusout');
		}
	});
	// 点击插入按钮
	$('#insert-btn').click(function(){
		if($('div.selected').length > 0){
			var $image_perfix = '<?php echo trim(get_option("wp_storage_to_pcs_image_perfix")); ?>',
				$download_perfix = '<?php echo trim(get_option("wp_storage_to_pcs_download_perfix")); ?>',
				$video_perfix = '<?php echo trim(get_option("wp_storage_to_pcs_video_perfix")); ?>',
				$audio_perfix = '<?php echo trim(get_option("wp_storage_to_pcs_audio_perfix")); ?>',
				$media_perfix = '<?php echo trim(get_option("wp_storage_to_pcs_media_perfix")); ?>',
				$remote_dir = '<?php echo trim(get_option("wp_storage_to_pcs_remote_dir")); ?>',
				$home_url = '<?php echo home_url("/"); ?>',
				$img_root = $home_url + $image_perfix + '/',
				$download_root = $home_url + $download_perfix + '/',
				$video_root = $home_url + $video_perfix + '/',
				$audio_root = $home_url + $audio_perfix + '/',
				$media_root = $home_url + $media_perfix + '/',
				$html = '';
			$('div.selected').each(function(){
				var $this = $(this),
					$file_name = $this.attr('data-file-name'),
					$file_path = $this.attr('data-file-path'),
					$file_type = $this.attr('data-file-type'),
					$file_touch = $file_path.replace($remote_dir,''),
					$img_src = $img_root + $file_touch,
					$file_src = $download_root + $file_touch,
					$video_src = $video_root + $file_touch,
					$video_cover,
					$video_shortcode = <?php echo (VIDEO_SHORTCODE?'true':'false'); ?>,
					$audio_src = $audio_root + $file_touch,
					$audio_shortcode = <?php echo (AUDIO_SHORTCODE?'true':'false'); ?>,
					$media_src = $media_root + $file_touch;
				// 如果被选择的是图片
				if($file_type == 'image'){
					$html += '<a href="'+$img_src+'" class="wp2pcs-image-link"><img src="'+$img_src+'" class="wp2pcs-image" alt="'+$file_name+'" /></a>';
				}
				// 如果被选择的是视频，使用视频播放器【1.3.0后暂停使用】
				else if($file_type == 'video' && $video_shortcode){
					$html += '[video src="'+$video_src+'.m3u8" cover="" width="640" height="480" stretch="bestfit" refresh="false"]';
				}
				// 如果被选择的是音乐，使用音频播放器【1.3.0后暂停使用】
				else if($file_type == 'audio' && $audio_shortcode){
					$html += '[audio src="'+$audio_src+'" name="'+$file_name+'" autostart="0" loop="no"]';
				}
				// 如果是其他文件，就直接给媒体链接
				else{
					$html += '<a href="' + $file_src + '" class="wp2pcs-download">' + $file_name + '</a>';
				}
			});
			$('.selected').removeClass('selected');
			$('.selected-video').removeClass('selected-video');
			$('.selected-audio').removeClass('selected-audio');
			$('.selected-file').removeClass('selected-file');
			// http://stackoverflow.com/questions/13680660/insert-content-to-wordpress-post-editor
			window.parent.send_to_editor($html);
			window.parent.tb_remove();
		}else{
			alert('没有选择任何附件');
		}
	});
	// 点击关闭按钮
	$('#close-btn').click(function(){
		window.parent.tb_remove();
	});
	// 清除选择的图片
	$('#clear-btn').click(function(){
		$('.selected').removeClass('selected');
		$('.selected-video').removeClass('selected-video');
		$('.selected-audio').removeClass('selected-audio');
		$('.selected-file').removeClass('selected-file');
	});
	// 点击上传按钮
	$('#upload-to-pcs-submit').click(function(){
		var $upload_path = '<?php echo $dir_pcs_path; ?>/',
			$file_name = $('#upload-to-pcs-input').val().match(/[^\/|\\]*$/)[0],
			$action = 'https://pcs.baidu.com/rest/2.0/pcs/file?method=upload&access_token=<?php echo WP2PCS_APP_TOKEN; ?>&ondup=newcopy&path=' + $upload_path + $file_name;
		<?php if(strpos(get_option('wp_storage_to_pcs_image_perfix'),'?') !== false && 0) : // 关闭中文监测 ?>
		if(/.*[\u4e00-\u9fa5]+.*$/.test($file_name)){
			alert('不支持含有汉字的图片名');
			return false;
		}
		<?php endif; ?>
		if($file_name != ''){
			$('#upload-to-pcs-refresh').addClass('hidden');
			$('#upload-to-pcs-from').attr('action',$action).submit();
			$('#upload-to-pcs-processing').removeClass('hidden');
			$is_uploading = setInterval(function(){
				$('#upload-to-pcs-window').load(function(){
					$('#upload-to-pcs-refresh').removeClass('hidden');
					$('#upload-to-pcs-processing').addClass('hidden');
					var $href = window.location.href;
					window.location.href = $href;
					clearInterval($is_uploading);
				});
			},500);
		}
	});
	// 点击切换到上传面板
	$('#show-upload-area').toggle(function(e){
		e.preventDefault();
		$('#files-on-pcs,#next-page,#prev-page,#opt-area,#manage-buttons').hide();
		$('#upload-to-pcs').show();
		$(this).text('返回列表');
	},function(e){
		e.preventDefault();
		$('#upload-to-pcs').hide();
		$('#files-on-pcs,#next-page,#prev-page,#opt-area,#manage-buttons').show();
		$(this).text('上传到这里');
	});
	// 点击下一页
	$('#next-page a').live('click',function(e){
		e.preventDefault();
		var $this = $(this),
			$href = $this.attr('href'),
			$loading = $this.attr('data-loading');
		if($loading=='true')return;
		$.ajax({
			url:$href,
			dataType:'html',
			beforeSend:function(){
				$this.text('正在加载...');
				$this.attr('data-loading','true');
			},
			success:function(data){
				var getHtml = $(data),
					getCode = $('<code></code>').append(getHtml),
					getList = $('#files-on-pcs',getCode).html(),
					getNextPage = $('#next-page',getCode),
					nextPageLink = $('a',getNextPage).attr('href');
				$('#files-on-pcs').append('<hr style="border:0;background:#ccc;height:2px;margin:10px 0;clear:both;" />' + getList);
				if(nextPageLink != undefined){
					$this.attr('href',nextPageLink);
					$this.text('下一页');
					$this.attr('data-loading','false');
				}else{
					$('#next-page').hide().remove();
				}
			}
		});
	});
});
</script>
<div id="opt-on-pcs-tabs">
	<p>当前位置：<a href="<?php echo remove_query_arg(array('dir','paged')); ?>">HOME</a><?php
	if(isset($_GET['dir']) && !empty($_GET['dir'])){
		$current_path = str_replace($remote_dir,'',$dir_pcs_path);
		$current_dir_string = array();
		$current_path_arr = array_filter(explode('/',$current_path));
		if(!empty($current_path_arr))foreach($current_path_arr as $key => $current_dir){
			$current_dir_string[] = $current_dir;
			$current_dir_link = implode('/',$current_dir_string);
			$current_dir_link = add_query_arg('dir',$remote_dir.$current_dir_link);
			$current_dir_link = '/<a href="'.$current_dir_link.'">'.$current_dir.'</a>';
			echo $current_dir_link;
		}
	}
	?> <?php if((is_multisite() && current_user_can('manage_network')) || (!is_multisite() && current_user_can('edit_theme_options'))): ?><a href="#upload-to-pcs" class="button" id="show-upload-area">上传到这里</a><?php endif; ?></p>
	<p id="manage-buttons">
		<button id="insert-btn" class="button-primary">插入</button>
		<button id="clear-btn" class="button">清除</button>
		<button id="close-btn" class="button">关闭</button>
		<?php if($app_key != 'false') : ?><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo $dir_pcs_path; ?>" target="_blank" class="button">管理</a><?php endif; ?>
		<a href="" class="button" id="reflush">刷新</a>
		<a href="javascript:void(0)" onclick="jQuery('#show-media-alert').toggle();jQuery('html,body').animate({scrollTop:jQuery('#show-media-alert').offset().top},500);" class="button">提示帮助</a>
		<a href="javascript:void(0)" onclick="jQuery('html,body').animate({scrollTop:0},500)" class="button right">顶部</a>
	</p>
	<div class="clear"></div>
</div>
<div id="files-on-pcs">
<?php
	if(isset($_GET['paged']) && is_numeric($_GET['paged']) && $_GET['paged'] > 1){
		$paged = $_GET['paged'];
	}else{
		$paged = 1;
	}
	$files_per_page = 7*5;// 每行7个，行数可以自己修改
	$limit = (($paged-1)*$files_per_page).'-'.($paged*$files_per_page);
	$files_on_pcs = wp_storage_to_pcs_media_list_files($dir_pcs_path,$limit);
	$files_count = count($files_on_pcs);
	//print_r($files_on_pcs);
	if(!empty($files_on_pcs))foreach($files_on_pcs as $file){
		$file_name = explode('/',$file->path);
		$file_name = $file_name[count($file_name)-1];
		$file_ext = substr($file_name,strrpos($file_name,'.')+1);
		$file_type = strtolower($file_ext);
		$link = false;
		$thumbnail = false;
		$class = '';
		// 判断是否为图片
		if(in_array($file_type,array('jpg','jpeg','png','gif','bmp'))){
			$thumbnail = wp_storage_to_pcs_media_thumbnail($file->path);
			$file_type = 'image';
		}
		// 判断是否为视频
		elseif(in_array($file_type,array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','ts','rm','rmvb','m3u8'))){
			$file_type = 'video';
			$class .= ' file-type-video ';
		}
		// 判断是否为音频
		elseif($file_type == 'mp3'){ //array('ogg','mp3','wma','wav','mp3pro','mid','midi')
			$file_type = 'audio';
			$class .= ' file-type-audio ';
		}
		else{
			$file_type = 'file';
		}
		// 判断是否为文件（图片）还是文件夹
		if($file->isdir === 0){
			$class .= ' file-type-file can-select ';
		}else{
			$class .= ' file-type-dir ';
			$link = true;
			$file_type = 'dir';
		}
		echo '<div class="file-on-pcs'.$class.'" data-file-name="'.$file_name.'" data-file-type="'.$file_type.'" data-file-path="'.$file->path.'">';
		if($link)echo '<a href="'.add_query_arg('dir',$file->path).'">';
		echo '<div class="file-thumbnail">';
		if($thumbnail)echo '<img src="'.$thumbnail.'" />';
		echo '</div>';
		echo '<div class="file-name" title="点击名称可以修改本次插入的附件名">';
		echo $file_name;
		echo '</div>';
		if($link)echo '</a>';
		echo '</div>';
	}
?>
</div>
<div style="clear:both;"></div>
<div id="upload-to-pcs" class="hidden">
	<form name="input" action="#" method="post" target="upload-to-pcs-window" enctype="multipart/form-data" id="upload-to-pcs-from">
		<input type="file" name="select" id="upload-to-pcs-input" />
		<input type="button" value="上传" class="button-primary" id="upload-to-pcs-submit" />
		<a href="" class="button hidden" id="upload-to-pcs-refresh">成功，刷新查看</a>
		<img src="<?php echo plugins_url('asset/loading.gif',WP2PCS_PLUGIN_NAME); ?>" class="hidden" id="upload-to-pcs-processing" />
	</form>
	<iframe name="upload-to-pcs-window" id="upload-to-pcs-window" style="display:none;"></iframe>
</div>
<div class="opt-area" id="opt-area">
	<?php
	if($paged > 1){
		echo '<p id="prev-page" class="page-navi">';
		echo '<a href="'.remove_query_arg('paged').'">第一页</a> 
		<a href="'.add_query_arg('paged',$paged-1).'">上一页</a>';
		echo '</p>';
	}
	if($files_count >= $files_per_page)echo '<p id="next-page" class="page-navi"><a href="'.add_query_arg('paged',$paged+1).'">下一页</a><p>';
	?>
</div>
<div class="alert hidden" id="show-media-alert">
	<p>如何使用：点击列表中的文件以选择它们，点击插入按钮就可以将选中的文件插入。点击之后背景变绿的是图片，变红的是链接，变蓝的是视频，变紫的是音乐。点击上传按钮会进入你的网盘目录，你上传完文件之后，再点击刷新按钮就可以看到上传完成后的图片。当你进入多个子目录之后，点击返回按钮返回网盘存储根目录。</p>
	<p>本插件提供媒体通用前缀<?php echo get_option('wp_storage_to_pcs_media_perfix'); ?>，调用附件二进制流资源。</p>
	<p>修改文件信息：选中文件之后，在原来的文件名上再点一次即可修改文件名。但修改只对这一次插入有效，并不真正修改文件数据。目前图片不能修改长宽信息，如果要修改长宽信息，先插入图片，然后再使用图片编辑功能修改。</p>
	<p>本插件的本地上传功能比较弱，请在网盘中上传（客户端或网页端都可以），完成之后请点击刷新按钮以查看新上传的文件。</p>
	<p>使用流式文件的实例，用下面的代码来播放flv视频：<?php esc_html_e('<embed src="'.plugins_url( 'asset/flv.swf',WP2PCS_PLUGIN_NAME).'" allowfullscreen="true" isautoplay="0" flashvars="vcastr_file='.wp2pcs_media_src('test.flv').'" quality="high" type="application/x-shockwave-flash" width="500" height="400"></embed>'); ?></p>
	<p>最后，强烈建议文件名、文件夹名使用常规的命名方法，不包含特殊字符，尽可能使用小写字母，使用-作为连接符，使用小写扩展名，由于命名特殊引起的问题，请自行排查。</p>
</div>
<?php
}
// 用一个函数来列出PCS中某个目录下的所有文件（夹）
function wp_storage_to_pcs_media_list_files($dir_pcs_path,$limit){
	global $baidupcs;
	$order_by = 'time';
	$order = 'desc';
	$results = $baidupcs->listFiles($dir_pcs_path,$order_by,$order,$limit);
	$results = json_decode($results);
	$results = $results->list;
	return $results;
}
// 用一个函数来显示这些文件（或目录）
function wp_storage_to_pcs_media_thumbnail($file_pcs_path,$width = 120,$height = 1600,$quality = 100){
	$app_key = get_option('wp_to_pcs_app_key');
	// 使用直链，有利于快速显示图片
	$image_outlink_per = get_option('wp_storage_to_pcs_image_perfix');
	$file_pcs_path = str_replace(trailing_slash_path(get_option('wp_storage_to_pcs_remote_dir')),'/',$file_pcs_path);
	$thumbnail = home_url('/'.$image_outlink_per.$file_pcs_path);
	return $thumbnail;
}