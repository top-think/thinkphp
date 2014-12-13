//兼容phonegap，电脑，手机的上传插件
//autor luofei614(http://weibo.com/luofei614)
;(function($){
    $.fn.extend({
        TPMupload:function(options){
            //配置项处理
            var defaults={
                "url":"",
                "name":"file",
                "sourceType":"Image", //针对手机有效， 上传类型，Image,Video,Audio,Libray 注意首字母大写。  Libray 表示上传手机相册中的图片。 
                "dataUrl":true,
                "quality":20,//图片质量
                "imgWidth":300,
                "imgHeight":300
            };
            if('string'==$.type(options))
        options={"url":options};
    var op=$.extend(defaults,options);
    //电脑上传
    var desktop_upload=function(index){
        op.name=$(this).attr('name') || op.name
        //增加上传按钮
        var $uploadBtn=$('<input type="button" class="TPMupload_btn" value="上传" />').insertBefore(this);
    //添加状态层
    var $status=$('<span class="TPMupload_status"></span>').insertBefore(this);
    //增加隐藏域
    var $hiddenInput=$('<input type="hidden" name="'+op.name+'" value="" />').insertBefore(this);;
    //增加结果显示层
    var $show=$('<div class="TPMupload_show"></div>').insertBefore(this);
    //增加提交表单
    var $form=$('<form action="'+op.url+'" target="TPMupload_iframe_'+index+'"  method="post" enctype="multipart/form-data"> <input type="file" size="1" name="'+op.name+'" style="cursor:pointer;" />  </form>').css({"position":"absolute","opacity":"0"}).insertBefore(this);
    //定位提交表单
    $uploadBtn.hover(function(e){
        $form.offset({top:e.pageY-20,left:e.pageX-50});
    });
    var $uploadInput=$form.find('input:file');
    $uploadInput.change(function(){
        $status.html('正在上传...');
        $form.submit();
    });
    $(this).remove();
    //增加iframe
    var $iframe=$('<iframe id="TPMupload_iframe_'+index+'" name="TPMupload_iframe_'+index+'" style="display:none" src="about:blank"></iframe>').appendTo('body');
    //获得iframe返回结果
    var iframe=$iframe[0];
    $iframe.bind("load", function(){
        if (iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" || // For Safari
            iframe.src == "javascript:'<html></html>';") { // For FF, IE
                return;
            }

        var doc = iframe.contentDocument ? iframe.contentDocument : window.frames[iframe.id].document;

        // fixing Opera 9.26,10.00
        if (doc.readyState && doc.readyState != 'complete') return;
        // fixing Opera 9.64
        if (doc.body && doc.body.innerHTML == "false") return;

        var response;

        if (doc.XMLDocument) {
            // response is a xml document Internet Explorer property
            response = doc.XMLDocument;
        } else if (doc.body){
            try{
                response = $iframe.contents().find("body").html();
            } catch (e){ // response is html document or plain text
                response = doc.body.innerHTML;
            }
        } else {
            // response is a xml document
            response = doc;
        }
        if(''!=response){
            $status.html('');
           if(-1!=response.indexOf('<pre>')){
               //iframe中的json格式，浏览器会自动渲染，加上pre标签，转义html标签，所以这里去掉pre标签，还原html标签。
                   var htmldecode=function(str)   
                   {   
                         var    s    =    "";   
                         if    (str.length    ==    0)    return    "";   
                         s    =    str.replace(/&amp;/g,    "&");   
                         s    =    s.replace(/&lt;/g,"<");   
                         s    =    s.replace(/&gt;/g,">");   
                         s    =    s.replace(/&nbsp;/g,"    ");   
                         s    =    s.replace(/'/g,"\'");   
                         s    =    s.replace(/&quot;/g, "\"");   
                         s    =    s.replace(/<br>/g,"\n");   
                         return    s;   
                   } 
                response=htmldecode($(response).html());
                console.log(response);
            }
            try{
                var ret=$.parseJSON(response);
                //显示图片
                if(ret.path) $hiddenInput.val(ret.path);
                if(ret.show) $show.html(ret.show);
                if(ret.error) $show.html(ret.error);
            }catch(e){
                console.log(response);
                alert('服务器返回格式错误'); 
            } 
        }
    });

    };
    //客户端上传
    var client_upload=function(index){
        op.name=$(this).attr('name') || op.name
        //增加上传按钮
        var $uploadBtn=$('<input type="button" class="TPMupload_btn" value="上传" />').insertBefore(this);
        //添加状态层
        var $status=$('<span class="TPMupload_status"></span>').insertBefore(this);
        //增加隐藏域
        var $hiddenInput=$('<input type="hidden" name="'+op.name+'" value="" />').insertBefore(this);;
        //增加结果显示层
        var $show=$('<div class="TPMupload_show"></div>').insertBefore(this);
        $(this).remove();
        var upload=function(file,isbase64){
            isbase64=isbase64 || false;
            if('http'!=op.url.substr(0,4).toLowerCase()){
                        //如果上传地址不是绝对地址， 加上TPM的基路径。 
                        op.url=TPM.op.api_base+op.url;
            }
            if(isbase64){
                //如果是base64的图片数据
                var $imgshow=$('<div><img src="data:image/png;base64,'+file+'" /><br /><span>点击图片可调整图片角度</span></div>').appendTo($show);
                var $img=$imgshow.find('img');
                $imgshow.click(function(){
                    var c=document.createElement('canvas');
                    var ctx=c.getContext("2d");
                    var img=new Image();
                    img.onload = function(){
                         c.width=this.height;
                         c.height=this.width; 
                         ctx.rotate(90 * Math.PI / 180);
                         ctx.drawImage(img, 0,-this.height);
                        var dataURL = c.toDataURL("image/png");
                        $img.attr('src',dataURL);
                        $hiddenInput.val(dataURL);
                    };
                    img.src=$img.attr('src');
                });
                $hiddenInput.val('data:image/png;base64,'+file); 
            }else{
                $status.html('正在上传...');
                //视频，语音等文件上传
                resolveLocalFileSystemURI(file,function(fileEntry){
                    fileEntry.file(function(info){
                         var options = new FileUploadOptions(); 
                            options.fileKey=op.name;
                            options.chunkedMode=false;
                            var ft = new FileTransfer();
                      
                            ft.upload(info.fullPath,op.url,function(r){
                                $status.html('');
                                try{
                                    var ret=$.parseJSON(r.response);
                                    //显示图片
                                    if(ret.path) $hiddenInput.val(ret.path);
                                    if(ret.show) $show.html(ret.show);
                                    if(ret.error) $show.html(ret.error);
                                }catch(e){
                                    console.log(r.response);
                                    alert('服务器返回格式错误'); 
                                } 
                            },function(error){
                                $status.html('');
                                alert("文件上传失败，错误码： " + error.code);
                            },options);
                    });
                });
            }
   
        };
        //扑捉对象
        $uploadBtn.click(function(){

            if('Libray'==op.sourceType || 'Image'==op.sourceType){
                var sourceType='Image'==op.sourceType?navigator.camera.PictureSourceType.CAMERA:navigator.camera.PictureSourceType.PHOTOLIBRARY;
                var destinationType=op.dataUrl?navigator.camera.DestinationType.DATA_URL:navigator.camera.DestinationType.FILE_URI;
                navigator.camera.getPicture(function(imageURI){
                    upload(imageURI,op.dataUrl);
                }, function(){
                }, {quality:op.quality,destinationType: destinationType,sourceType:sourceType,targetWidth:op.imgWidth,targetHeight:op.imgHeight});
            }else{
                var action='capture'+op.sourceType;
                navigator.device.capture[action](function(mediaFiles){
                    upload(mediaFiles[0].fullPath);
                },function(){
                }); 
            }
        });

    };

    $(this).each(function(index){
        //在SAE云窗调试器下可能有延迟问题，第一次加载会判断window.cordova未定义，这时候需要点击一下页面其他链接，再点击回来就可以了 
        if('cordova' in window){
            //手机上的处理方法
            client_upload.call(this,index);
        }else{
            //电脑上的处理方法
            desktop_upload.call(this,index);
        }
    });


        }
    });
})(jQuery);
