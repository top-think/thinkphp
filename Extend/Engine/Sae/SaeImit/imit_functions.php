<?php
//模拟器需要用的函数
function Imit_L($key){
    static $msgs=array();
    if(is_array($key)){
        $msgs=array_merge($msgs,$key);
        return ;
    }
    return $msgs[$key];
    
}
