<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: mode.php 2702 2012-02-02 12:35:01Z liu21st $

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 模式配置文件 定义格式
 +------------------------------------------------------------------------------
 * 包括: core 编译列表文件定义
 *         alias 项目类库别名定义
 *         extends 系统行为定义
 *         tags 应用行为定义
 *         config 模式配置定义
 *         common 项目公共文件定义
 * 可以只定义其中一项或者多项 其他则取默认模式配置
 +------------------------------------------------------------------------------
*/
return array(
    // 系统核心列表文件定义 无需加载Portal Think Log ThinkException类库 
    // 需要纳入编译缓存的文件都可以在此定义 其中 App Action类库必须定义
    // 不在编译列表中的类库 如果需要自动加载 可以定义别名列表
    /*
    例如：
    'core'         =>   array(
        THINK_PATH.'Common/functions.php', // 标准模式函数库
        CORE_PATH.'Core/Log.class.php',    // 日志处理类
        CORE_PATH.'Core/Dispatcher.class.php', // URL调度类
        CORE_PATH.'Core/App.class.php',   // 应用程序类
        CORE_PATH.'Core/Action.class.php', // 控制器类
        CORE_PATH.'Core/View.class.php',  // 视图类
    ),*/

    // 项目别名定义文件 [支持数组直接定义或者文件名定义]
    // 例如 'alias'         =>    CONF_PATH.'alias.php', 

    // 系统行为定义文件 [必须 支持数组直接定义或者文件名定义 ]
    // 例如 'extends'    =>    THINK_PATH.'Conf/tags.php', 

    // 项目应用行为定义文件 [支持数组直接定义或者文件名定义]
    // 例如 'tags'         =>   CONF_PATH.'tags.php', 

    // 项目公共文件
    // 例如 'common'   =>    COMMON_PATH.'common.php', 

    // 模式配置文件  [支持数组直接定义或者文件名定义]（如有相同则覆盖项目配置文件中的配置）
    // 例如 'config'       =>   array(), 
);