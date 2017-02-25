# WP2PCS

将网站数据和文件定时自动备份到百度云盘的WordPress插件。

## Dependences

环境要求：

* php 5.6+
* wordpress 4.0+
* curl (full module)
* rewrite mod if you want to use permalink (nginx is not suite for virtual file permalink)

使用老版本 [v1.5.5](https://github.com/tangshuang/WP2PCS/releases/tag/v1.5.5) please.

## Install

先下载整个wp2pcs到你的电脑，然后处理[oauth-server](../oauth-server)，处理完之后修改config.php，把里面的`WP2PCS_API_URL`修改为你的授权服务器的域名，把`WP2PCS_BAIDUPCS_REMOTE_DIR`改为百度开发者中心的应用名称。保存之后，上传wp2pcs目录到你的WordPress插件目录下，然后到WordPress后台启用wp2pcs插件。

## Active

在WordPress后台启用wp2pcs插件之后，你需要进行百度账号授权。进入后台"设置-WP2PCS"，点击“百度授权”按钮。页面跳转授权之后，就可以对定时备份进行设置。
