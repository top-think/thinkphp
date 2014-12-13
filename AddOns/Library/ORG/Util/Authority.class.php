<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <www.3g4k.com>　
// +----------------------------------------------------------------------
// $Id: Authority.class.php 2207 2011-11-30 13:17:26Z liu21st $
/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Authority();  $auth->getAuth('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Authority();  $auth->getAuth('规则1,规则2','用户id','and') 
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 * 
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 * @category ORG
 * @package ORG
 * @subpackage Util
 * @author luofei614<www.3g4k.com>
 */

//数据库
/*
-- ----------------------------
-- think_auth_rule，规则表，
-- id:主键，name：规则唯一标识, title：规则中文名称 type:类型（0存在规则就通过，1按规则表达时进行认证），condition：规则表达式
-- ----------------------------
 DROP TABLE IF EXISTS `think_auth_rule`;
CREATE TABLE `think_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(10) NOT NULL DEFAULT '',
  `title` char(20) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `condition` char(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8; 
-- ----------------------------
-- think_auth_group 用户组表， 
-- id：主键， title:用户组中文名称， rules：用户组拥有的规则id， 多个规则用“,”隔开
-- ----------------------------
 DROP TABLE IF EXISTS `think_auth_group`;
CREATE TABLE `think_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `rules` char(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group_access 用户组明细表
-- uid:用户id，group_id：用户组id
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group_access`;
CREATE TABLE `think_auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_2` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

 */

class Authority extends Think {

    //默认配置
    protected $_config = array(
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'think_auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'think_auth_group_access', //用户组明细表
        'AUTH_RULE' => 'think_auth_rule', //权限规则表
        'AUTH_USER' => 'think_members'//用户信息表
    );

    public function __construct() {
        if (C('AUTH_CONFIG')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    //获得权限$name 可以是字符串或数组或逗号分割， uid为 认证的用户id， $or 是否为or关系，为true是， name为数组，只要数组中有一个条件通过则通过，如果为false需要全部条件通过。
    public function getAuth($name, $uid, $relation='or') {
        if (!$this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid);
        if (is_string($name)) {
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //有权限的name
        foreach ($authList as $val) {
            if (in_array($val, $name))
                $list[] = $val;
        }
        if ($relation=='or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation=='and' and empty($diff)) {
            return true;
        }
        return false;
    }

    //获得用户组，外部也可以调用
    public function getGroups($uid) {
        static $groups = array();
        if (!empty($groups[$uid]))
            return $groups[$uid];
        $groups[$uid] = M()->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')->where("a.uid='$uid'")->join($this->_config['AUTH_GROUP']." g on a.group_id=g.id")->select();
        return $groups[$uid];
    }

    //获得权限列表
    protected function getAuthList($uid) {
        static $_authList = array();
        if (isset($_authList[$uid])) {
            return $_authList[$uid];
        }
        if(isset($_SESSION['_AUTH_LIST_'.$uid])){
            return $_SESSION['_AUTH_LIST_'.$uid];
        }
        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid] = array();
            return array();
        }
        //读取用户组所有权限规则(in)
        $map['id'] = array('in', $ids);
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->select();
        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $r) {
            if ($r['type'] == 1) {
                //条件验证
                $user = $this->getUserInfo($uid);
                $command = preg_replace('/\{(\w*?)\}/e', '$user[\'\\1\']', $r['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = $r['name'];
                }
            } else {
                //存在就通过
                $authList[] = $r['name'];
            }
        }
        $_authList[$uid] = $authList;
        if($this->_config['AUTH_TYPE']==2){
            //session结果
            $_SESSION['_AUTH_LIST_'.$uid]=$authList;
        }
        return $authList;
    }
    //获得用户资料,根据自己的情况读取数据库
    protected function getUserInfo($uid) {
        return M()->table($this->_config['AUTH_USER'])->find($uid);
    }

}