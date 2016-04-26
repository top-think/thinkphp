<?php

/**
 * ThinkPHP Redis简单主从实现的高可用方案
 * 
 * 扩展依赖：https://github.com/phpredis/phpredis
 * 
 * 一主一从的实践经验
 * 1, A、B为主从，正常情况下，A写，B读，通过异步同步到B（或者双写，性能有损失）
 * 2, B挂，则读写均落到A
 * 3, A挂，则尝试升级B为主，并断开主从尝试写入
 * 4, 手工恢复A，并加入B的从
 */

/**
    'DATA_CACHE_TYPE'          =>'Redisd',      //默认动态缓存为Redis
    'DATA_CACHE_PREFIX'        =>'Redis_',      //缓存前缀
    'DATA_CACHE_TIME'          =>0,             //默认过期时间，默认0为永不过期
    'REDISD_HOST'              =>'192.168.1.11,192.168.1.12', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读
    'REDISD_MASTER_FAILOVER'   =>'192.168.1.12',              //当主发生故障时，从12断从升级为主，读写均在12
    'REDISD_PORT'              =>6379,          //端口号
    'REDISD_TIMEOUT'           =>30,            //连接超时时间
    'REDISD_PERSISTENT'        =>false,         //是否长连接 false=短连接，推荐长链接
    'REDISD_AUTH'              =>'',            //AUTH认证密码
 */

namespace Think\Cache\Driver;

use Think\Cache;

class Redisd extends Cache
{
    static $redis_rw_handler;
    static $redis_err_pool;
    
    /**
     * 为了在单次php请求中复用redis连接，第一次获取的options会被缓存，第二次使用不同的$options，将会无效
     * 
     * @param  array $options 缓存参数，来自于S函数和\Think\Cache::getInstance("redisd", $options);
     * @access public
     */
    public function __construct($options = array())
    {
        if (! extension_loaded('redis' )) {
            throw_exception(L('_NOT_SUPPERT_') . ':redis');
        }

        $default = array (
            'host' => C('REDISD_HOST') ? C('REDISD_HOST') : '127.0.0.1',
            'port' => C('REDISD_PORT') ? C('REDISD_PORT') : 6379,
            'timeout' => C('REDISD_TIMEOUT') ? C('REDISD_TIMEOUT') : 0,
            'persistent' => C('REDISD_PERSISTENT'),
            'auth' => C('REDISD_AUTH'),
            'server_master_failover' => C('REDISD_MASTER_FAILOVER'),
        );
        
        $options = array_merge($default, $options);
        
        $this->options = $options;
        $this->options ['expire'] = isset($options ['expire']) ? $options ['expire'] : C('DATA_CACHE_TIME');
        $this->options ['prefix'] = isset($options ['prefix']) ? $options ['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options ['length'] = isset($options ['length']) ? $options ['length'] : 0;
        $this->options ['func'] = $options ['persistent'] ? 'pconnect' : 'connect';
        
        $host = explode(",", trim($this->options ['host'], ","));
        $host = array_map("trim", $host);
        
        $this->options ["servers"] = count($host);
        $this->options ["server_master"] = array_shift($host);
        $this->options ["server_master_failover"] = explode(",", trim($this->options ['server_master_failover'], ","));
        $this->options ["server_slave"] = (array)$host;
    }
    
    /**
     * 主从选择器，配置多个Host则自动启用读写分离，默认主写，随机从读
     * 随机从读的场景适合读频繁，且php与redis从位于单机的架构，这样可以减少网络IO
     * 一致Hash适合超高可用，跨网络读取，且从节点较多的情况，本业务不考虑该需求
     * 
     * @access public
     * @param bool $master true 默认主写
     */
    public function master($master = false)
    {
        if (isset(self::$redis_rw_handler[$master])) {
            return $this->handler = self::$redis_rw_handler[$master];
        }

        //如果不为主，则从配置的host剔除主，并随机读从，失败以后再随机选择从
        //另外一种方案是根据key的一致性hash选择不同的node，但读写频繁的业务中可能打开大量的文件句柄
        if(!$master && $this->options["servers"] > 1) {
            shuffle($this->options["server_slave"]);
            $host = array_shift($this->options["server_slave"]);
        }else{
            $host = $this->options["server_master"];
        }

        $this->handler = new \Redis();
        $func = $this->options ['func'];
        
        $parse = parse_url($host);
        $host  = isset($parse['host']) ? $parse['host'] : $parse['path'];
        $port  = $parse['port'] ? $parse['port'] : $this->options ['port'];
        
        $this->handler->$func($host, $port, $this->options ['timeout']);

        if ($this->options ['auth'] != null) {
            $this->handler->auth($this->options ['auth']);
        }

        //发生错误则摘掉当前节点
        try {
            $error = $this->handler->getLastError();
        } catch (\RedisException $e) {
            //phpredis throws a RedisException object if it can't reach the Redis server.
            //That can happen in case of connectivity issues, if the Redis service is down, or if the redis host is overloaded.
            //In any other problematic case that does not involve an unreachable server 
            //(such as a key not existing, an invalid command, etc), phpredis will return FALSE.
            
            \Think\Log::write(sprintf("redisd->%s:%s:%s", $master ? "master" : "salve", $host, $e->getMessage()), \Think\Log::WARN);
            
            //主节点挂了以后，尝试连接主备，断开主备的主从连接进行升主
            if($master) {
                if(! count($this->options["server_master_failover"])) {
                    E("redisd master: no more server_master_failover. {$host} : ".$e->getMessage());
                    return false;
                }

                $this->options["server_master"] = array_shift($this->options["server_master_failover"]);
                $this->master();
                
                \Think\Log::write(sprintf("master is down, try server_master_failover : %s", $this->options["server_master"]), \Think\Log::WARN);

                //如果是slave，断开主从升主，需要手工同步新主的数据到旧主上
                //目前这块的逻辑未经过严格测试
                //$this->handler->slaveof();
            } else {
                //尝试failover，如果有其它节点则进行其它节点的尝试
                foreach ($this->options["server_slave"] as $k=>$v)
                {
                    if (trim($v) == trim($host))
                        unset($this->options["server_slave"][$k]);
                }
                
                //如果无可用节点，则抛出异常
                if(! count($this->options["server_slave"])) {
                    \Think\Log::write("已无可用Redis读节点", \Think\Log::EMERG);
                    E("redisd slave: no more server_slave. {$host} : ".$e->getMessage());
                    return false;
                } else {
                    \Think\Log::write("salve {$host} is down, try another one.", \Think\Log::EMERG);
                    return $this->master(false);
                }
            }
        } catch(\Exception $e) {
            E($e->getMessage(), $e->getCode());
        }

        self::$redis_rw_handler[$master] = $this->handler;
    }

    /**
     * 读取缓存
     * 
     * @access public
     * @param  string $name 缓存key
     * @return mixed
     */
    public function get($name)
    {
        N('cache_read', 1);
        $this->master(false);

        try {
            $value = $this->handler->get($this->options ['prefix'] . $name);
        } catch (\RedisException $e) {
            unset(self::$redis_rw_handler[0]);
            $this->master();
            $this->get($name);
        } catch (\Exception $e) {
            \Think\Log::write($e->getMessage(), \Think\Log::WARN);
        }

        $jsonData = null;
        //如果是对象则进行反转
        if (!empty($value) && false !== strpos("[{", $value[0])) {
            $jsonData = $value ? json_decode($value, true) : $value;
        }

        return ($jsonData === NULL) ? $value : $jsonData;
    }
    
    /**
     * 写入缓存
     * 
     * @access public
     * @param  string  $name   缓存key
     * @param  mixed   $value  缓存value
     * @param  integer $expire 过期时间，单位秒
     * @return boolen
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        $this->master(true);
        
        if (is_null($expire )) {
            $expire = $this->options ['expire'];
        }
        $name = $this->options ['prefix'] . $name;
        
        /**
         * 兼容历史版本
         * Redis不支持存储对象，存入对象会转换成字符串
         * 但在这里，对所有数据做json_decode会有性能开销
         */
        $value = (is_object($value) || is_array($value )) ? json_encode($value) : $value;
        
        if ($value === null) {
            return $this->handler->delete($this->options ['prefix'] . $name);
        }
        
        // $expire < 0 则等于ttl操作，列为todo吧
        try {
            if (is_int($expire) && $expire) {
                $result = $this->handler->setex($name, $expire, $value);
            } else {
                $result = $this->handler->set($name, $value);
            }
        } catch (\RedisException $e) {
            unset(self::$redis_rw_handler[1]);
            $this->master(true);
            $this->set($name, $value, $expire);
        } catch (\Exception $e) {
            \Think\Log::write($e->getMessage(), \Think\Log::WARN);
        }

        return $result;
    }
    
    /**
     * 返回句柄对象
     * 需要先执行 $redis->master() 连接到 DB
     * 
     * @access public
     * @return object
     */
    function handler()
    {
        return $this->handler;
    }
    
    /**
     * 删除缓存
     * 
     * @access public
     * @param  string $name 缓存变量名
     * @return boolen
     */
    public function rm($name)
    {
        N('cache_write', 1);
        $this->master(true);
        return $this->handler->delete($this->options ['prefix'] . $name);
    }
    
    /**
     * 清除缓存
     * 
     * @access public
     * @return boolen
     */
    public function clear()
    {
        N('cache_write', 1);
        $this->master(true);
        return $this->handler->flushDB ();
    }
    
    /**
     * 析构释放连接
     * 
     * @access public
     */
    public function __destruct()
    {
        //该方法仅在connect连接时有效
        //当使用pconnect时，连接会被重用，连接的生命周期是fpm进程的生命周期，而非一次php的执行。 
        //如果代码中使用pconnect， close的作用仅是使当前php不能再进行redis请求，但无法真正关闭redis长连接，连接在后续请求中仍然会被重用，直至fpm进程生命周期结束。
        
        try {
            if(method_exists($this->handler, "close"))
                $this->handler->close ();
        } catch (\Exception $e) {
        }
    }
}