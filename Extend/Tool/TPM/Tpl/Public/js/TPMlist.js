//列表插件
//元素属性： data-api，请求api地址 ； data-datas 请求参数 data-tpl 模板地址 data-tabletpagesize 平板每页显示条数, data-phonepagesize 手机每页显示条数
//author : luofei614<http://weibo.com/luofei614>
;(function($){
$.fn.extend({
 'TPMlist':function(options){
     var defaults={
        "param_pagesize":"pagesize",
        "param_page":"page",
        "tabletpagesize":40,
        "phonepagesize":20
     };
    options=$.extend(defaults,options);
    $(this).each(function(){
       //获得api
       var api=$(this).data('api');
        //获得请求参数
        var datas=$(this).data('datas');
        //获得模板
        var tpl=$(this).data('tpl');
        //获得数据集合名称
       //获得pagesize
       var type=$(window).height()>767?'tablet':'phone';
       var defaultpagesize='tablet'==type?options.tabletpagesize:options.phonepagesize;//默认每页显示条数
       var pagesize=$(this).data(type+'pagesize') || defaultpagesize;
       $children=$('<div><div class="list_content">加载中..</div></div>').appendTo(this).find('.list_content');
       //下拉刷新
       var sc=$(this).TPMpulltorefresh(function(){
         $children.TPMgetListData(api,datas,tpl,pagesize,1,this,options);
       });
       $children.TPMgetListData(api,datas,tpl,pagesize,1,sc,options);
       
    });
 },
  'TPMgetListData':function(api,datas,tpl,pagesize,page,sc,options){
   var params=datas?datas.split('&'):{};
   var datas_obj={};
   for(var i=0;i<params.length;i++){
        var p=params[i].split('=');
        datas_obj[p[0]]=p[1];
   }
   datas_obj[options.param_pagesize]=pagesize;
   datas_obj[options.param_page]=page;
   var $this=$(this);
   //请求api
   TPM.sendAjax(api,datas_obj,'get',function(response){
       //渲染模板
       $.get(tpl,function(d,x,s){
           var html=TPM.parseTpl(d,response);
           //判断是否为第一页，如果为第一页，清空以前数据然后重新加载，如果不是第一页数据进行累加
           if(1==page){
                $this.empty(); 
           }
           $this.find('.getmore').remove();//删除以前的加载更多
           $this.append(html);
           if(response.currentpage!=response.totalpages){
               //加载更多按钮
               $more=$('<div class="getmore">加载更多</div>');
               $more.appendTo($this);
               $more.click(function(){
                   $(this).html('加载中...');//TODO 加载中样式
                    $this.TPMgetListData(api,datas,tpl,pagesize,parseInt($this.data('currentpage'))+1,sc,options); 
               });
           }
           sc.refresh();//iscroll refresh;
            //记录当前页面
           $this.data('currentpage',response.currentpage);
       },'text')
   });
 },
 //下拉刷新
 'TPMpulltorefresh':function(cb){
       //增加下拉刷新提示层
       var $pulldown=$('<div class="pullDown"><span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span></div>')
       $pulldown.prependTo($(this).children());
       var offset=$pulldown.outerHeight(true);
       var  myScroll=new iScroll($(this)[0],{
           useTransition: true,
           topOffset:offset,
           hideScrollbar:true,
           onRefresh: function () {
               $pulldown.removeClass('loading');
               $pulldown.find('.pullDownLabel').html('下拉可以刷新');
            },
            onScrollMove: function () {
                if (this.y > 5 && !$pulldown.is('.flip')) {
                    $pulldown.addClass('flip');
                    $pulldown.find('.pullDownLabel').html('松开可以刷新');
                    this.minScrollY = 0;
                } else if (this.y < 5 && $pulldown.is('.flip')) {
                    $pulldown.removeClass('flip');
                    $pulldown.find('.pullDownLabel').html('下拉可以刷新');
                    this.minScrollY = -offset;
                }
            },
            onScrollEnd: function () {
                if($pulldown.is('.flip')){
                    $pulldown.removeClass('flip');
                    $pulldown.addClass('loading');
                    $pulldown.find('.pullDownLabel').html('加载中...');
                    cb.call(this);//触发回调函数
                }
           }
    });
       return myScroll;
 }

});
})(jQuery);

