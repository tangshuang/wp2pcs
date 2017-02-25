# WP2PCS的授权服务器代码

先在你的服务器上面创建一个网站，绑定域名，比如说`api.papapa.com`。

然后修改config.php里面的内容，百度的PCS API要你自己申请。

修改完之后，上传到`api.papapa.com`，访问以下`api.papapa.com/client-baidu-oauth.php`试试看，如果能够访问得到，说明服务器搭建好了。

修改wp2pcs插件的config.php中的`WP2PCS_API_URL`的域名为`api.papapa.com`，这样当你在你的WordPress后台点击授权的时候，就会进入`api.papapa.com`进行授权的跳转。登录百度账号后跳转回你的WordPress后台，授权就成功了。

`api.papapa.com`可以作为你自己任何WordPress的授权服务器，你不需要搭建多个授权服务器，而只需要把wp2pcs插件上传到不同的WordPress去使用即可。

**注意**：不要公开给别人使用，wp2pcs就是这样被封掉的。作为开发者，更希望你用它来做备份。
