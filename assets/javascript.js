jQuery(function($){
  // 显示和隐藏区域块
  $('.metabox-holder .postbox .handlediv').click(function(){
    $(this).parent().toggleClass('closed');
  });
});
