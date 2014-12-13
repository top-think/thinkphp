<?php
//平滑函数，sae和本地都可以用，增加系统平滑性
function sae_unlink($filePath) {
    if (IS_SAE) {
        $arr = explode('/', ltrim($filePath, './'));
        $domain = array_shift($arr);
        $filePath = implode('/', $arr);
        $s = Think::instance('SaeStorage');
        return $s->delete($domain, $filePath);
    } else {
        return unlink($filePath);
    }
}