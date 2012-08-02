<?php
/**
 * SAE数据抓取服务
 *
 * @author  zhiyong
 * @version $Id: SaeFetchurl.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
 * @package sae
 *
 */

/**
 * SAE数据抓取class
 *
 * SaeFetchurl用于抓取外部数据。支持的协议为http/https。<br />
 * 该类已被废弃，请直接使用curl抓取外部资源
 * @deprecated 该类已被废弃，请直接使用curl抓取外部资源
 *
 * 默认超时时间：
 *  - 连接超时： 5秒
 *  - 发送数据超时： 30秒
 *  - 接收数据超时： 40秒
 *
 * 抓取页面
 * <code>
 * $f = new SaeFetchurl();
 * $content = $f->fetch('http://sina.cn');
 * </code>
 *
 * 发起POST请求
 * <code>
 * $f = new SaeFetchurl();
 * $f->setMethod('post');
 * $f->setPostData( array('name'=> 'easychen' , 'email' => 'easychen@gmail.com' , 'file' => '文件的二进制内容') );
 * $ret = $f->fetch('http://photo.sinaapp.com/save.php');
 * 
 * //抓取失败时输出错误码和错误信息
 * if ($ret === false)
 * 		var_dump($f->errno(), $f->errmsg());
 * </code>
 *
 * 错误码参考：
 *  - errno: 0 		成功
 *  - errno: 600 	fetchurl 服务内部错误
 *  - errno: 601 	accesskey 不存在
 *  - errno: 602 	认证错误，可能是secretkey错误
 *  - errno: 603 	超出fetchurl的使用配额
 *  - errno: 604 	REST 协议错误，相关的header不存在或其它错误，建议使用SAE提供的fetch_url函数
 *  - errno: 605 	请求的URI格式不合法
 *  - errno: 606 	请求的URI，服务器不可达。
 *
 * @author  zhiyong
 * @version $Id: SaeFetchurl.class.php 2766 2012-02-20 15:58:21Z luofei614@gmail.com $
 * @package sae
 *
 */
class SaeFetchurl extends SaeObject
{
	function __construct( $akey = NULL , $skey = NULL )
	{
		if( $akey === NULL )
			$akey = SAE_ACCESSKEY;

		if( $skey === NULL )
			$skey = SAE_SECRETKEY;

		$this->impl_ = new FetchUrl($akey, $skey);
		$this->method_ = "get";
		$this->cookies_ = array();
		$this->opt_ = array();
		$this->headers_ = array();
	}

	/**
	 * 设置acccesskey和secretkey
	 *
	 * 使用当前的应用的key时,不需要调用此方法
	 *
	 * @param string $akey
	 * @param string $skey
	 * @return void
	 * @author zhiyong
	 * @ignore
	 */
	public function setAuth( $akey , $skey )
	{
		$this->impl_->setAccesskey($akey);
		$this->impl_->setSecretkey($skey);
	}

	/**
	 * @ignore
	 */
	public function setAccesskey( $akey )
	{
		$this->impl_->setAccesskey($akey);
	}

	/**
	 * @ignore
	 */
	public function setSecretkey( $skey )
	{
		$this->impl_->setSecretkey($skey);
	}

	/**
	 * 设置请求的方法(POST/GET/PUT... )
	 *
	 * @param string $method
	 * @return void
	 * @author zhiyong
	 */
	public function setMethod( $method )
	{
		$this->method_ = trim($method);
		$this->opt_['method'] = trim($method);
	}

	/**
	 * 设置POST方法的数据
	 *
	 * @param array|string $post_data 当格式为array时，key为变量名称,value为变量值，使用multipart方式提交。当格式为string时，直接做为post的content提交。与curl_setopt($ch, CURLOPT_POSTFIELDS, $data)中$data的格式相同。
	 * @param bool $multipart value是否为二进制数据
	 * @return bool
	 * @author zhiyong
	 */
	public function setPostData( $post_data , $multipart = false )
	{
		$this->opt_["post"] = $post_data;
		$this->opt_["multipart"] = $multipart;

		return true;
	}

	/**
	 * 在发起的请求中,添加请求头
	 *
	 * 不可以使用此方法设定的头：
	 *  - Content-Length
	 *  - Host
	 *  - Vary
	 *  - Via
	 *  - X-Forwarded-For
	 *  - FetchUrl
	 *  - AccessKey
	 *  - TimeStamp
	 *  - Signature
	 *  - AllowTruncated	//可使用setAllowTrunc方法来进行设定
	 *  - ConnectTimeout	//可使用setConnectTimeout方法来进行设定
	 *  - SendTimeout		//可使用setSendTimeout方法来进行设定
	 *  - ReadTimeout		//可使用setReadTimeout方法来进行设定
	 *
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 * @author zhiyong
	 */
	public function setHeader( $name , $value )
	{
		$name = trim($name);
		if (!in_array(strtolower($name), FetchUrl::$disabledHeaders)) {
			$this->headers_[$name] = $value;
			return true;
		} else {
			trigger_error("Disabled FetchUrl Header:" . $name, E_USER_NOTICE);
			return false;
		}
	}

	/**
	 * 设置FetchUrl参数
	 *
	 * 参数列表：
	 *  - truncated		布尔		是否截断
	 *  - redirect			布尔		是否支持重定向
	 *  - username			字符串		http认证用户名
	 *  - password			字符串		http认证密码
	 *  - useragent		字符串		自定义UA
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 * @author Elmer Zhang
	 * @ignore
	 */
	public function setOpt( $name , $value )
	{
		$name = trim($name);
		$this->opt_[$name] = $value;
	}

	/**
	 * 在发起的请求中,批量添加cookie数据
	 *
	 * @param array $cookies 要添加的Cookies，格式：array('key1' => 'value1', 'key2' => 'value2', ....)
	 * @return void
	 * @author zhiyong
	 */
	public function setCookies( $cookies = array() )
	{
		if ( is_array($cookies) and !empty($cookies) ) {
			foreach ( $cookies as $k => $v ) {
				$this->setCookie($k, $v);
			}
		}
	}

	/**
	 * 在发起的请求中,添加cookie数据,此函数可多次调用,添加多个cookie
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 * @author zhiyong
	 */
	public function setCookie( $name , $value )
	{
		$name = trim($name);
		array_push($this->cookies_, "$name=$value");
	}

	/**
	 * 是否允许截断，默认为不允许
	 *
	 * 如果设置为true,当发送数据超过允许大小时,自动截取符合大小的部分;<br />
	 * 如果设置为false,当发送数据超过允许大小时,直接返回false;
	 *
	 * @param bool $allow
	 * @return void
	 * @author zhiyong
	 */
	public function setAllowTrunc($allow) {
		$this->opt_["truncated"] = $allow;
	}

	/**
	 * 设置连接超时时间,此时间必须小于SAE系统设置的时间,否则以SAE系统设置为准（默认为5秒）
	 *
	 * @param int $ms 毫秒
	 * @return void
	 * @author zhiyong
	 */
	public function setConnectTimeout($ms) {
		$this->opt_["connecttimeout"] = $ms;
	}

	/**
	 * 设置发送超时时间,此时间必须小于SAE系统设置的时间,否则以SAE系统设置为准（默认为20秒）
	 *
	 * @param int $ms 毫秒
	 * @return void
	 * @author zhiyong
	 */
	public function setSendTimeout($ms) {
		$this->opt_["sendtimeout"] = $ms;
	}

	/**
	 * 设置读取超时时间,此时间必须小于SAE系统设置的时间,否则以SAE系统设置为准（默认为60秒）
	 *
	 * @param int $ms 毫秒
	 * @return void
	 * @author zhiyong
	 */
	public function setReadTimeout($ms) {
		$this->opt_["ReadTimeout"] = $ms;
	}

	/**
	 * 当请求页面是转向页时,是否允许跳转,SAE最大支持5次跳转(默认不跳转)
	 *
	 * @param bool $allow 是否允许跳转。true:允许，false:禁止，默认为true
	 * @return void
	 * @author zhiyong
	 */
	public function setAllowRedirect($allow = true) {
		$this->opt_["redirect"] = $allow;
	}

	/**
	 * 设置HTTP认证用户名密码
	 *
	 * @param string $username HTTP认证用户名
	 * @param string $password HTTP认证密码
	 * @return void
	 * @author zhiyong
	 */
	public function setHttpAuth($username, $password) {
		$this->opt_["username"] = $username;
		$this->opt_["password"] = $password;
	}

	/**
	 * 发起请求
	 *
	 * <code>
	 * <?php
	 * echo "Use callback function\n";
	 *
	 * function demo($content) {
	 * 		echo strtoupper($content);
	 * }
	 * 
	 * $furl = new SaeFetchurl();
	 * $furl->fetch($url, $opt, 'demo');
	 * 
	 * echo "Use callback class\n";
	 * 
	 * class Ctx {
	 *  	public function demo($content) {
	 * 				$this->c .= $content;	
	 * 		}
	 * 		public $c;
	 * };
	 * 
	 * $ctx = new Ctx;
	 * $furl = new SaeFetchurl();
	 * $furl->fetch($url, $opt, array($ctx, 'demo'));
	 * echo $ctx->c;
	 * ?>
	 * </code>
	 *
	 * @param string $url
	 * @param array $opt 请求参数，格式：array('key1'=>'value1', 'key2'=>'value2', ... )。参数列表：
	 *  - truncated		布尔		是否截断
	 *  - redirect			布尔		是否支持重定向
	 *  - username			字符串		http认证用户名
	 *  - password			字符串		http认证密码
	 *  - useragent		字符串		自定义UA
	 * @param callback $callback 用来处理返回的数据的函数。可以为函数名或某个实例对象的方法。
	 * @return string 成功时读取到的内容，否则返回false
	 * @author zhiyong
	 */
	public function fetch( $url, $opt = NULL, $callback=NULL )
	{
		if (count($this->cookies_) != 0) {
			$this->opt_["cookie"] = join("; ", $this->cookies_);
		}
		$opt = ($opt) ?  array_merge($this->opt_, $opt) : $this->opt_;
		return $this->impl_->fetch($url, $opt, $this->headers_, $callback);
	}

	/**
	 * 返回数据的header信息
	 *
	 * @param bool $parse 是否解析header，默认为true。
	 * @return array
	 * @author zhiyong
	 */
	public function responseHeaders($parse = true)
	{
		$items = explode("\r\n", $this->impl_->headerContent());
		if (!$parse) {
			return $items;
		}
		array_shift($items);
		$headers = array();
		foreach ($items as $_) {
			$pos = strpos($_, ":");
			$key = trim(substr($_, 0, $pos));
			$value = trim(substr($_, $pos + 1));
			if ($key == "Set-Cookie") {
				if (array_key_exists($key, $headers)) {
					array_push($headers[$key], trim($value));
				} else {
					$headers[$key] = array(trim($value));
				}
			} else {
				$headers[$key] = trim($value);
			}
		}
		return $headers;
	}

	/**
	 * 返回HTTP状态码
	 *
	 * @return int
	 * @author Elmer Zhang
	 */
	public function httpCode() {
		return $this->impl_->httpCode();
	}

	/**
	 * 返回网页内容
	 * 常用于fetch()方法返回false时
	 *
	 * @return string
	 * @author Elmer Zhang
	 */
	public function body() {
		return $this->impl_->body();
	}

	/**
	 * 返回头里边的cookie信息
	 * 
	 * @param bool $all 是否返回完整Cookies信息。为true时，返回Cookie的name,value,path,max-age，为false时，只返回Cookies的name, value
	 * @return array
	 * @author zhiyong
	 */
	public function responseCookies($all = true)
	{
		$header = $this->impl_->headerContent();
		$matchs = array();
		$cookies = array();
		$kvs = array();
		if (preg_match_all('/Set-Cookie:\s([^\r\n]+)/i', $header, $matchs)) {
			foreach ($matchs[1] as $match) {
				$cookie = array();
				$items = explode(";", $match);
				foreach ($items as $_) {
					$item = explode("=", trim($_));
					$cookie[$item[0]]= $item[1];
				}
				array_push($cookies, $cookie);
				$kvs = array_merge($kvs, $cookie);
			}
		}
		if ($all) {
			return $cookies;
		} else {
			unset($kvs['path']);
			unset($kvs['max-age']);
			return $kvs;
		}
	}

	/**
	 * 返回错误码
	 *
	 * @return int
	 * @author zhiyong
	 */
	public function errno()
	{
		if ($this->impl_->errno() != 0) {
			return $this->impl_->errno();
		} else {
			if ($this->impl_->httpCode() != 200) {
				return $this->impl_->httpCode();
			}
		}
		return 0;
	}

	/**
	 * 返回错误信息
	 *
	 * @return string
	 * @author zhiyong
	 */
	public function errmsg()
	{
		if ($this->impl_->errno() != 0) {
			return $this->impl_->error();
		} else {
			if ($this->impl_->httpCode() != 200) {
				return $this->impl_->httpDesc();
			}
		}
		return "";
	}

	/**
	 * 将对象的数据重新初始化,用于多次重用一个SaeFetchurl对象
	 *
	 * @return void
	 * @author Elmer Zhang
	 */
	public function clean() {
		$this->__construct();
	}

	/**
	 * 开启/关闭调试模式
	 *
	 * @param bool $on true：开启调试；false：关闭调试
	 * @return void
	 * @author Elmer Zhang
	 */
	public function debug($on) {
		if ($on) {
			$this->impl_->setDebugOn();
		} else {
			$this->impl_->setDebugOff();
		}
	}


	private $impl_;
	private $opt_;
	private $headers_;

}


/**
 * FetchUrl , the sub class of SaeFetchurl
 *
 *
 * @package sae
 * @subpackage fetchurl
 * @author  zhiyong
 * @ignore
 */
class FetchUrl {
	const end_         = "http://fetchurl.sae.sina.com.cn/" ;
	const maxRedirect_ = 5;
	public static $disabledHeaders = array(
		'content-length',
		'host',
		'vary',
		'via',
		'x-forwarded-for',
		'fetchurl',
		'accesskey',
		'timestamp',
		'signature',
		'allowtruncated',
		'connecttimeout',
		'sendtimeout',
		'readtimeout',
	);

	public function __construct($accesskey, $secretkey) {
		$accesskey = trim($accesskey);
		$secretkey = trim($secretkey);

		$this->accesskey_ = $accesskey;
		$this->secretkey_ = $secretkey;

		$this->errno_ = 0;
		$this->error_ = null;
		$this->debug_ = false;
	}

	public function __destruct() {
		// do nothing
	}

	public function setAccesskey($accesskey) {
		$accesskey = trim($accesskey);
		$this->accesskey_ = $accesskey;
	}

	public function setSecretkey($secretkey) {
		$secretkey = trim($secretkey);
		$this->secretkey_ = $secretkey;
	}

	public function setDebugOn() {
		$this->debug_ = true;
	}

	public function setDebugOff() {
		$this->debug_ = false;
	}

	public function fetch($url, $opt = null, $headers = null, $callback = null) {

		$url = trim($url);
		if (substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://') {
			$url = 'http://' . $url;
		}

		$this->callback_ = $callback;

		$maxRedirect = FetchUrl::maxRedirect_;
		if (is_array($opt) && array_key_exists('redirect',$opt) && !$opt['redirect']) {
			$maxRedirect = 1;
		}

		for ($i = 0; $i < $maxRedirect; ++$i) {
			$this->dofetch($url, $opt, $headers);
			if ($this->errno_ == 0) {
				if ($this->httpCode_ == 301 || $this->httpCode_ == 302) {
					$matchs = array();
					if (preg_match('/Location:\s([^\r\n]+)/i', $this->header_, $matchs)) {
						$newUrl = $matchs[1];
						// if new domain
						if (strncasecmp($newUrl, "http://", strlen("http://")) == 0) {
							$url = $newUrl;
						} else {
							$url = preg_replace('/^((?:https?:\/\/)?[^\/]+)\/(.*)$/i', '$1', $url) . "/". $newUrl;
						}

						if ($this->debug_) {
							echo "[debug] redirect to $url\n";
						}
						continue;
					}
				}
			}
			break;
		}

		if ($this->errno_ == 0 && $this->httpCode_ == 200) {
			return $this->body_;
		} else {
			return false;
		}
	}

	public function headerContent() {
		return $this->header_;
	}

	public function errno() {
		return $this->errno_;
	}

	public function error() {
		return $this->error_;
	}

	public function httpCode() {
		return $this->httpCode_;
	}

	public function body() {
		return $this->body_;
	}

	public function httpDesc() {
		return $this->httpDesc_;
	}

	private function signature($url, $timestamp) {
		$content = "FetchUrl"  . $url .
			"TimeStamp" . $timestamp .
			"AccessKey" . $this->accesskey_;
		$signature = (base64_encode(hash_hmac('sha256',$content,$this->secretkey_,true)));
		if ($this->debug_) {
			echo "[debug] content: $content" . "\n";
			echo "[debug] signature: $signature" . "\n";
		}
		return $signature;
	}

	// we have to set wirteBody & writeHeader public
	// for we used them in curl_setopt()
	public function writeBody($ch, $body) {
		if ($this->callback_) {
			call_user_func($this->callback_, $body);
		} else {
			$this->body_ .= $body;	
		}
		if ($this->debug_) {
			echo "[debug] body => $body";
		}
		return strlen($body);
	}

	public function writeHeader($ch, $header) {
		$this->header_ .= $header;
		if ($this->debug_) {
			echo "[debug] header => $header";	
		}
		return strlen($header);	
	}

	private function dofetch($url, $opt, $headers_) {


		$this->header_ = $this->body_ = null;
		$headers = array();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false) ;
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,true) ;
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'writeBody'));
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'writeHeader'));
		if ($this->debug_) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		}

		if (is_array($opt) && !empty($opt)) {
			foreach( $opt as $k => $v) {
				switch(strtolower($k)) {
				case 'username':
					if (array_key_exists("password",$opt)) {
						curl_setopt($ch, CURLOPT_USERPWD, $v . ":" . $opt["password"]);
					}
					break;
				case 'password':
					if (array_key_exists("username",$opt)) {
						curl_setopt($ch, CURLOPT_USERPWD, $opt["username"] . ":" . $v);
					}
					break;
				case 'useragent':
					curl_setopt($ch, CURLOPT_USERAGENT, $v);
					break;
				case 'post':
					curl_setopt($ch, CURLOPT_POSTFIELDS, $v);
					break;
				case 'cookie':
					curl_setopt($ch, CURLOPT_COOKIESESSION, true);
					curl_setopt($ch, CURLOPT_COOKIE, $v);
					break;
				case 'multipart':
					if ($v) array_push($headers, "Content-Type: multipart/form-data");
					break;
				case 'truncated':
					array_push($headers, "AllowTruncated:" . $v);
					break;
				case 'connecttimeout':
					array_push($headers, "ConnectTimeout:" . intval($v));
					break;
				case 'sendtimeout':
					array_push($headers, "SendTimeout:" . intval($v));
					break;
				case 'readtimeout':
					array_push($headers, "ReadTimeout:" . intval($v));
					break;
				default:
					break;

				}
			}
		}

		if (isset($opt['method'])) {
			if (strtolower($opt['method']) == 'get') {
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
		}

		if (is_array($headers_) && !empty($headers_)) {
			foreach($headers_ as $k => $v) {
				if (!in_array(strtolower($k), FetchUrl::$disabledHeaders)) {
					array_push($headers, "{$k}:" . $v);
				}
			}
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		curl_exec($ch);
		$info = curl_getinfo($ch);
		if ($this->debug_) {
			echo "[debug] curl_getinfo => " . print_r($info, true) . "\n";
		}
		$this->errno_ = curl_errno($ch);
		$this->error_ = curl_error($ch);

		if ($this->errno_ == 0) {
			$matchs = array();
			if (preg_match('/^(?:[^\s]+)\s([^\s]+)\s([^\r\n]+)/', $this->header_, $matchs)) {
				$this->httpCode_ = $matchs[1];
				$this->httpDesc_ = $matchs[2];
				if ($this->debug_) {
					echo "[debug] httpCode = " . $this->httpCode_ . "  httpDesc = " . $this->httpDesc_ . "\n";
				}
			} else {
				$this->errno_ = -1;
				$this->error_ = "invalid response";
			}
		}
		curl_close($ch);
	}

	private $accesskey_;
	private $secretkey_;

	private $errno_;
	private $error_;

	private $httpCode_;
	private $httpDesc_;
	private $header_;
	private $body_;

	private $debug_;

}