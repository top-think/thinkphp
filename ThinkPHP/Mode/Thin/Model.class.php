<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 简洁模式Model模型类
 * 只支持原生SQL操作 支持多数据库连接和切换
 +------------------------------------------------------------------------------
 */
class Model extends Think
{
    // 数据库连接对象列表
    private $_db = array();
    // 当前数据库操作对象
    protected $db = null;
    // 数据表前缀
    protected $tablePrefix  =   '';
    // 模型名称
    protected $name = '';
    // 数据库名称
    protected $dbName  = '';
    // 数据表名（不包含表前缀）
    protected $tableName = '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName ='';
    // 最近错误信息
    protected $error = '';

    /**
     +----------------------------------------------------------
     * 架构函数
     * 取得DB类的实例对象
     +----------------------------------------------------------
     * @param string $name 模型名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($name='')
    {
        // 模型初始化
        $this->_initialize();
        // 获取模型名称
        if(!empty($name)) {
            $this->name   =  $name;
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();
        }
        // 数据库初始化操作
        import("Db");
        // 获取数据库操作对象
        $this->db = Db::getInstance(empty($this->connection)?'':$this->connection);
        // 设置表前缀
        $this->tablePrefix = $this->tablePrefix?$this->tablePrefix:C('DB_PREFIX');
        // 设置默认的数据库连接
        $this->_db[0]   =   $this->db;
    }

    // 回调方法 初始化模型
    protected function _initialize() {}

    /**
     +----------------------------------------------------------
     * SQL查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $sql  SQL指令
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function query($sql)
    {
        if(is_array($sql)) {
            return $this->patchQuery($sql);
        }
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            return $this->db->query($sql);
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $sql  SQL指令
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function execute($sql='')
    {
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            $result =   $this->db->execute($sql);
            return $result;
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 得到当前的数据对象名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getModelName()
    {
        if(empty($this->name)) {
            $this->name =   substr(get_class($this),0,-5);
        }
        return $this->name;
    }

    /**
     +----------------------------------------------------------
     * 得到完整的数据表名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getTableName()
    {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }else{
                $tableName .= parse_name($this->name);
            }
            if(!empty($this->dbName)) {
                $tableName    =  $this->dbName.'.'.$tableName;
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return $this->trueTableName;
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 提交事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     +----------------------------------------------------------
     * 增加数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $config 数据库连接信息
     * 支持批量添加 例如 array(1=>$config1,2=>$config2)
     * @param mixed $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function addConnect($config,$linkNum=NULL) {
        if(isset($this->_db[$linkNum]))
            return false;
        if(NULL === $linkNum && is_array($config)) {
            // 支持批量增加数据库连接
            foreach ($config as $key=>$val)
                $this->_db[$key]            =    Db::getInstance($val);
            return true;
        }
        // 创建一个新的实例
        $this->_db[$linkNum]            =    Db::getInstance($config);
        return true;
    }

    /**
     +----------------------------------------------------------
     * 删除数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function delConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            $this->_db[$linkNum]->close();
            unset($this->_db[$linkNum]);
            return true;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function closeConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            $this->_db[$linkNum]->close();
            return true;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 切换数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function switchConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            // 在不同实例直接切换
            $this->db   =   $this->_db[$linkNum];
            return true;
        }else{
            return false;
        }
    }
};
?>