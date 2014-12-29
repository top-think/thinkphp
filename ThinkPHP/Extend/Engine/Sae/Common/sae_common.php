<?php
//[sae] 获得storage的domain地址,在config_sae.php中可以使用
function sae_storage_root($domain){
    if(defined('SAE_CACHE_BUILDER'))
        return '~sae_storage_root("'.$domain.'")';
    $s=Think::instance('SaeStorage');
    return rtrim($s->getUrl($domain,''),'/');
}