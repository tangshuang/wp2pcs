<?php

/*
Plugin Name: WP2PCS BACKUP(WP备份到网盘)
Plugin URI: http://www.wp2pcs.com/
Description: 本插件帮助网站站长将网站和百度网盘连接。网站的数据库、日志、网站程序文件（包括wordpress系统文件、主题、插件、上传的附件等）一并上传到百度云盘，站长可以根据自己的习惯定时备份，让你的网站数据不再丢失！
Version: 1.0
Author: 唐霜
Author URI: http://www.tangshuang.net
*/

/*
 *
 * 初始化数据
 *
 */

// 初始化固定值常量
define('WP2PCS_BACKUP_PLUGIN_NAME',__FILE__);

// 包含一些必备的函数和类，以提供下面使用
require(dirname(__FILE__).'/libs/FunctionsLib.php');
require(dirname(__FILE__).'/libs/BaiduPCS.class.php');

// 包含配置文件
require(dirname(__FILE__).'/wp2pcs-backup-config.php');

// 功能文件
require(dirname(__FILE__).'/wp2pcs.backup.database.functions.php');
require(dirname(__FILE__).'/wp2pcs.backup.file.functions.php');
require(dirname(__FILE__).'/wp2pcs.backup.baidupcs.php');
require(dirname(__FILE__).'/wp2pcs.diff.baidupcs.php');

// 直接初始化全局变量
$BaiduPCS = new BaiduPCS(WP2PCS_BAIDU_APP_TOKEN);

// 授权成功的时候再赋值
register_activation_hook(WP2PCS_BACKUP_PLUGIN_NAME,'wp2pcs_backup_default_options');
function wp2pcs_backup_default_options(){
	// 初始化定时备份按钮
	if(!wp_next_scheduled('wp_diff_to_pcs_corn_task'))delete_option('wp_diff_to_pcs_future');
	if(!wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))delete_option('wp_backup_to_pcs_future');
}

// 停用插件的时候停止定时任务
register_deactivation_hook(WP2PCS_BACKUP_PLUGIN_NAME,'wp2pcs_backup_delete_options');
function wp2pcs_backup_delete_options(){
	// 删除授权TOKEN
	delete_option('wp2pcs_baidu_app_token');
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
	add_action('network_admin_menu','wp2pcs_menu');
	function wp2pcs_menu(){
		add_plugins_page('WordPress备份到云盘','WP2PCS备份','manage_network','wp2pcs','wp2pcs_pannel');
	}
}else{
	add_action('admin_menu','wp2pcs_menu');
	function wp2pcs_menu(){
		add_plugins_page('WordPress备份到云盘','WP2PCS备份','edit_theme_options','wp2pcs','wp2pcs_pannel');
	}
}

// 添加提交更新动作
add_action('admin_init','wp2pcs_init_action');
function wp2pcs_init_action(){
	// 权限控制
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	// 提交授权
	if(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp2pcs_oauth_redirect'){
		check_admin_referer();
		$back_url = wp2pcs_get_current_url(false).'?page='.$_POST['page'];
		$back_url = urlencode(wp_nonce_url($back_url));
    $oauth_type = $_POST['oauth_type'];
    if($oauth_type == 'baidu') {
	    $token_url = "http://api.wp2pcs.com/oauth.php?from=$back_url&key=CuOLkaVfoz1zGsqFKDgfvI0h";
    }
    else {
      wp_die('目前只开放了百度云授权。');
    }
  	wp_redirect($token_url);
		exit;
	}
	// 授权通过
	if(isset($_GET['wp2pcs_baidu_app_token']) && !empty($_GET['wp2pcs_baidu_app_token'])){
		check_admin_referer();
		$baidu_app_token = urlencode($_GET['wp2pcs_baidu_app_token']);// 这个地方注意，由于授权的时候密文中可能出现+号，如果不做处理，得不到想要的结果
		$baidu_app_token = wp2pcs_decrypt($baidu_app_token,'aed9763fd9e73caa202627a9adaa6dd7');
		update_option('wp2pcs_baidu_app_token',$baidu_app_token);
		wp_redirect(wp2pcs_get_current_url(false).'?page='.$_GET['page'].'&time='.time());
		exit;
	}
	// 更新授权API KEY
	if(@$_POST['page'] == @$_GET['page'] && @$_POST['action'] == 'wp2pcs_flush_token'){
		check_admin_referer();
		$oauth_type = $_POST['oauth_type'];
    if($oauth_type == 'baidu') {
      delete_option('wp2pcs_baidu_app_token');
    }
    else if($oauth_type == 'tencent') {
      delete_option('wp2pcs_tencent_app_token');
    }
		wp_redirect(wp2pcs_get_current_url(false).'?page='.$_GET['page'].'&time='.time());
		exit;
	}
}

// 选项和菜单
function wp2pcs_pannel(){
?>
<style>
.tishi{font-size:0.8em;color:#999}
</style>
<div class="wrap" id="wp2pcs-admin-dashbord">
	<h2>WP2PCS BACKUP WordPress备份到网盘</h2>
    <div class="metabox-holder">
	  <?php if(!get_option('wp2pcs_baidu_app_token')): ?>
		<div class="postbox">
		<form method="post" autocomplete="off">
			<h3>WP2PCS开关 <a href="javascript:void(0)" class="tishi-btn">+</a></h3>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>目前WP2PCS只支持百度网盘，往后将会支持腾讯微云、360网盘，敬请期待！</p>
				<p>
					<button type="submit" class="button-primary" name="oauth_type" value="baidu">百度授权</button>
				</p>
				<p class="tishi hidden">点击阅读 <a href="http://www.wp2pcs.com/?p=241" target="_blank">哪些情况下不能使用WP2PCS?</a></p>
				<input type="hidden" name="action" value="wp2pcs_oauth_redirect" />
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
				<p>
					<button type="submit" name="oauth_type" value="baidu" class="button-primary">更新百度授权</button>
				</p>
				<p class="tishi hidden">当你发现WP2PCS使用中出现了无法备份，或资源无法获取的情况，上面一般会有红色的字提示你，这时，你需要更新授权。</p>
				<p class="tishi hidden">有的时候，可能插件中出现了小的BUG，作者及时修复了，但不会通过升级版本来提示你，所以你在发现问题后最好马上<a href="http://www.wp2pcs.com/?cat=1" target="_blank">阅读这里</a>，看看是否有新的BUG补丁通知。</p>
				<input type="hidden" name="action" value="wp2pcs_flush_token" />
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				<?php wp_nonce_field(); ?>
			</div>
		  </form>
		</div>
		<?php if(function_exists('wp_backup_to_pcs_panel'))wp_backup_to_pcs_panel(); ?>
		<?php if(function_exists('wp_diff_to_pcs_panel'))wp_diff_to_pcs_panel(); ?>
		<div id="wp2pcs-information-area" class="hidden">
			<?php
			// 判断是否已经授权，如果quota失败的话，就可能需要重新授权
			global $BaiduPCS;
			$quota = json_decode($BaiduPCS->getQuota());
			// 如果获取失败，说明无法连接到PCS
			if(isset($quota->error_code) || $quota->error_code || (int)$quota->quota == 0){
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
				<p><b style="color:red;">每一款插件都有自己的核心理念，WP2PCS坚持“备份”“存储”功能。如果你在使用中遇到什么问题，或者你需要更高级的功能，我们将为你提供<a href="http://www.wp2pcs.com/?cat=6" target="_blank">完美的帮助</a>。</b></p>
			</div>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>官方网站：<a href="http://www.wp2pcs.com" target="_blank">http://www.wp2pcs.com</a></p>
				<p>官方QQ交流群：292172954 <a href="http://shang.qq.com/wpa/qunwpa?idkey=97278156f3def92eef226cd5b88d9e7a463e157655650f4800f577472c219786" target="_blank"><img title="WP2PCS官方交流群" alt="WP2PCS官方交流群" src="http://pub.idqqimg.com/wpa/images/group.png" border="0" /></a></p>
				<p>向插件作者捐赠：BitCoin（14r1iUQYRPaYDnNH9Q1xnzwn4Vj93mv1na）</p>
			</div>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p><b>最新动态</b></p>
				<div style="width:650px;height:260px;overflow:hidden;text-align:center;line-height:260px;background:#ccc;">
					<a href="javascript:void(0)" id="open-wp2pcs-notic-in-iframe">点击查看</a>
					<a href="http://www.wp2pcs.com/?cat=1" target="_blank">直接阅读</a>
				</div>
			</div>
      <div class="inside tishi hidden" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
        <p><strong>调试信息：</strong></p>
        <p>Token: <?php echo WP2PCS_BAIDU_APP_TOKEN; ?></p>
        <p>ZipArchive: <?php if(class_exists('ZipArchive'))echo 'OK';else echo 'Error'; ?></p>
        <p>临时目录: <?php if(!file_exists(WP2PCS_TMP_PATH))echo WP2PCS_TMP_PATH.'不存在';elseif(CAN_WRITE)echo 'OK';else echo '没有可写权限'; ?></p>
        <p>文件列表：<?php $local_files_record = trailing_slash_path(WP2PCS_TMP_PATH,IS_WIN).'local_files.php';if(file_exists($local_files_record)){if(is_really_writable($local_files_record))echo 'OK';else echo '不可写';}else '不存在'; ?></p>
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
	// 点击阅读官网资讯
	$('#open-wp2pcs-notic-in-iframe').click(function(){
		$(this).parent().css('background','none');
		$(this).html('<iframe src="http://www.wp2pcs.com/?cat=1" frameborder="0" style="width:980px;height:610px;margin-top:-200px;" scrolling="no"></iframe>');
	});
});	
</script>
<?php
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
