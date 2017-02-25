jQuery(function($){
  // 关闭文件信息框
  var $click = {};
  $click.media_file = false;
  function close_file_info() {
    $('#wp2pcs-manage-media-page-files .file-on-pcs').removeClass('selected');
    $('#wp2pcs-manage-media-page-file-info').hide();
  }
  $('#wp2pcs-manage-media-page-file-info .close').on('click',function(e){
    e.preventDefault();
    close_file_info();
  });
  $(document).on('click',function() {
    if(!$click.media_file) close_file_info();
    $click.media_file = false;
  });
  // 点击上传按钮
  $('#wp2pcs-manage-media-btn-upload').on('click',function(e){
    e.preventDefault();
    var $this = $(this),
        $upload_box = $('#wp2pcs-manage-media-page-upload');
    if($upload_box.is(':hidden')) {
      $('#wp2pcs-manage-media-page-content').hide();
      close_file_info();
      $upload_box.html('<iframe src="' + $this.attr('href') + '"></iframe>')
      $upload_box.fadeIn(500);
    }
    else {
      $upload_box.hide();
      $('#wp2pcs-manage-media-page-content').fadeIn(500);
    }
    return false;
  });
  // 顶部管理条固定位置
  function fixed_top_bar() {
    var $bar = $('#wp2pcs-manage-media-page-top-bar'),
        $content = $('#wp2pcs-manage-media-page-content'),
        width = $bar.width(),
        left = $content.offset().left;
    $bar.css({'position':'fixed','left':left + 'px','top':'30px','width':width + 'px'});
    $content.css({'padding-top':'70px'});
  }
  fixed_top_bar();
  // 点击文件区域
  $(document).on('click','#wp2pcs-manage-media-page-files .file-on-pcs',function(e){
    var $this = $(this),
        $file_info = $('#wp2pcs-manage-media-page-file-info'),
        root_dir = $('#wp2pcs-manage-media-page-check-root-dir').val(),
        file_format = $this.attr('data-file-format'),
        file_type = $this.attr('data-file-type'),
        file_name = $this.attr('data-file-name'),
        file_size = $this.attr('data-file-size'),
        file_path = $this.attr('data-file-path'),
        $child = $this.children(),
        file_url = $child.attr('data-url'),
        video_path = $child.attr('data-video-path'),
        site_id = $child.attr('data-site-id'),
        is_vip = $('#wp2pcs-manage-media-page-check-vip').val();
    if(site_id == undefined) site_id = '';
    $('#wp2pcs-manage-media-page-files .file-on-pcs').removeClass('selected');
    $this.addClass('selected');
    $file_info.find('.thumb').html('');
    if(file_format == 'dir') {
      return true;
    }
    else if(file_format == 'image') {
      $file_info.find('.thumb').html('<img src="' + file_url + '">');
      $file_info.find('.format').text('图像');
      $file_info.find('.code').text('<img src="' + file_url + '" class="wp2pcs-img">');
    }
    else if(file_format == 'music') {
      $file_info.find('.format').text('音乐');
      $file_info.find('.code').text('[audio src="' + file_url + '" poster="none" preload="none" loop="off" autoplay="off" data-site-id="' + site_id + '"]');
    }
    else if(file_format == 'video') {
      $file_info.find('.format').text('视频');
      if(is_vip) $file_info.find('.code').text('<iframe class="wp2pcs-video-player" width="480" height="360" data-stretch="uniform" data-autostart="false" data-image="" data-path="' + video_path + '" data-site-id="' + site_id + '"' + (root_dir ? ' data-root-dir="' + root_dir + '"' : '') + ' scrolling="no" frameborder="0"></iframe>');
      else $file_info.find('.code').text('[video width="" height="" src="' + file_url + '" poster="none" preload="none" loop="off" autoplay="off" data-site-id="' + site_id + '"]');
    }
    else {
      $file_info.find('.format').text('文件');
      $file_info.find('.code').text('<a href="' + file_url + '" class="wp2pcs-file">点击下载</a>');
    }
    $file_info.find('.name').text(file_name);
    if(file_size/(1024*1024*1024) > 1) {
      file_size = (file_size/(1024*1024*1024)).toFixed(2) + 'GB';
    }
    else if(file_size/(1024*1024) > 1) {
      file_size = (file_size/(1024*1024)).toFixed(2) + 'MB';
    }
    else if(file_size/1024 > 1) {
      file_size = (file_size/1024).toFixed(2) + 'KB';
    }
    else {
      file_size += 'B';
    }
    $file_info.find('.size').text(file_size);
    $file_info.find('.path').text('路径：' + file_path);
    $file_info.find('.url').html('网址：<a href="' + file_url + '" target="_blank">' + file_url + '</a>');
    $file_info.show();
    $click.media_file = true;
  });
  $('#wp2pcs-manage-media-page-file-info textarea.code').on('click',function(){
    $(this).select();
  });
  $('#wp2pcs-manage-media-page-top-bar').on('click',function() {
    $click.media_file = true;
  });
  // 下拉加载
  $(window).scroll(function(){
    var $window = $(window),
        scroll_top = $window.scrollTop(),
        screen_height = $window.height(),
        $pagenavi = $('#wp2pcs-manage-media-page-pagenavi'),
        $next = $pagenavi.find('a.next-page'),
        href = $next.attr('href'),
        loading = $pagenavi.attr('data-loading'),
        ajaxing = $pagenavi.attr('data-ajaxing');
    if($pagenavi.length > 0 && scroll_top + screen_height + 100 > $pagenavi.offset().top && href != undefined) {
    
    if(ajaxing == 'true') return;
    $pagenavi.attr('data-ajaxing','true');
    $.ajax({
      url : href,
      dataType : 'html',
      type : 'GET',
      timeout : 10000,
      beforeSend : function() {
        $pagenavi.html('<img src="' + loading + '">');
      },
      success : function(data) {
        var DATA = $(data),
            DATA = $('<code></code>').append(DATA),
            LIST = $('#wp2pcs-manage-media-page-files',DATA),
            NAVI = $('#wp2pcs-manage-media-page-pagenavi',DATA);
        $('#wp2pcs-manage-media-page-files').append(LIST.html());
        if(NAVI.find('a.next-page').length > 0) {
          $pagenavi.html(NAVI.html()).removeAttr('data-ajaxing');
        }
        else {
          $pagenavi.remove();
        }
      },
      error : function() {
        $pagenavi.html('<a href="' + href + '" class="next-page">下一页</a>').removeAttr('data-ajaxing');
      }
    });
    
    } // -- endif --
  });
  $(document).on('click','#wp2pcs-manage-media-page-pagenavi a.next-page',function(e){
    if($(this).parent().attr('data-ajaxing') != 'true') {
      e.preventDefault();
      $(window).scroll();
      return false;
    }
  });
  // 刷新按钮
  $('#wp2pcs-manage-media-btn-refresh').click(function(e){
    e.preventDefault();
    var $this = $(this),
        $body = $('#wp2pcs-manage-media-page-content'),
        href = $this.attr('href'),
        loading = $this.attr('data-loading'),
        ajaxing = $this.attr('data-ajaxing');
    $('#wp2pcs-manage-media-page-upload').hide();
    $('#wp2pcs-manage-media-page-content').fadeIn(500);
    close_file_info();
    if(ajaxing == 'true') return;
    $this.attr('data-ajaxing','true');
    $.ajax({
      url : href,
      dataType : 'html',
      type : 'GET',
      timeout : 10000,
      beforeSend : function() {
        $body.html('<img src="' + loading + '" style="display:block;margin: 0 auto;margin-top: 10%;">');
      },
      success : function(data) {
        var DATA = $(data),
            DATA = $('<code></code>').append(DATA),
            CONTENT = $('#wp2pcs-manage-media-page-content',DATA);
        $body.html(CONTENT.html());
        $this.removeAttr('data-ajaxing');
      },
      error : function() {
        $this.removeAttr('data-ajaxing');
        var cf = confirm('连接超时，强制刷新？');
        if(cf) {window.location.reload(false);}
      }
    });
  });

});
