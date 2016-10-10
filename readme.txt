=== WP2PCS （WordPress连接到云盘） ===
Contributors: 否子戈
Donate link: http://www.wp2pcs.com
Tags:wp2pcs, 数据备份, 资源调用, baidu, cloud storage, PCS, 百度网盘
Requires at least: 3.5.1
Tested up to: 4.6.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

备份WordPress到云盘，调用云盘内的资源到网站使用。

== Description ==

把WordPress和云盘（PCS，个人云存储）连接在一起的插件。它的两项基本功能：将wordpress的数据库、文件<strong>定时自动备份</strong>到云盘，以防止由于过失而丢失了网站数据；把云盘作为网站的后备箱，<strong>存放</strong>图片、附件，解决网站空间不够用的烦恼，可以在网站内<strong>直接调用</strong>云盘上的文件。目前只支持百度云盘。

开发与探讨：http://github.com/tangshuang/WP2PCS
使用中如有疑问请加官方唯一QQ群(292172954)参与讨论。
技术问题请到官网查看文档，在对应的文档下方留言。

<strong>说明</strong>

1、目前本插件只支持百度网盘。<br />
2、本插件完全免费，同时提供付费服务，满足不同用户的需求。<br />

<strong>不适用范围</strong>

* 超大型网站（打包压缩后超过G）
* 尽可能不使用在开启MULTISITE的多站点网站
* 没有读写权限或读写权限受限制的空间（如BAE、SAE）
* 服务器memory limit, time limit比较小，又不能自己修改的
* 免费主机、海外主机等性能差或与PCS通信弱的主机

== Installation ==

1、把wp2pcs文件夹上传到/wp-content/plugins/目录<br />
2、在后台插件列表中启用它<br />
3、在“插件-WP2PCS”菜单中，点击授权按钮，等待授权跳转<br />
4、如果授权成功，你会进入到插件的使用页面。<br />
5、初始化所有信息。<br />
6、如果授权不成功，点击更新按钮重新授权。

== Frequently Asked Questions ==


== Screenshots ==

== Changelog ==

= 1.6.1 =
精简插件，成为一个轻量级的备份插件，删除广告、通知等不必要的功能，提供一个资源调用开关选项。

== Upgrade Notice ==

= 1.6.1 =
精简插件，成为一个轻量级的备份插件，删除广告、通知等不必要的功能，提供一个资源调用开关选项。
