function app_ready($){
    $("#login").height($(window).height());
    if(Modernizr.mq("(max-width:767px)")){
        if($('#right').size()>0){
            $('#right_box').empty();
            $('#right').appendTo('#right_box');
            // 防止多次监听
            $('#main').off('swipeLeft.once');
            $('#main').on('swipeLeft.once',function(){
                $('#main').addClass('show_right_nav');
            });    
            $('#main').off('swipeRight.once');
            $('#main').on('swipeRight.once',function(){
                $('#main').removeClass('show_right_nav');
            });
            }
    }
    $('input:file').TPMupload('/Index/upload');
}

function show_right_nav(){
    if($('#main').is('.show_right_nav')){
        $('#main').removeClass('show_right_nav');
    }else{
        $('#main').addClass('show_right_nav');
    }
}
//扫描二维码
function  qrcode(){
    sina.barcodeScanner.scan(function(result) {
        TPM.http(result.text);
    }); 
}
//语言识别
function voice(){
        if(!isclient){
            alert('请在手机客户端中使用');
            return ;
        }

        var appId = '4fa77fe4';
        sina.voice.recognizer.init(appId);
        
        sina.voice.recognizer.setOption({
            engine: 'sms',
            sampleRate: 'rate16k',
        });

        sina.voice.recognizer.setListener("onResults");
        
        sina.voice.recognizer.start(function(response) {
            console.log("response: " + response.errorCode + ", msg: " + response.message);
        });
}

function onResults(response) 
{
	response.results.forEach(function(recognizerResult) {
		$("#content").val( $("#content").val() + recognizerResult.text );	
  	});
}

//绑定账户
function bind(type){
    var url="/Index/bind/type/"+type;
    if(isclient){
        tpm_popurl(TPM.op.api_base+url,function(){
           TPM.reload(TPM.op.main);
        },'绑定账号')               
    }else{
        url=$('<a href="'+url+'"></a>')[0].href;
        tpm_popurl(url,function(){
            location.reload();
        },'绑定账号');
    }
}


