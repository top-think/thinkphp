<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Model;

use Think\Model;

/**
 * ThinkPHP视图模型扩展
 */
class ViewModel extends Model
{

    protected $viewFields = array();

    /**
     * 自动检测数据表信息
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {}

    /**
     * 得到完整的数据表名
     * @access public
     * @return string
     */
    public function getTableName()
    {
        if (empty($this->trueTableName)) {
            $tableName = '';
            foreach ($this->viewFields as $key => $view) {
                // 获取数据表名称
                if (isset($view['_table'])) {
                    // 2011/10/17 添加实际表名定义支持 可以实现同一个表的视图
                    $tableName .= $view['_table'];
                    $prefix    = $this->tablePrefix;
                    $tableName = preg_replace_callback("/__([A-Z_-]+)__/sU", function ($match) use ($prefix) {return $prefix . strtolower($match[1]);}, $tableName);
                } else {
                    $class = parse_res_name($key, C('DEFAULT_M_LAYER'));
                    $Model = class_exists($class) ? new $class() : M($key);
                    $tableName .= $Model->getTableName();
                }
                // 表别名定义
                $tableName .= !empty($view['_as']) ? ' ' . $view['_as'] : ' ' . $key;
                // 支持ON 条件定义
                $tableName .= !empty($view['_on']) ? ' ON ' . $view['_on'] : '';
                // 指定JOIN类型 例如 RIGHT INNER LEFT 下一个表有效
                $type = !empty($view['_type']) ? $view['_type'] : '';
                $tableName .= ' ' . strtoupper($type) . ' JOIN ';
                $len = strlen($type . '_JOIN ');
            }
            $tableName           = substr($tableName, 0, -$len);
            $this->trueTableName = $tableName;
        }
        return $this->trueTableName;
    }

    /**
     * 表达式过滤方法
     * @access protected
     * @param string $options 表达式
     * @return void
     */
    protected function _options_filter(&$options)
    {
        if (isset($options['field'])) {
            $options['field'] = $this->checkFields($options['field']);
        } else {
            $options['field'] = $this->checkFields();
        }

        if (isset($options['group'])) {
            $options['group'] = $this->checkGroup($options['group']);
        }

        if (isset($options['where'])) {
            $options['where'] = $this->checkCondition($options['where']);
        }

        if (isset($options['order'])) {
            $options['order'] = $this->checkOrder($options['order']);
        }

    }

    /**
     * 检查是否定义了所有字段
     * @access protected
     * @param string $name 模型名称
     * @param array $fields 字段数组
     * @return array
     */
    private function _checkFields($name, $fields)
    {
        if (false !== $pos = array_search('*', $fields)) {
            // 定义所有字段
            $fields = array_merge($fields, M($name)->getDbFields());
            unset($fields[$pos]);
        }
        return $fields;
    }

    /**
     * 检查条件中的视图字段
     * @param $where 条件表达式
     * @return array
     */
    protected function checkCondition($where)
    {
        if (is_array($where)) {
            $fields = $field_map_table = array();
            foreach ($this->viewFields as $key => $val) {
                $table_alias = isset($val['_as']) ? $val['_as'] : $key;
                $val         = $this->_checkFields($key, $val);
                foreach ($val as $as_name => $v) {
                    if (is_numeric($as_name)) {
                        $fields[]          = $v; //所有表字段集合
                        $field_map_table[] = $table_alias; //所有表字段对应表名集合
                    } else {
                        $fields[$as_name]          = $v;
                        $field_map_table[$as_name] = $table_alias;
                    }
                }
            }
            $where = $this->_parseWhere($where, $fields, $field_map_table);
        }

        return $where;
    }

    /**
     * 解析where表达式
     * @param $where
     * @param $fields
     * @param $field_map_table
     * @return array
     */
    private function _parseWhere($where, $fields, $field_map_table)
    {
        $view = array();
        foreach ($where as $name => $val) {
            if ('_complex' == $name) {
                //复合查询
                foreach ($val as $k => $v) {
                    if (false === strpos(substr($k, 0, 1), '_')) {
                        if (false !== $field = array_search($k, $fields, true)) { // 存在视图字段
                            $k = is_numeric($field) ? $field_map_table[$field] . '.' . $k : $field_map_table[$field] . '.' . $field; //字段别名
                        }
                    } else if (is_array($v)) {
                        //数组复合查询
                        $v = $this->_parseWhere($val[$k], $fields, $field_map_table);
                    }
                    $view[$name][$k] = $v;
                }
            } else {
                if (strpos($name, '|')) {
                    //name|title快捷查询
                    $arr = explode('|', $name);
                    foreach ($arr as $k => $v) {
                        if (false !== $field = array_search($v, $fields, true)) {
                            $arr[$k] = is_numeric($field) ? $field_map_table[$field] . '.' . $v : $field_map_table[$field] . '.' . $field;
                        }
                    }
                    $view[implode('|', $arr)] = $val;
                } else if (strpos($name, '&')) {
                    //name&title快捷查询
                    $arr = explode('&', $name);
                    foreach ($arr as $k => $v) {
                        if (false !== $field = array_search($v, $fields, true)) {
                            $arr[$k] = is_numeric($field) ? $field_map_table[$field] . '.' . $v : $field_map_table[$field] . '.' . $field;
                        }
                    }
                    $view[implode('&', $arr)] = $val;
                } else {
                    if (false !== $field = array_search($name, $fields, true)) {
                        $name = is_numeric($field) ? $field_map_table[$field] . '.' . $name : $field_map_table[$field] . '.' . $field;
                    }
                    $view[$name] = $val;
                }
            }
        }

        return $view;
    }

    /**
     * 检查Order表达式中的视图字段
     * @access protected
     * @param string $order 字段
     * @return string
     */
    protected function checkOrder($order = '')
    {
        if (is_string($order) && !empty($order)) {
            $orders = explode(',', $order);
            $_order = array();
            foreach ($orders as $order) {
                $array = explode(' ', trim($order));
                $field = $array[0];
                $sort  = isset($array[1]) ? $array[1] : 'ASC';
                // 解析成视图字段
                foreach ($this->viewFields as $name => $val) {
                    $k   = isset($val['_as']) ? $val['_as'] : $name;
                    $val = $this->_checkFields($name, $val);
                    if (false !== $_field = array_search($field, $val, true)) {
                        // 存在视图字段
                        $field = is_numeric($_field) ? $k . '.' . $field : $k . '.' . $_field;
                        break;
                    }
                }
                $_order[] = $field . ' ' . $sort;
            }
            $order = implode(',', $_order);
        }
        return $order;
    }

    /**
     * 检查Group表达式中的视图字段
     * @access protected
     * @param string $group 字段
     * @return string
     */
    protected function checkGroup($group = '')
    {
        if (!empty($group)) {
            $groups = explode(',', $group);
            $_group = array();
            foreach ($groups as $field) {
                // 解析成视图字段
                foreach ($this->viewFields as $name => $val) {
                    $k   = isset($val['_as']) ? $val['_as'] : $name;
                    $val = $this->_checkFields($name, $val);
                    if (false !== $_field = array_search($field, $val, true)) {
                        // 存在视图字段
                        $field = is_numeric($_field) ? $k . '.' . $field : $k . '.' . $_field;
                        break;
                    }
                }
                $_group[] = $field;
            }
            $group = implode(',', $_group);
        }
        return $group;
    }

    /**
     * 检查fields表达式中的视图字段
     * @access protected
     * @param string $fields 字段
     * @return string
     */
    protected function checkFields($fields = '')
    {
        if (empty($fields) || '*' == $fields) {
            // 获取全部视图字段
            $fields = array();
            foreach ($this->viewFields as $name => $val) {
                $k   = isset($val['_as']) ? $val['_as'] : $name;
                $val = $this->_checkFields($name, $val);
                foreach ($val as $key => $field) {
                    if (is_numeric($key)) {
                        $fields[] = $k . '.' . $field . ' AS ' . $field;
                    } elseif ('_' != substr($key, 0, 1)) {
                        // 以_开头的为特殊定义
                        if (false !== strpos($key, '*') || false !== strpos($key, '(') || false !== strpos($key, '.')) {
                            //如果包含* 或者 使用了sql方法 则不再添加前面的表名
                            $fields[] = $key . ' AS ' . $field;
                        } else {
                            $fields[] = $k . '.' . $key . ' AS ' . $field;
                        }
                    }
                }
            }
            $fields = implode(',', $fields);
        } else {
            if (!is_array($fields)) {
                $fields = explode(',', $fields);
            }

            // 解析成视图字段
            $array = array();
            foreach ($fields as $key => $field) {
                if (strpos($field, '(') || strpos(strtolower($field), ' as ')) {
                    // 使用了函数或者别名
                    $array[] = $field;
                    unset($fields[$key]);
                }
            }
            foreach ($this->viewFields as $name => $val) {
                $k   = isset($val['_as']) ? $val['_as'] : $name;
                $val = $this->_checkFields($name, $val);
                foreach ($fields as $key => $field) {
                    if (false !== $_field = array_search($field, $val, true)) {
                        // 存在视图字段
                        if (is_numeric($_field)) {
                            $array[] = $k . '.' . $field . ' AS ' . $field;
                        } elseif ('_' != substr($_field, 0, 1)) {
                            if (false !== strpos($_field, '*') || false !== strpos($_field, '(') || false !== strpos($_field, '.'))
                            //如果包含* 或者 使用了sql方法 则不再添加前面的表名
                            {
                                $array[] = $_field . ' AS ' . $field;
                            } else {
                                $array[] = $k . '.' . $_field . ' AS ' . $field;
                            }

                        }
                    }
                }
            }
            $fields = implode(',', $array);
        }
        return $fields;
    }
}
