<?php
/* 海龙挖掘机 2.0正式版
 * 正文提取，分析，可自动判断编码，自动转码
 * 原理：根据代码块加权的原理，首先将HTML分成若干个小块，然后对每个小块进行评分。
 * 取分数在3分以上的代码块中的内容返回
 * 加分项 1 含有标点符号
 *        2 含有<p>标签
 *        3 含有<br>标签
 * 减分项 1 含有li标签
 *        2 不包含任何标点符号
 *        3 含有关键词javascript
 *        4 不包含任何中文的，直接删除
 *        5 有<li><a这样标签
 * 实例：
 * $he = new HtmlExtractor();
 * $str = $he->text($html);
 * 其中$html是某个网页的HTML代码，$str是返回的正文，正文编码是utf-8的
 */
class HtmlExtractor{

    /*
     * 取得汉字的个数（目前不太精确)
     */
    function chineseCount($str){
        $count = preg_match_all("/[\xB0-\xF7][\xA1-\xFE]/",$str,$ff);
        return $count;
    }

    /*
     * 判断一段文字是否是UTF-8，如果不是，那么要转成UTF-8
     */
    function getutf8($str){
        if(!$this->is_utf8(substr(strip_tags($str),0,500))){
            $str = $this->auto_charset($str,"gbk","utf-8");
        }
        return $str;
    }

    function is_utf8($string)
	{
		if(preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$string) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$string) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$string) == true){
            return true;
        }else{
            return false;
        }
	}

    /*
     * 自动转换字符集，支持数组和字符串
     */
    function auto_charset($fContents,$from,$to){
        $from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
        $to       =  strtoupper($to)=='UTF8'? 'utf-8':$to;
        if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) ){
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if(is_string($fContents) ) {
            if(function_exists('mb_convert_encoding')){
                return mb_convert_encoding ($fContents, $to, $from);
            }elseif(function_exists('iconv')){
                return iconv($from,$to,$fContents);
            }else{
                return $fContents;
            }
        }
        elseif(is_array($fContents)){
            foreach ( $fContents as $key => $val ) {
                $_key =     $this->auto_charset($key,$from,$to);
                $fContents[$_key] = $this->auto_charset($val,$from,$to);
                if($key != $_key )
                    unset($fContents[$key]);
            }
            return $fContents;
        }
        else{
            return $fContents;
        }
    }

    /*
     * 进行正文提取动作
     */
    function text($str){
        $str = $this->clear($str);
        $str = $this->getutf8($str);
        $divList = $this->divList($str);
        $content = array();
        foreach($divList[0] as $k=>$v){
            //首先判断，如果这个内容块的汉字数量站总数量的一半还多，那么就直接保留
            //还要判断，是不是一个A标签把整个内容都扩上
            if($this->chineseCount($v)/(strlen($v)/3) >= 0.4 && $this->checkHref($v)){
                array_push($content,strip_tags($v,"<p><br>"));
            }else if($this->makeScore($v) >= 3){
                //然后根据分数判断，如果大于3分的，保留
                array_push($content,strip_tags($v,"<p><br>"));
            }else{
                //这些就是排除的内容了
            }
        }
        return implode("",$content);
    }

    /*
     * 判断是不是一个A标签把整个内容都扩上
     * 判断方法：把A标签和它的内容都去掉后，看是否还含有中文
     */
    private function checkHref($str){
        if(!preg_match("'<a[^>]*?>(.*)</a>'si",$str)){
            //如果不包含A标签，那不用管了，99%是正文
            return true;
        }
        $clear_str = preg_replace("'<a[^>]*?>(.*)</a>'si","",$str);
        if($this->chineseCount($clear_str)){
            return true;
        }else{
            return false;
        }
    }

    function makeScore($str){
        $score = 0;
        //标点分数
        $score += $this->score1($str);
        //判断含有P标签
        $score += $this->score2($str);
        //判断是否含有br标签
        $score += $this->score3($str);
        //判断是否含有li标签
        $score -= $this->score4($str);
        //判断是否不包含任何标点符号
        $score -= $this->score5($str);
        //判断javascript关键字
        $score -= $this->score6($str);
        //判断<li><a这样的标签
        $score -= $this->score7($str);
        return $score;
    }

    /*
     * 判断是否有标点符号
     */
    private function score1($str){
        //取得标点符号的个数
        $count = preg_match_all("/(，|。|！|（|）|“|”|；|《|》|、)/si",$str,$out);
        if($count){
            return $count * 2;
        }else{
            return 0;
        }
    }

    /*
     * 判断是否含有P标签
     */
    private function score2($str){
        $count = preg_match_all("'<p[^>]*?>.*?</p>'si",$str,$out);
        return $count * 2;
    }

    /*
     * 判断是否含有BR标签
     */
    private function score3($str){
        $count = preg_match_all("'<br/>'si",$str,$out) + preg_match_all("'<br>'si",$str,$out);
        return $count * 2;
    }

    /*
     * 判断是否含有li标签
     */
    private function score4($str){
        //有多少，减多少分 * 2
        $count = preg_match_all("'<li[^>]*?>.*?</li>'si",$str,$out);
        return $count * 2;
    }

    /*
     * 判断是否不包含任何标点符号
     */
    private function score5($str){
        if(!preg_match_all("/(，|。|！|（|）|“|”|；|《|》|、|【|】)/si",$str,$out)){
            return 2;
        }else{
            return 0;
        }
    }

    /*
     * 判断是否包含javascript关键字，有几个，减几分
     */
    private function score6($str){
        $count = preg_match_all("'javascript'si",$str,$out);
        return $count;
    }

    /*
     * 判断<li><a这样的标签，有几个，减几分
     */
    private function score7($str){
        $count = preg_match_all("'<li[^>]*?>.*?<a'si",$str,$out);
        return $count * 2;
    }

    /*
     * 去噪
     */
    private function clear($str){
        $str = preg_replace("'<script[^>]*?>.*?</script>'si","",$str);
        $str = preg_replace("'<style[^>]*?>.*?</style>'si","",$str);
        $str = preg_replace("'<!--.*?-->'si","",$str);
        return $str;
    }

    /*
     * 取得内容块
     */
    private function divList($str){
        preg_match_all("'<[^a][^>]*?>.*?</[^>]*?>'si",$str,$divlist);
        return $divlist;
    }
}
?>