<?php

/*
Plugin Name: WP2PCS(WP连接网盘)
Plugin URI: http://www.wp2pcs.com/
Description: 本插件帮助网站站长将网站和百度网盘连接。网站的数据库、日志、网站程序文件（包括wordpress系统文件、主题、插件、上传的附件等）一并上传到百度云盘，站长可以根据自己的习惯定时备份，让你的网站数据不再丢失！可以实现把网盘作为自己的附件存储空间，实现文件、图片、音乐、视频外链等功能。
Version: 1.3.8
Author: 否子戈
Author URI: http://www.utubon.com
*/

/*
 *
 * 初始化数据
 *
 */

// 初始化固定值常量
define('WP2PCS_PLUGIN_NAME',__FILE__);

// 包含一些必备的函数和类，以提供下面使用
require(dirname(__FILE__).'/wp2pcs-setup-functions.php');
require(dirname(__FILE__).'/libs/BaiduPCS.class.php');

// 经过判断或函数运算才能进行定义的常量
define('WP2PCS_APP_KEY',get_option('wp_to_pcs_app_key'));// CuOLkaVfoz1zGsqFKDgfvI0h
define('WP2PCS_APP_SECRET',get_option('wp_to_pcs_app_secret'));
define('WP2PCS_APP_TOKEN',get_option('wp_to_pcs_app_token'));
define('WP2PCS_OAUTH_CODE',get_option('wp2pcs_oauth_code'));
define('WP2PCS_SITE_DOMAIN',$_SERVER['HTTP_HOST']);
define('WP2PCS_REMOTE_ROOT','/apps/'.get_option('wp_to_pcs_remote_aplication').'/'.WP2PCS_SITE_DOMAIN.'/');
define('WP2PCS_PLUGIN_VER',str_replace('.','','2014.06.05.20.00'));// 以最新一次更新的时间点（到分钟）作为版本号
define('WP2PCS_IS_WIN',strpos(PHP_OS,'WIN')!==false);
define('WP2PCS_TMP_DIR',get_real_path(ABSPATH.'wp2pcs_tmp'));// WP2PCS暂时性存储目录
define('WP2PCS_IS_WRITABLE',is_really_writable(WP2PCS_TMP_DIR));

// 当你发现自己错过了很多定时任务时，可以帮助你执行没有执行完的定时任务
if(is_admin())define('ALTERNATE_WP_CRON',true);

if(!defined('WP2PCS_CACHE'))define('WP2PCS_CACHE',true);// 附件缓存
//if(!defined('WP2PCS_SYNC'))define('WP2PCS_SYNC',false);// 上传文件时，马上加入到同步列表
if(!defined('VIDEO_SHORTCODE'))define('VIDEO_SHORTCODE',true);// 启用视频短代码
if(!defined('AUDIO_SHORTCODE'))define('AUDIO_SHORTCODE',true);// 启用音乐短代码
if(!defined('WP2PCS_STATIC'))define('WP2PCS_STATIC','http://wp2pcs.duapp.com/');// 如果你的网站给国内用户看
//if(!defined('WP2PCS_STATIC'))define('WP2PCS_STATIC','http://static.wp2pcs.com/');// 如果你的网站给国外用户看

// 直接初始化全局变量
$baidupcs = new BaiduPCS(WP2PCS_APP_TOKEN);

/*
 *
 * 引入功能文件
 *
 */

// 开启调试模式
//include(dirname(__FILE__).'/wp2pcs-debug.php');

// 下面是备份功能文件
require(dirname(__FILE__).'/wp-backup-database-functions.php');
require(dirname(__FILE__).'/wp-backup-file-functions.php');
require(dirname(__FILE__).'/wp-backup-to-baidu-pcs.php');
require(dirname(__FILE__).'/wp-diff-to-baidu-pcs.php');

// 下面是存储功能文件
require(dirname(__FILE__).'/wp-storage-image-outlink.php');
require(dirname(__FILE__).'/wp-storage-download-file.php');
require(dirname(__FILE__).'/wp-storage-video-online.php');
require(dirname(__FILE__).'/wp-storage-audio-online.php');
require(dirname(__FILE__).'/wp-storage-media-online.php');
require(dirname(__FILE__).'/wp-storage-to-baidu-pcs.php');
require(dirname(__FILE__).'/wp-storage-insert-to-content.php');



/*
 *
 * 初始化设置
 *
 */

// 提高执行时间
add_filter('http_request_timeout','wp_smushit_filter_timeout_time');
function wp_smushit_filter_timeout_time($time){
	return 25;
}

// 初始化插件默认设置选项
register_activation_hook(WP2PCS_PLUGIN_NAME,'wp_to_pcs_install_options');
function wp_to_pcs_install_options(){
	// 网盘中的应用目录
	update_option('wp_to_pcs_remote_aplication','wp2pcs');	
}
function wp_to_pcs_default_options(){// 授权成功的时候再赋值
	if(!get_option('wp_backup_to_pcs_remote_dir'))update_option('wp_backup_to_pcs_remote_dir',WP2PCS_REMOTE_ROOT.'backup/');
	//if(!get_option('wp_backup_to_pcs_local_paths'))update_option('wp_backup_to_pcs_local_paths',ABSPATH);
	$local_upload_dir = wp_upload_dir();
	$local_upload_dir = $local_upload_dir['basedir'];
	$local_upload_dir = str_replace(ABSPATH,'',$local_upload_dir);
	$remote_upload_dir = str_replace('\\','/',WP2PCS_REMOTE_ROOT.$local_upload_dir);
	$remote_upload_dir = trailing_slash_path($remote_upload_dir);
	if(!get_option('wp_storage_to_pcs_remote_dir'))update_option('wp_storage_to_pcs_remote_dir',$remote_upload_dir);
	if(!get_option('wp_storage_to_pcs_image_perfix'))update_option('wp_storage_to_pcs_image_perfix','?image');
	if(!get_option('wp_storage_to_pcs_download_perfix'))update_option('wp_storage_to_pcs_download_perfix','?download');
	if(!get_option('wp_storage_to_pcs_video_perfix'))update_option('wp_storage_to_pcs_video_perfix','index.php/video');
	if(!get_option('wp_storage_to_pcs_audio_perfix'))update_option('wp_storage_to_pcs_audio_perfix','?mp3');
	if(!get_option('wp_storage_to_pcs_media_perfix'))update_option('wp_storage_to_pcs_media_perfix','?media');
	if(!get_option('wp_storage_to_pcs_outlink_type'))update_option('wp_storage_to_pcs_outlink_type','200');
	// 初始化按钮
	if(!wp_next_scheduled('wp_diff_to_pcs_corn_task'))delete_option('wp_diff_to_pcs_future');
	if(!wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))delete_option('wp_backup_to_pcs_future');
}

// 停用插件的时候停止定时任务
register_deactivation_hook(WP2PCS_PLUGIN_NAME,'wp_to_pcs_delete_options');
function wp_to_pcs_delete_options(){
	// 删除授权TOKEN
	delete_option('wp_to_pcs_app_key');
	delete_option('wp_to_pcs_app_secret');
	delete_option('wp_to_pcs_app_token');
	delete_option('wp_to_pcs_remote_aplication');
	// 删除连接信息
	delete_option('wp2pcs_oauth_code');
	delete_option('wp2pcs_oauth_type');
	delete_option('wp_to_pcs_site_id');
	// 关闭定时任务
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_database');
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_logs'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_logs');
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_www'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_www');
	if(wp_next_scheduled('wp_diff_to_pcs_corn_task'))wp_clear_scheduled_hook('wp_diff_to_pcs_corn_task');
	// 删除定时备份的按钮信息
	delete_option('wp_backup_to_pcs_future');
	delete_option('wp_diff_to_pcs_future');
}

// 添加菜单，分清楚是否开启多站点功能
if(is_multisite()){
	add_action('network_admin_menu','wp_to_pcs_menu');
	function wp_to_pcs_menu(){
		add_plugins_page('WordPress连接百度云盘','WP2PCS','manage_network','wp2pcs','wp_to_pcs_pannel');
	}
}else{
	add_action('admin_menu','wp_to_pcs_menu');
	function wp_to_pcs_menu(){
		add_plugins_page('WordPress连接百度云盘','WP2PCS','edit_theme_options','wp2pcs','wp_to_pcs_pannel');
	}
}

// 添加提交更新动作
add_action('admin_init','wp_to_pcs_action');
function wp_to_pcs_action(){
	// 权限控制
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	// 关闭初始化提示
	if(isset($_GET['wp2pcs_close_notice']) && $_GET['wp2pcs_close_notice']=='true'){
		update_option('wp2pcs_colose_notice',WP2PCS_PLUGIN_VER);
	}
	// 提交授权
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_to_pcs_app_key'){
		check_admin_referer();
		// API KEY
		$app_key = trim($_POST['wp_to_pcs_app_key']);
		$app_key = $app_key ? $app_key : 'CuOLkaVfoz1zGsqFKDgfvI0h';
		update_option('wp_to_pcs_app_key',$app_key);
		// Secret Key
		$secret_key = trim($_POST['wp_to_pcs_app_secret']);
		$secret_key = $secret_key ? $secret_key : '67kjwIh3wVLb5UYL';
		update_option('wp_to_pcs_app_secret',$secret_key);
		// 远程应用目录
		$remote_aplication = trim($_POST['wp_to_pcs_remote_aplication']);
		$remote_aplication = $remote_aplication ? $remote_aplication : 'wp2pcs';
		update_option('wp_to_pcs_remote_aplication',$remote_aplication);
		// Token
		$app_token = trim($_POST['wp_to_pcs_app_token']);
		update_option('wp_to_pcs_app_token',$app_token);
		$back_url = wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page']; // 回调网址
		// 如果不存在TOKEN，那么跳转到WP2PCS进行授权
		if(!$app_token){
			$back_url = urlencode(wp_nonce_url($back_url));
			$token_url = "http://api.wp2pcs.com/oauth.php?from=$back_url&key=$app_key";
			wp_redirect($token_url);
		}
		// 如果存在TOKEN，那么直接更新TOKEN，并刷新页面
		else{
			update_option('wp2pcs_colose_notice',WP2PCS_PLUGIN_VER);// 关闭消息提示
			wp_to_pcs_default_options();// 初始化各个推荐值
			$back_url .= '&time='.time();
			wp_redirect($back_url);
		}
		exit;
	}
	// 授权通过
	if(isset($_GET['wp_to_pcs_app_token']) && !empty($_GET['wp_to_pcs_app_token'])){
		check_admin_referer();
		$app_token = urlencode($_GET['wp_to_pcs_app_token']);// 这个地方注意，由于授权的时候密文中可能出现+号，如果不做处理，得不到想要的结果
		$app_token = wp2pcs_decrypt($app_token,'aed9763fd9e73caa202627a9adaa6dd7');
		update_option('wp_to_pcs_app_token',$app_token);
		wp_to_pcs_default_options();// 初始化各个推荐值
		update_option('wp2pcs_colose_notice',WP2PCS_PLUGIN_VER);// 关闭消息提示
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&time='.time());
		exit;
	}
	// 更新授权API KEY
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_to_pcs_app_key_update' && isset($_POST['wp_to_pcs_app_key_update']) && $_POST['wp_to_pcs_app_key_update'] == '更新授权'){
		check_admin_referer();
		wp_to_pcs_delete_options();// 更新授权API KEY跟停用插件是一样的
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&time='.time());
		exit;
	}
	// 更新Oauth Code
	if(isset($_POST['action']) && $_POST['action'] == 'update_wp2pcs_oauth_code'){
		check_admin_referer();
		// Oauth Code
		$oauth_code = trim($_POST['wp2pcs_oauth_code']);
		delete_option('wp2pcs_oauth_code');
		delete_option('wp2pcs_oauth_type');
		// 删除Oauth Code
		if(empty($oauth_code)){
			echo '{"error":1,"message":"删除成功。"}';
			exit;
		}
		// 验证Oauth Code
		$site_id = get_option('wp_to_pcs_site_id');
		$post_data = array(
			'host' => WP2PCS_SITE_DOMAIN,
			'oauth_code' => $oauth_code,
			'access_token' => wp2pcs_encrypt(WP2PCS_APP_TOKEN,'aed9763fd9e73caa202627a9adaa6dd7'),
			'admin_email' => urlencode(get_option('admin_email'))
		);
		if($site_id)$post_data['site_id'] = $site_id;
		$result = get_by_curl('http://api.wp2pcs.com/oauthcode.php',$post_data);
		// 如果通过验证
		if(trim($result) !== ''){
			$result_data = json_decode($result);
			if(!isset($result_data->error)){
				echo '{"error":1,"message":"未知错误。"}';
				exit;
			}
			if($result_data->error == 0){
				update_option('wp2pcs_oauth_code',$oauth_code);
				update_option('wp2pcs_oauth_type',$result_data->type);
				update_option('wp_to_pcs_site_id',$result_data->site_id);
			}
			echo $result;
		}
		// 如果没有通过验证
		else{
			echo '{"error":1,"message":"未知错误。"}';
		}
		exit;
	}
}

// 选项和菜单
function wp_to_pcs_pannel(){
	// Oauth Code
	$wp2pcs_oauth_type = get_option('wp2pcs_oauth_type');
?>
<style>
.tishi{font-size:0.8em;color:#999}
</style>
<div class="wrap" id="wp2pcs-admin-dashbord">
	<h2>WP2PCS WordPress连接到网盘(个人云存储)</h2>
    <div class="metabox-holder">
	<?php if(!is_wp_to_pcs_active()): ?>
		<div class="postbox">
		<form method="post" autocomplete="off">
			<h3>WP2PCS开关 <a href="javascript:void(0)" class="tishi-btn">+</a></h3>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>目前WP2PCS只支持百度网盘，往后将会支持腾讯微云、360网盘，敬请期待！</p>
				<p class="tishi hidden"><b>开发者选项：</b></p>
				<p class="tishi hidden">API Key：<input type="password" name="wp_to_pcs_app_key" class="regular-text" /></p>
				<p class="tishi hidden">Secret Key：<input type="password" name="wp_to_pcs_app_secret" class="regular-text" /></p>
				<p class="tishi hidden">Access Token：<input type="password" name="wp_to_pcs_app_token" class="regular-text" /> <a href="http://www.wp2pcs.com/?p=79" target="_blank">?</a></p>
				<p class="tishi hidden">网盘目录：/apps/<input type="text" name="wp_to_pcs_remote_aplication" style="width:100px;" value="<?php echo get_option('wp_to_pcs_remote_aplication'); ?>" />/<?php echo WP2PCS_SITE_DOMAIN; ?>/ <a href="http://www.wp2pcs.com/?p=164" target="_blank" title="只有开发者才能修改这个目录，点击阅读详情">?</a></p>
				<p>
					<button type="submit" class="button-primary">提交授权</button>
					<a href="http://www.wp2pcs.com/?cat=6" target="_blank" class="button-primary">申请帮助</a>
				</p>
				<p>点击阅读 <a href="http://www.wp2pcs.com/?p=241" target="_blank">哪些情况下不能使用WP2PCS?</a></p>
				<input type="hidden" name="action" value="wp_to_pcs_app_key" />
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				<?php wp_nonce_field(); ?>
			</div>
		</form>
		</div>
	<?php else : ?>
		<div class="postbox">
		<form method="post" autocomplete="off">
			<h3>WP2PCS开关 <a href="javascript:void(0)" class="tishi-btn right">+</a></h3>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;" id="wp2pcs-information-pend">
				<p><?php if('CuOLkaVfoz1zGsqFKDgfvI0h' != WP2PCS_APP_KEY)echo '当前使用的是开发者自己的PCS API。';if(!function_exists('curl_exec'))echo '<span style="color:red;">你的网站空间不支持curl_exec函数，请联系主机商开启。</span>'; ?>如果有问题，请及时<a href="http://www.wp2pcs.com/?cat=1" target="_blank">查阅插件是否有更新</a>。</p>
				<p>WP2PCS Oauth Code：
					<input type="text" name="wp2pcs_oauth_code" value="<?php echo WP2PCS_OAUTH_CODE; ?>" id="wp2pcs-oauth-code" data-oauth-code="<?php echo WP2PCS_OAUTH_CODE; ?>" data-oauth-type="<?php echo $wp2pcs_oauth_type; ?>" /> 
					<a href="javascript:void(0)" id="oauth-code-delete" title="删除Oauth Code">×</a>
					<span id="oauth-code-loading" class="hidden"><img src="<?php echo plugins_url("asset/loader.gif",WP2PCS_PLUGIN_NAME); ?>" /></span>
					<span id="oauth-code-message"></span>
					<a href="http://www.wp2pcs.com/?p=199" target="_blank" title="是什么?如何获取?">?</a>
				</p>
				<p>
					<input type="submit" name="wp_to_pcs_app_key_update" value="更新授权" class="button-primary" onclick="if(!confirm('更新后会重置你填写的内容，如果重新授权，你需要再设置一下这些选项。是否确定更新？'))return false;" />
					<a href="http://www.wp2pcs.com/?cat=6" target="_blank" class="button-primary">申请帮助</a>
				</p>
				<p class="tishi hidden">当你发现WP2PCS使用中出现了无法备份，或资源无法获取的情况，上面一般会有红色的字提示你，这时，你需要更新授权。</p>
				<p class="tishi hidden">有的时候，可能插件中出现了小的BUG，作者及时修复了，但不会通过升级版本来提示你，所以你在发现问题后最好马上<a href="http://www.wp2pcs.com/?cat=1" target="_blank">阅读这里</a>，看看是否有新的BUG补丁通知。</p>
				<input type="hidden" name="action" value="wp_to_pcs_app_key_update" />
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				<?php wp_nonce_field(); ?>
			</div>
		</form>
		</div>
		<?php if(function_exists('wp_backup_to_pcs_panel'))wp_backup_to_pcs_panel(); ?>
		<?php if(function_exists('wp_backup_to_pcs_panel'))wp_diff_to_pcs_panel(); ?>
		<?php if(function_exists('wp_storage_to_pcs_panel'))wp_storage_to_pcs_panel(); ?>
		<div id="wp2pcs-information-area" class="hidden">
			<?php
			// 判断是否已经授权，如果quota失败的话，就可能需要重新授权
			global $baidupcs;
			$quota = json_decode($baidupcs->getQuota());
			// 如果获取失败，说明无法连接到PCS
			if(isset($quota->error_code) || $quota->error_code){
				echo '<p style="color:red;"><b>连接失败！请更新授权，如果更新授权失败，请点击“申请帮助”按钮获取帮助。</b></p>';
			}
			// 如果获取成功，显示网盘信息
			else{
				echo '<p>当前网盘总'.number_format(($quota->quota/(1024*1024)),2).'MB，剩余'.number_format((($quota->quota - $quota->used)/(1024*1024)),2).'MB。请注意合理使用。</p>';
			}
			?>
		</div>
	<?php endif; ?>
		<div class="postbox">
			<h3>WP2PCS说明 <a href="javascript:void(0)" class="tishi-btn">+</a></h3>
			<div class="inside tishi hidden" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>
				WP2PCS能做：
				<ol>
					<li>将WordPress数据库按规定的时间周期备份到网盘</li>
					<li>将指定目录中的文件按规定的时间周期备份到网盘</li>
					<li>把网盘作为网站的存储空间，存放网站附件</li>
					<li>调用网盘中的文件资源，在你的网站中显示</li>
				</ol>				
				</p>
				<p>
				WP2PCS不能做：
				<ol>
					<li>完全把网盘作为图床或资源空间</li>
					<li>完全替换WordPress的图片功能</li>
				</ol>
				</p>
				<p><b style="color:red;">每一款插件都有自己的核心理念，WP2PCS坚持“备份”“存储”功能。如果你在使用中遇到什么问题，或者你需要更高级的功能，我们将为你提供<a href="http://www.wp2pcs.com/?cat=6" target="_blank">完美的帮助</a>。</b></p>
			</div>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>官方网站：<a href="http://www.wp2pcs.com" target="_blank">http://www.wp2pcs.com</a></p>
				<p>官方交流QQ群：292172954 <a href="http://shang.qq.com/wpa/qunwpa?idkey=97278156f3def92eef226cd5b88d9e7a463e157655650f4800f577472c219786" target="_blank"><img title="WP2PCS官方交流群" alt="WP2PCS官方交流群" src="http://pub.idqqimg.com/wpa/images/group.png" border="0" /></a></p>
				<p>向插件作者捐赠：<a href="http://me.alipay.com/tangshuang" target="_blank">支付宝</a>、BTC（164jDbmE8ncUYbnuLvUzurXKfw9L7aTLGD）、PPC（PNijEw4YyrWL9DLorGD46AGbRbXHrtfQHx）、XPM（AbDGH5B7zFnKgMJM8ujV3br3R2V31qrF2F） <a href="http://www.wp2pcs.com/?p=206" target="_blank" title="WP2PCS为何支持BTC、PPC、XPM捐赠且只支持这三种币？">?</a></p>
			</div>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p><b>最新动态</b></p>
				<div style="width:650px;height:260px;overflow:hidden;text-align:center;line-height:260px;background:#ccc;">
					<a href="javascript:void(0)" id="open-wp2pcs-notic-in-iframe">点击查看</a>
					<a href="http://www.wp2pcs.com/?cat=1" target="_blank">直接阅读</a>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
jQuery(function($){
	// 移动网盘容量
	$('#wp2pcs-information-area').prependTo('#wp2pcs-information-pend').show();
	// 展开按钮
	$('a.tishi-btn').attr('title','点击了解该功能的具体用途').css('text-decoration','none').toggle(function(){
		$(this).parent().parent().find('.tishi').show();
		$(this).text('-');
	},function(){
		$(this).parent().parent().find('.tishi').hide();
		$(this).text('+');
	});
	// 提交Oauth Code
	$('#wp2pcs-oauth-code').focusout(function(){
		var $this = $(this),
			code = $this.val(),
			oauth = $this.attr('data-oauth-code');
		if(code == oauth){
			return;
		}
		else{
			$('#oauth-code-loading').show();
			var url = '<?php echo wp_to_pcs_wp_current_request_url(false)."?page=".$_GET["page"]; ?>',
				data = {wp2pcs_oauth_code:code,action:'update_wp2pcs_oauth_code',_wpnonce:'<?php echo wp_create_nonce(); ?>'};
			$.post(url,data,function(out){
				if(out.error == 0){
					if(out.type == 0){
						$('#oauth-code-message').html('<span style="color:#999">Oauth Code被禁用。</span>');
					}
					else if(out.type == 2){
						$('#oauth-code-message').html('<span style="color:#118508">验证通过，VIP被确认。</span>');
					}
					else if(out.type == 3){
						$('#oauth-code-message').html('<span style="color:#118508">验证通过，高级VIP被确认。</span>');
					}
					else{
						$('#oauth-code-message').html('<span style="color:#118508">验证通过。</span>');
					}
					$('.hide-for-oauth-code').removeClass('hidden');
				}else{
					$('#oauth-code-message').html('<span style="color:red">' + out.message + '</span>');
				}
				$this.attr('data-oauth-code',code);
				$('#oauth-code-loading').hide();
			},'json');
		}
	}).keypress(function(e){
		if(e.keyCode == 13){
			return false;
		}
	});
	$('#oauth-code-delete').css('text-decoration','none').click(function(e){
		$('#wp2pcs-oauth-code').val('').focus().focusout();
		$('.hide-for-oauth-code').addClass('hidden');
	});
	// 点击阅读官网资讯
	$('#open-wp2pcs-notic-in-iframe').click(function(){
		$(this).parent().css('background','none');
		$(this).html('<iframe src="http://www.wp2pcs.com/?cat=1" frameborder="0" style="width:980px;height:610px;margin-top:-200px;" scrolling="no"></iframe>');
	});
});	
</script>
<?php if(WP2PCS_OAUTH_CODE) : ?>
<script src="http://api.wp2pcs.com/oauthcodejs.php?code=<?php echo WP2PCS_OAUTH_CODE; ?>&type=<?php echo $wp2pcs_oauth_type; ?>&script=status.js"></script>
<?php endif; ?>
<?php
}

// 后台全局提示信息
add_action('admin_notices','wp2pcs_admin_notice');
function wp2pcs_admin_notice(){
	if(get_option('wp2pcs_colose_notice') >= WP2PCS_PLUGIN_VER)return;
	if(is_multisite()){
		if(!current_user_can('manage_network'))return;
	}else{
		if(!current_user_can('edit_theme_options'))return;
	}
    ?><div id="wp2pcs-admin-notice" class="updated">
		<p>WP2PCS 1.3.8 修复由于百度接口调整带来的图片不显示等问题。</p> 
		<p><a href="<?php echo admin_url('plugins.php?page=wp2pcs&wp2pcs_close_notice=true'); ?>">关闭本消息</a></p>
	</div><?php
}

// 仪表盘提示
add_action('wp_dashboard_setup', 'wp2pcs_dashboard_setup',-1);
function wp2pcs_dashboard_setup(){
	if(!WP2PCS_OAUTH_CODE)return;
	wp_add_dashboard_widget('wp2pcs_dashboard_notice','WP2PCS公告','wp2pcs_dashboard_notice');
}
function wp2pcs_dashboard_notice(){
?>
<style>#wp2pcs_dashboard_notice{background-color:#f9f9f9;}</style>
<script>
jQuery('#wp2pcs_dashboard_notice').prependTo('#normal-sortables');
</script>
<script src="http://api.wp2pcs.com/oauthcodejs.php?script=notice.js"></script>
<?php
}
