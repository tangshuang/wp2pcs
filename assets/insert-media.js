jQuery.cookie = function(name, value, options) {
  if(typeof value != 'undefined') { // name and value given, set cookie
    options = options || {};
    if(value === null) {
      value = '';
      options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
      var date;
      if (typeof options.expires == 'number') {
        date = new Date();
        date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      } else {
        date = options.expires;
      }
      expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
    }
    var path = options.path ? '; path=' + options.path : '';
    var domain = options.domain ? '; domain=' + options.domain : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
  }
  else { // only name given, get cookie
    var cookieValue = null;
    if(document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        // Does this cookie string begin with the name we want?
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
};

jQuery(function($){
  // 点击帮助按钮
  $('#wp2pcs-insert-media-btn-help').on('click',function(){
    if($('#wp2pcs-insert-media-iframe-help').is(':hidden')) {
      $('#wp2pcs-insert-media-iframe-content,#wp2pcs-insert-media-iframe-upload').hide();
      $('#wp2pcs-insert-media-iframe-help').fadeIn(500);
    }
    else {
      $('#wp2pcs-insert-media-iframe-help,#wp2pcs-insert-media-iframe-upload').hide();
      $('#wp2pcs-insert-media-iframe-content').fadeIn(500);
    }
  });
  // 点击上传按钮
  $('#wp2pcs-insert-media-btn-upload').on('click',function(e){
    e.preventDefault();
    var $this = $(this),
        $upload_box = $('#wp2pcs-insert-media-iframe-upload');
    if($upload_box.is(':hidden')) {
      $('#wp2pcs-insert-media-iframe-content,#wp2pcs-insert-media-iframe-help').hide();
      $upload_box.html('<iframe src="' + $this.attr('href') + '"></iframe>')
      $upload_box.fadeIn(500);
    }
    else {
      $('#wp2pcs-insert-media-iframe-help,#wp2pcs-insert-media-iframe-upload').hide();
      $('#wp2pcs-insert-media-iframe-content').fadeIn(500);
    }
    return false;
  });
  // 点击文件区域
  $(document).on('click','#wp2pcs-insert-media-iframe-files .file-on-pcs:not(.file-type-dir)',function(e){
    if($(e.target).prop('tagName') == 'INPUT') return;
    var $this = $(this),
        $input = $this.children('input');
    $this.toggleClass('selected');
    if($this.hasClass('selected')) {
      $input.prop('checked',true);
      $('#wp2pcs-insert-media-btn-help').next('span.wp2pcs-insert-media-show-url').remove();
      $('#wp2pcs-insert-media-btn-help').after('<span class="wp2pcs-insert-media-show-url" style="float:left;margin-left:10px;"><input type="url" class="regular-text" value="' + $input.val() + '"></span>');
    }
    else {
      $input.prop('checked',false);
      $('#wp2pcs-insert-media-btn-help').next('span.wp2pcs-insert-media-show-url').remove();
    }
  });
  // 变化勾选状况
  $(document).on('change','#wp2pcs-insert-media-iframe-files .file-on-pcs input',function(){
    var $this = $(this),
        $box = $this.parent();
    if($this.prop('checked') == true) {
      $box.addClass('selected');
    }
    else {
      $box.removeClass('selected');
    }
  });
  // 勾选是否插入图片链接
  if($.cookie('wp2pcs-insert-media-iframe-check-imglink') == 'true' || $.cookie('wp2pcs-insert-media-iframe-check-imglink') === null) {
    $('#wp2pcs-insert-media-iframe-check-imglink').prop('checked',true);
  }
  else {
    $('#wp2pcs-insert-media-iframe-check-imglink').prop('checked',false);
  }
  $('#wp2pcs-insert-media-iframe-check-imglink').on('change',function(){
    $.cookie('wp2pcs-insert-media-iframe-check-imglink',$(this).prop('checked') ? 'true' : 'false');
  });
  // 勾选是否插入视频播放器
  if($.cookie('wp2pcs-insert-media-iframe-check-videoplay') == 'true' || $.cookie('wp2pcs-insert-media-iframe-check-videoplay') === null) {
    $('#wp2pcs-insert-media-iframe-check-videoplay').prop('checked',true);
  }
  else {
    $('#wp2pcs-insert-media-iframe-check-videoplay').prop('checked',false);
  }
  $('#wp2pcs-insert-media-iframe-check-videoplay').on('change',function(){
    $.cookie('wp2pcs-insert-media-iframe-check-videoplay',$(this).prop('checked') ? 'true' : 'false');
  });
  // 清除选择的图片
  $('#wp2pcs-insert-media-btn-clear').click(function(){
    $('.file-on-pcs').removeClass('selected');
    $('.file-on-pcs input').prop('checked',false);
  });
  // 点击插入按钮
  $('#wp2pcs-insert-media-btn-insert').click(function(){
    if($('.file-on-pcs.selected').length > 0) {
      var html = '';
      $('.file-on-pcs.selected').each(function(){
        var $this = $(this),
            file_type = $this.attr('date-file-type'),
            $input = $this.children('input'),
            is_imglink = $('#wp2pcs-insert-media-iframe-check-imglink').prop('checked'),
            is_videoplay = $('#wp2pcs-insert-media-iframe-check-videoplay').prop('checked'),
            root_dir = $('#wp2pcs-insert-media-iframe-check-root-dir').val(),
            video_path = $input.attr('data-video-path'),
            site_id = $input.attr('data-site-id'),
            url = $input.val();
        if(site_id == undefined) site_id = '';
        // 如果被选择的是图片
        if($this.hasClass('file-format-image')){
          if(is_imglink) html += '<a href="' + url + '">';
          html += '<img src="' + url + '" class="wp2pcs-img">';
          if(is_imglink) html += '</a>';
        }
        // 如果是视频
        else if($this.hasClass('file-format-video')) {
          if(is_videoplay) {
            html += '<iframe class="wp2pcs-video-player" width="480" height="360" data-stretch="uniform" data-autostart="false" data-image="" data-path="' + video_path + '" data-site-id="' + site_id + '"';
            if(root_dir) html += ' data-root-dir="' + root_dir + '"';
            html += ' scrolling="no" frameborder="0"></iframe>';
          }
          else {
            html += '[video width="" height="" src="' + url + '" poster="none" preload="none" loop="off" autoplay="off" data-site-id="' + site_id + '"]';
          }
        }
        else if($this.hasClass('file-format-music')) {
          html += '[audio src="' + url + '" poster="none" preload="none" loop="off" autoplay="off" data-site-id="' + site_id + '"]';
        }
        // 如果是其他文件，就直接给媒体链接
        else{
          html += '<span class="wp2pcs-file">';
          if(is_imglink) html += '<a href="' + url + '" class="wp2pcs-file">';
          html += url;
          if(is_imglink) html += '</a>';
          html += '</span>';
        }
      });
      $('#wp2pcs-insert-media-btn-clear').click();
      // http://stackoverflow.com/questions/13680660/insert-content-to-wordpress-post-editor
      window.parent.send_to_editor(html);
      window.parent.tb_remove();
    }else{
      alert('没有选择任何附件');
    }
  });
  // 下拉加载
  $(window).scroll(function(){
    var $window = $(window),
        scroll_top = $window.scrollTop(),
        screen_height = $window.height(),
        $pagenavi = $('#wp2pcs-insert-media-iframe-pagenavi'),
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
            LIST = $('#wp2pcs-insert-media-iframe-files',DATA),
            NAVI = $('#wp2pcs-insert-media-iframe-pagenavi',DATA);
        $('#wp2pcs-insert-media-iframe-files').append(LIST.html());
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
  $(document).on('click','#wp2pcs-insert-media-iframe-pagenavi a.next-page',function(e){
    if($(this).parent().attr('data-ajaxing') != 'true') {
      e.preventDefault();
      $(window).scroll();
      return false;
    }
  });
  // 刷新按钮
  $('#wp2pcs-insert-media-btn-refresh').click(function(e){
    e.preventDefault();
    var $this = $(this),
        $body = $('#wp2pcs-insert-media-iframe-content'),
        href = $this.attr('href'),
        loading = $this.attr('data-loading'),
        ajaxing = $this.attr('data-ajaxing');
    $('#wp2pcs-insert-media-iframe-help,#wp2pcs-insert-media-iframe-upload').hide();
    $('#wp2pcs-insert-media-iframe-content').fadeIn(500);
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
            CONTENT = $('#wp2pcs-insert-media-iframe-content',DATA);
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
