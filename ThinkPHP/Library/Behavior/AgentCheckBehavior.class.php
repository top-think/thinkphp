<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Behavior;
use Think\Behavior;
defined('THINK_PATH') or exit();
/**
 * 行为扩展：代理检测
 * @category   Extend
 * @package  Extend
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class AgentCheckBehavior extends Behavior {
    protected $options   =  array(
            'LIMIT_PROXY_VISIT'=>true,
        );
    public function run(&$params) {
        // 代理访问检测
        if(C('LIMIT_PROXY_VISIT') && ($_SERVER['HTTP_X_FORWARDED_FOR'] || $_SERVER['HTTP_VIA'] || $_SERVER['HTTP_PROXY_CONNECTION'] || $_SERVER['HTTP_USER_AGENT_VIA'])) {
            // 禁止代理访问
            exit('Access Denied');
        }
    }
}
