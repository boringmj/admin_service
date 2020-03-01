<?php

#class/Admin

class Admin
{
    //必要参数(目前没有提供函数设置)
    public $database_object;            //数据库对象
    public $app_id="";                  //开发者app_id
    public $uuid="";                    //用户唯一标识,uuid
    public $admin_config=array();       //后台配置信息

    //系统回馈参数(系统一般不直接返回结果,需要回馈参数中自行查找,大多回馈参数出于提高效率而存在的,不可直接访问)
    protected $balance=0;               //用户余额(需调用方法返回)
    protected $time_stamp=0;            //开通时间(不可返回)
    protected $admin_info=array();      //后台信息
    protected $create_admin_count=0;    //已开通的后台个数(需要先调用getCreateCount()后才可访问)
    protected $api_info=array();        //查询到的api_id对应的信息(至少需要调用一次checkApi()才会有数据)
    public $error_info=array();         //错误信息

    //检查用户数据是否已经存在,不存在补充用户数据
    protected function checkUser()
    {
        //避免重复检查浪费资源
        if(empty($this->admin_info))
        {
            $server_time_stamp=time();
            $creat_time=date("Ym",$server_time_stamp);
            $table_name=$this->database_object->getTablename('admin_user');
            $sql_statement=$this->database_object->object->prepare("SELECT time_stamp,uuid,balance,create_time,create_count FROM {$table_name} WHERE uuid=:uuid ORDER BY id DESC LIMIT 0,1");
            $sql_statement->bindParam(':uuid',$this->uuid);
            $sql_statement->execute();
            $result_sql_temp=$sql_statement->fetch();
            if(isset($result_sql_temp['uuid'])&&$result_sql_temp['uuid']===$this->uuid)
            {
                //验证数据是否还是当月的
                if($result_sql_temp['create_time']!=$creat_time)
                {
                    //如果不是当月的就更新当月创建的次数,不验证本次是否成功
                    $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET create_time=:create_time,create_count=0 WHERE uuid=:uuid");
                    $sql_statement->bindParam(':create_time',$creat_time);
                    $sql_statement->bindParam(':uuid',$this->uuid);
                    $sql_statement->execute();
                    $result_sql_temp['create_time']=$creat_time;
                    $result_sql_temp['create_count']="0";
                }
                //返回用户数据
                $this->balance=round($result_sql_temp['balance'],2);
                $this->time_stamp=$result_sql_temp['time_stamp'];
                $this->admin_info=$result_sql_temp;
                return 1;
            }
            else
            {
                //不存在就补充
                $sql_statement=$this->database_object->object->prepare("INSERT INTO {$table_name}(time_stamp,uuid,balance,create_time,create_count,app_id) VALUES (:time_stamp,:uuid,0,:create_time,0,:app_id)");
                $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                $sql_statement->bindParam(':uuid',$this->uuid);
                $sql_statement->bindParam(':create_time',$creat_time);
                $sql_statement->bindParam(':app_id',$this->app_id);
                if($sql_statement->execute())
                {
                    //依旧需要返回用户数据
                    $this->balance=round(0,2);
                    $this->time_stamp=$server_time_stamp;
                    $this->admin_info=array(
                        'time_stamp'=>$server_time_stamp,
                        'uuid'=>$this->uuid,
                        'balance'=>$this->balance,
                        'create_time'=>$creat_time,
                        'create_count'=>"0"
                    );
                    return 1;
                }
                else
                {
                    $this->error_info['checkUser']=array(
                        'code'=>1018,
                        'title'=>"失败",
                        'content'=>"异常错误",
                        'variable'=>''
                    );
                    return 0;
                }
            }
        }
        else
        {
            return 1;
        }
    }

    //获取用户剩余额,失败返回-1
    public function getBalance()
    {
        if($this->checkUser())
        {
            return round($this->balance,2);
        }
        else
        {
            //出现错误返回-1
            $this->error_info['getBalance']=$this->error_info['checkUser'];
            return -1;
        }
    }

    //增减用户金额,失败返回-1
    public function setBalance($money)
    {
        if($this->checkUser())
        {
            $money=round($this->balance+$money,2);
            if($money>=0)
            {
                $table_name=$this->database_object->getTablename('admin_user');
                $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET balance=:balance WHERE uuid=:uuid");
                $sql_statement->bindParam(':balance',$money);
                $sql_statement->bindParam(':uuid',$this->uuid);
                if($sql_statement->execute())
                {
                    $this->balance=$money;
                    return round($this->balance,2);
                }
                else
                {
                    $this->error_info['setBalance']=array(
                        'code'=>1018,
                        'title'=>"失败",
                        'content'=>"异常错误",
                        'variable'=>''
                    );
                    return -1;
                }
            }
            else
            {
                $this->error_info['setBalance']=array(
                    'code'=>1059,
                    'title'=>"失败",
                    'content'=>"余额不足",
                    'variable'=>''
                );
                return -1;
            }
        }
        else
        {
            //出现错误返回-1
            $this->error_info['setBalance']=$this->error_info['checkUser'];
            return -1;
        }
    }

    //获取已经开通的后台个数,出现错误返回-1
    public function getCreateCount()
    {
        if($this->checkUser())
        {
            $table_name=$this->database_object->getTablename('admin_application');
            $sql_statement=$this->database_object->object->prepare("SELECT time_stamp,uuid FROM {$table_name} WHERE uuid=:uuid");
            $sql_statement->bindParam(':uuid',$this->uuid);
            $sql_statement->execute();
            $this->create_admin_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
            return $this->create_admin_count;
        }
        else
        {
            //出现错误返回-1
            $this->error_info['getCreateCount']=$this->error_info['checkUser'];
            return -1;
        }
    }

    //获取这个月创建应用总数
    public function getCreateCountMonth()
    {
        if($this->checkUser())
        {
            return $this->admin_info['create_count'];
        }
        else
        {
            //出现错误返回-1
            $this->error_info['getCreateCountMonth']=$this->error_info['checkUser'];
            return -1;
        }
    }

    //检查api_id是否存在或是否为当前用户所有的(不检查api_id状态)
    public function checkApi($api_id)
    {
        if($this->checkUser())
        {
            $table_name=$this->database_object->getTablename('admin_application');
            $sql_statement=$this->database_object->object->prepare("SELECT * FROM {$table_name} WHERE uuid=:uuid AND api_id=:api_id ORDER BY id DESC LIMIT 0,1");
            $sql_statement->bindParam(':uuid',$this->uuid);
            $sql_statement->bindParam(':api_id',$api_id);
            $sql_statement->execute();
            $result_sql_temp=$sql_statement->fetch(PDO::FETCH_ASSOC);
            if(isset($result_sql_temp['uuid'])&&$result_sql_temp['uuid']===$this->uuid)
            {
                $this->api_info=$result_sql_temp;
                return 1;
            }
            else
            {
                $this->error_info['checkApi']=array(
                    'code'=>1062,
                    'title'=>"失败",
                    'content'=>"非法请求",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            //出现错误返回0
            $this->error_info['checkApi']=$this->error_info['checkUser'];
            return 0;
        }
    }

    //设置应用状态(Y开启,N禁用,保留项:B封禁,E过期),这里没有资源浪费检查,毕竟写的话有点麻烦
    public function setApiStates($api_id,$states)
    {
        if($this->checkUser())
        {
            if($this->checkApi($api_id))
            {
                if($this->api_info['api_states']==='Y'||$this->api_info['api_states']==='N')
                {
                    if($states==='Y'||$states==='N')
                    {
                        //直接改变当前状态,不验证是否成功
                        $table_name=$this->database_object->getTablename('admin_application');
                        $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET api_states=:api_states WHERE uuid=:uuid");
                        $sql_statement->bindParam(':api_states',$states);
                        $sql_statement->bindParam(':uuid',$this->uuid);
                        $sql_statement->execute();
                        $this->api_info['api_states']=$states;
                        return 1;
                    }
                    else
                    {
                        $this->error_info['setApiStates']=array(
                            'code'=>1043,
                            'title'=>"失败",
                            'content'=>"错误的选择状态",
                            'variable'=>''
                        );
                        return 0;
                    }
                }
                else
                {
                    $this->error_info['setApiStates']=array(
                        'code'=>1063,
                        'title'=>"失败",
                        'content'=>"非法请求",
                        'variable'=>''
                    );
                    return 0;
                }
            }
            else
            {
                $this->error_info['setApiStates']=$this->error_info['checkApi'];
                return 0;
            }
        }
        else
        {
            //出现错误返回0
            $this->error_info['setApiStates']=$this->error_info['checkUser'];
            return 0;
        }
    }

    //获取api_id对应的信息
    public function getApiInfo($api_id)
    {
        if($this->checkUser())
        {
            //检查是否已经存在缓存信息(主要是节约资源避免二次调用)
            if(isset($this->api_info['api_id'])&&$this->api_info['api_id']===$api_id)
            {
                return $this->api_info;
            }
            else
            {
                //没有调用过就获取
                if($this->checkApi($api_id))
                {
                    return $this->api_info;
                }
                else
                {
                    $this->error_info['getApiInfo']=$this->error_info['checkApi'];
                    return 0;
                }
            }
        }
        else
        {
            //出现错误返回0
            $this->error_info['getApiInfo']=$this->error_info['checkUser'];
            return 0;
        }
    }
}

?>