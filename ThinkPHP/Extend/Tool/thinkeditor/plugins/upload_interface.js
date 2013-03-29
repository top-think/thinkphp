function te_upload_interface() {
    //初始化参数
    var _args = arguments,
    _fn   = _args.callee,
    _data = '';

    if( _args[0] == 'reg' ) {
        //注册回调
        _data = _args[1];
        _fn.curr = _data['callid'];
        _fn.data = _data;
        jQuery('#temaxsize').val(_data['maxsize']);
    } else if( _args[0] == 'get' ) {
        //获取配置
        return _fn.data || false;

    } else if( _args[0] == 'call' ) {
        //处理回调与实例不一致
        if( _args[1] != _fn.curr ) {
            alert( '上传出错，请不要同时打开多个上传弹窗' );
            return false;
        }
        //上传成功
        if( _args[2] == 'success' ) {
            _fn.data['callback']( _args[3] );
        }
        //上传失败
        else if( _args[2] == 'failure' ) {
            alert( '[上传失败]\n错误信息:'+_args[3] );
        }
        //文件类型检测错误
        else if( _args[2] == 'filetype' ) {
            alert( '[上传失败]\n错误信息：您上传的文件类型有误' );
        }
        //处理状态改变
        else if( _args[2] == 'change' ) {
            // TODO 更细致的回调实现,此处返回true自动提交
            return true;
        }
    }
}
//用户选择文件时
function checkTypes(id){
    //校验文件类型
    var filename  = document.getElementById( 'teupload' ).value,
    filetype  = document.getElementById( 'tefiletype' ).value.split( ',' );

    currtype  = filename.split( '.' ).pop(),
    checktype = false;

    if( filetype[0] == '*' ) {
        checktype = true;
    } else {
        for(var i=0; i<filetype.length; i++) {
            if( currtype ==  filetype[i] ) {
                checktype = true;
                break;
            }
        }
    }
    if( !checktype ) {
        alert( '[上传失败]\n错误信息：您上传的文件类型有误' );
        return false;
    } else {
        //校验通过，提交
        jQuery('#'+id).submit()
    }
}