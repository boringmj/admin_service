<?php

#class/User

class User
{
    //系统同步参数
    protected $start_salt="";           //开始时的盐(需要与配置文件同步)
    protected $end_salt="";             //结束时的盐(需要与配置文件同步)

    //不可设置参数
    protected $user_email="";           //用户邮箱
    protected $user_qq="";              //qq号
    protected $user_wechat="";          //微信号
    
    //可选择参数
    protected $user_name="";            //用户名,通过setUser($user)设置
    protected $user_pass="";            //登录密码,通过setPass($pass)设置
    public $uuid="";                    //用户唯一标识,uuid
    public $ukey="";                    //登录唯一标识,ukey

    //必要参数(目前没有提供函数设置)
    public $database_object;            //数据库对象
    public $app_id="";                  //开发者app_id

    //系统回馈参数(系统一般不直接返回结果,需要回馈参数中自行查找)
    public $user_info=array(
        'get'=>0,                       //获取情况,为0就是没有获取用户信息,1为正常获取,一般用于判断用户是否是一个合法的用户
        'name'=>"",                     //用户名
        'id'=>"",                       //用户id
        'uuid'=>"",                     //用户唯一标识,uuid
        'nickname'=>"",                 //用户昵称
        'proving'=>0,                   //用户状态
        'time_stamp'=>0,                //注册时间戳
        'time'=>"0000-00-00 00:00:00",  //注册时间
        'integral'=>0,                  //积分
        'identification'=>0,            //身份标识码
        'identification_name'=>''       //身份标识名称
    );
    public $error_info=array();         //错误信息

    //系统固定参数
    protected $identification_name=array(
        '0'=>"超级管理员",
        '1'=>"普通用户",
        '100'=>"管理员",
        '101'=>"审查组总管",
        '1000'=>"举报审查组长",
        '1001'=>"举报审查员",
        '1002'=>"教程审查组长",
        '1003'=>"教程审查员",
        '10001'=>"认证成员"
    );

    /*# ~~~~~~~~~~ 以下是为方便传入参数定义的 ~~~~~~~~~~~~ #*/

    //设置uuid
    public function setUuid($uuid)
    {
        $this->uuid=$uuid;
    }

    //设置ukey
    public function setUkey($ukey)
    {
        $this->ukey=$ukey;
    }

    //设置用户账户
    public function setUser($user)
    {
        $this->user_name=$user;
    }

    //设置用户密码
    public function setPass($pass)
    {
        $this->user_pass=$pass;
    }

    //设置开始加盐
    public function setStartSalt($salt)
    {
        $this->start_salt=$salt;
    }

    //设置结束加盐
    public function setEndSalt($salt)
    {
        $this->end_salt=$salt;
    }

    /*# ~~~~~~~~~~ 以下是为方便获取参数定义的 ~~~~~~~~~~~~ #*/

    //获取用户邮箱
    public function getUserEmail()
    {
        return $this->user_email;
    }

    //获取用户qq号
    public function getUserQq()
    {
        return $this->user_qq;
    }

    //获取用户微信号
    public function getUserWechat()
    {
        return $this->user_wechat;
    }

    /*# ~~~~~~~~~~ 以下是为辅助程序运行定义的 ~~~~~~~~~~~~ #*/

    public function getIdentificationName($id)
    {
        $id=empty($this->identification_name[$id])?'1':$id;
        return $this->identification_name[$id];
    }

    protected function getRandstringid($type=0)
    {
        if(function_exists('uuid_create'))
        {
            return uuid_create($type);
        }
        else
        {
            $char="1234567890abcdef";
            $str=$this->getRandomstring(8,$char)."-".$this->getRandomstring(4,$char)."-".$this->getRandomstring(4,$char)."-".$this->getRandomstring(4,$char)."-".$this->getRandomstring(12,$char);
            return strtolower($str);
        }
    }

    protected function getRandomstring($len,$chars=null)
    {
        if(is_null($chars))
        {
            $chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i=0,$str='',$lc=strlen($chars)-1;$i<$len;$i++)
        {
            $str.=$chars[mt_rand(0,$lc)];
        }
        return $str;
    }

    /*# ~~~~~~~~~~ 以下调用需要先传入必要参数 ~~~~~~~~~~~~ #*/

    /*
    *通过用户账户和密码登录
    *登录后自动补充uuid和ukey(需要自行发送给用户),并且允许可获取用户信息的昵称
    */
    public function UserLogin($user='',$pass='')
    {
        if(empty($user))
            $user=$this->user_name;
        if(empty($pass))
            $pass=$this->user_pass;

        //去当前时间戳
        $server_time_stamp=time();
        //抛弃超过最大存储空间的数据
        $user=substr($user,0,32);
        //验证用户是否合法
        $table_name=$this->database_object->getTablename('user');
        $sql_statement=$this->database_object->object->prepare("SELECT uuid,nickname,proving FROM {$table_name} WHERE user=:user AND passwd=:passwd ORDER BY id DESC LIMIT 0,1");
        $pass=md5($this->start_salt.$pass.$this->end_salt);
        $sql_statement->bindParam(':user',$user);
        $sql_statement->bindParam(':passwd',$pass);
        $sql_statement->execute();
        $result_sql_temp=$sql_statement->fetch();
        if(isset($result_sql_temp['uuid']))
        {
            //用户存在
            if(isset($result_sql_temp['proving'])&&$result_sql_temp['proving']==='1')
            {
                $table_name=$this->database_object->getTablename('temporary_login_user');
                //生成ukey
                $ukey=md5($this->getRandomstring(22).time());
                //删除过期的用户数据
                $sql_statement=$this->database_object->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-30*24*60*60));
                $sql_statement->execute();
                //尝试获取用户信息(不存在创建,存在修改)
                $sql_statement=$this->database_object->object->prepare("SELECT time_stamp FROM {$table_name} WHERE uuid=:uuid ORDER BY id DESC LIMIT 0,1");
                $sql_statement->bindParam(':uuid',$result_sql_temp['uuid']);
                $sql_statement->execute();
                $result_sql_temp_login_user=$sql_statement->fetch();
                if(!isset($result_sql_temp_login_user['time_stamp']))
                {
                    //创建最新的用户数据
                    $sql_statement=$this->database_object->object->prepare("INSERT INTO {$table_name}(app_id,time_stamp,user,uuid,ukey) VALUES (:app_id,:time_stamp,:user,:uuid,:ukey)");
                    $uuid=$this->getRandomstring(22).time();
                    $sql_statement->bindParam(':app_id',$this->app_id);
                    $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                    $sql_statement->bindParam(':user',$user);
                    $sql_statement->bindParam(':uuid',$result_sql_temp['uuid']);
                    $sql_statement->bindParam(':ukey',$ukey);
                    if(!$sql_statement->execute())
                    {
                        $this->error_info['UserLogin']=array(
                            'code'=>1018,
                            'title'=>"失败",
                            'content'=>"异常错误",
                            'variable'=>''
                        );
                        return 0;
                    }
                }
                else
                {
                    //修改存储的数据(类似于前一次登录被强行下线)
                    $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET time_stamp=:time_stamp,ukey=:ukey WHERE uuid=:uuid AND app_id=:app_id");
                    $sql_statement->bindParam(':app_id',$this->app_id);
                    $sql_statement->bindParam(':uuid',$result_sql_temp['uuid']);
                    $sql_statement->bindParam(':ukey',$ukey);
                    $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                    if(!$sql_statement->execute())
                    {
                        $this->error_info['UserLogin']=array(
                            'code'=>1018,
                            'title'=>"失败",
                            'content'=>"异常错误",
                            'variable'=>''
                        );
                        return 0;
                    }
                }
                //返回uuid,ukey和昵称
                $this->uuid=$result_sql_temp['uuid'];
                $this->ukey=$ukey;
                $this->user_info['nickname']=$result_sql_temp['nickname'];
                return 1;
            }
            else
            {
                $this->error_info['UserLogin']=array(
                    'code'=>1032,
                    'title'=>"失败",
                    'content'=>"用户状态异常",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            $this->error_info['UserLogin']=array(
                'code'=>1031,
                'title'=>"失败",
                'content'=>"登录失败",
                'variable'=>''
            );
            return 0;
        }
    }

    /*
    *验证用户登录状态
    *验证用户登录后即可获取用户信息的用户名,验证用户登录成功不意味着用户是合法的,只是意味着用户已经登录过了,秘钥还是合法的
    */
    public function verificationUserLogin($uuid='',$ukey='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;
        if(empty($ukey))
            $ukey=$this->ukey;
        
        //取当前时间戳
        $server_time_stamp=time();
        //取数据表名
        $table_name=$this->database_object->getTablename('temporary_login_user');
        //删除过期的用户数据
        $sql_statement=$this->database_object->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-30*24*60*60));
        $sql_statement->execute();
        //尝试获取登录信息
        $sql_statement=$this->database_object->object->prepare("SELECT time_stamp,user FROM {$table_name} WHERE uuid=:uuid AND ukey=:ukey AND app_id=:app_id ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':uuid',$uuid);
        $sql_statement->bindParam(':ukey',$ukey);
        $sql_statement->bindParam(':app_id',$this->app_id);
        $sql_statement->execute();
        $result_sql_temp_login_user=$sql_statement->fetch();
        if(isset($result_sql_temp_login_user['time_stamp']))
            if(!empty($result_sql_temp_login_user['user'])&&$server_time_stamp<=$result_sql_temp_login_user['time_stamp']+30*24*60*60)
            {
                //返回用户名
                $this->user_info['name']=$result_sql_temp_login_user['user'];
                return 1;
            }
            else
            {
                $this->error_info['verificationUserLogin']=array(
                    'code'=>1035,
                    'title'=>"失败",
                    'content'=>"登录已过期",
                    'variable'=>''
                );
                return 0;
            }
        else
        {
            $this->error_info['verificationUserLogin']=array(
                'code'=>1031,
                'title'=>"失败",
                'content'=>"登录失败",
                'variable'=>''
            );
            return 0;
        }
    }

    /*
    *通过uuid和ukey获取用户信息
    *获取用户信息成功后即可获取全部用户信息和邮箱,qq号和微信号
    */
    public function getUserInfo($uuid='',$ukey='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;
        if(empty($ukey))
            $ukey=$this->ukey;
        
        //先验证是否是一个合法的登录
        if($this->verificationUserLogin($uuid,$ukey))
        {
            //获取用户信息
            $table_name=$this->database_object->getTablename('user');
            $sql_statement=$this->database_object->object->prepare("SELECT uuid,id,user,nickname,proving,time_stamp,qq,wechat,email,identification,integral FROM {$table_name} WHERE user=:user AND uuid=:uuid AND proving=1 ORDER BY id DESC LIMIT 0,1");
            $sql_statement->bindParam(':user',$this->user_info['name']);
            $sql_statement->bindParam(':uuid',$uuid);
            $sql_statement->execute();
            $result_sql_temp=$sql_statement->fetch();
            if(isset($result_sql_temp['uuid']))
            {
                //返回基础信息
                $this->user_info['get']=1;
                $this->user_info['id']=$result_sql_temp['id'];
                $this->user_info['name']=$result_sql_temp['user'];
                $this->user_info['uuid']=$result_sql_temp['uuid'];
                $this->user_info['nickname']=$result_sql_temp['nickname'];
                $this->user_info['proving']=$result_sql_temp['proving'];
                $this->user_info['time_stamp']=$result_sql_temp['time_stamp'];
                $this->user_info['time']=date("Y-m-d H:i:s",$result_sql_temp['time_stamp']);
                $this->user_info['integral']=$result_sql_temp['integral'];
                $this->user_info['identification']=$result_sql_temp['identification'];empty($this->identification_name[$result_sql_temp['identification']])?'1':$result_sql_temp['identification'];
                $this->user_info['identification_name']=$this->getIdentificationName($result_sql_temp['identification']);
                $this->user_qq=$result_sql_temp['qq'];
                $this->user_wechat=$result_sql_temp['wechat'];
                $this->user_email=$result_sql_temp['email'];
                return 1;
            }
            else
            {
                $this->error_info['getUserInfo']=array(
                    'code'=>1036,
                    'title'=>"失败",
                    'content'=>"用户状态异常",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            $this->error_info['getUserInfo']=$this->error_info['verificationUserLogin'];
            return 0;
        }
    }

    /*
    *通过user_name(必要参数)和uuid获取用户信息(相当于直接登陆成功)
    *获取用户信息成功后即可获取全部用户信息和邮箱,qq号和微信号
    */
    public function getUuidUserInfo($user_name,$uuid='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;

        //取数据表名
        $table_name=$this->database_object->getTablename('temporary_login_user');
        //删除过期的用户数据
        $sql_statement=$this->database_object->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-30*24*60*60));
        $sql_statement->execute();
        
        //获取用户信息
        $table_name=$this->database_object->getTablename('user');
        $sql_statement=$this->database_object->object->prepare("SELECT uuid,id,user,nickname,proving,time_stamp,qq,wechat,email,identification,integral FROM {$table_name} WHERE user=:user AND uuid=:uuid AND proving=1 ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':user',$user_name);
        $sql_statement->bindParam(':uuid',$uuid);
        $sql_statement->execute();
        $result_sql_temp=$sql_statement->fetch();
        if(isset($result_sql_temp['uuid']))
        {
            //返回基础信息
            $this->user_info['get']=1;
            $this->user_info['id']=$result_sql_temp['id'];
            $this->user_info['name']=$result_sql_temp['user'];
            $this->user_info['uuid']=$result_sql_temp['uuid'];
            $this->user_info['nickname']=$result_sql_temp['nickname'];
            $this->user_info['proving']=$result_sql_temp['proving'];
            $this->user_info['time_stamp']=$result_sql_temp['time_stamp'];
            $this->user_info['time']=date("Y-m-d H:i:s",$result_sql_temp['time_stamp']);
            $this->user_info['integral']=$result_sql_temp['integral'];
            $this->user_info['identification']=$result_sql_temp['identification'];empty($this->identification_name[$result_sql_temp['identification']])?'1':$result_sql_temp['identification'];
            $this->user_info['identification_name']=$this->getIdentificationName($result_sql_temp['identification']);
            $this->user_qq=$result_sql_temp['qq'];
            $this->user_wechat=$result_sql_temp['wechat'];
            $this->user_email=$result_sql_temp['email'];
            return 1;
        }
        else
        {
            $this->error_info['getUuidUserInfo']=array(
                'code'=>1036,
                'title'=>"失败",
                'content'=>"用户状态异常",
                'variable'=>''
            );
            return 0;
        }
    }

    /*
    *通过uuid(必要参数)获取用户信息,无视用户状态
    *直接返回一个用户信息的数组
    */
    public function getUuidInfo($uuid)
    {        
        //获取用户信息
        $table_name=$this->database_object->getTablename('user');
        $sql_statement=$this->database_object->object->prepare("SELECT uuid,id,user,nickname,proving,time_stamp,qq,wechat,email,identification,integral FROM {$table_name} WHERE uuid=:uuid ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':uuid',$uuid);
        $sql_statement->execute();
        $result_sql_temp=$sql_statement->fetch();
        if(isset($result_sql_temp['uuid']))
        {
            //返回基础信息
            $result_sql_temp['identification_name']=$this->getIdentificationName($result_sql_temp['identification']);
            return $result_sql_temp;
        }
        else
        {
            $this->error_info['getUuidInfo']=array(
                'code'=>1053,
                'title'=>"失败",
                'content'=>"非法请求",
                'variable'=>''
            );
            return 0;
        }
    }

    /*
    *通过uuid和ukey更改用户积分
    *成功返回1反之0,需要用户传入修改类型(1或者0,1为增加,0为减少)和数量
    */
    public function setUserIntegral($set_type,$set_number,$uuid='',$ukey='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;
        if(empty($ukey))
            $ukey=$this->ukey;
        
        //先验证是否是一个合法的登录
        if($this->getUserInfo($uuid,$ukey))
        {
            //获取处理后的用户积分数量
            $server_integral=$this->user_info['integral'];
            settype($server_integral,'int');
            if($set_type)
                $server_integral+=$set_number;
            else
                $server_integral-=$set_number;
            //处理积分不足的情况
            if($server_integral<0)
            {
                $this->error_info['setUserIntegral']=array(
                    'code'=>1041,
                    'title'=>"失败",
                    'content'=>"积分不足",
                    'variable'=>''
                );
                return 0;
            }
            //存储用户积分数据
            $table_name=$this->database_object->getTablename('user');
            $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET integral=:integral WHERE uuid=:uuid AND user=:user");
            $sql_statement->bindParam(':integral',$server_integral);
            $sql_statement->bindParam(':uuid',$uuid);
            $sql_statement->bindParam(':user',$this->user_info['name']);
            if($sql_statement->execute())
                return 1;
            else
            {
                $this->error_info['setUserIntegral']=array(
                    'code'=>1018,
                    'title'=>"失败",
                    'content'=>"异常错误",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            $this->error_info['setUserIntegral']=$this->error_info['getUserInfo'];
            return 0;
        }
    }

    /*
    *通过uuid给用户发送消息
    *成功返回1反之0,需要传入标题和内容。发送来源,附件和uuid为可选部分,发送来源system为系统消息,其余均为个人消息,不判断发送来源是否存在
    */
    public function sendUserMessage($title,$content,$frid='system',$annex='',$uuid='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;
        
        //先验证目标用户是否存在
        if($this->getUuidInfo($uuid))
        {
            //消息id(mgid)
            $mgid=$this->getRandstringid(1);
            $server_time_stamp=time();
            $annex_list=is_array($annex)?json_encode($annex):"";
            //存储用户积分数据
            $table_name=$this->database_object->getTablename('message_user');
            $sql_statement=$this->database_object->object->prepare("INSERT INTO {$table_name}(time_stamp,uuid,app_id,mgid,frid,msg_title,msg_content,annex_list) VALUES (:time_stamp,:uuid,:app_id,:mgid,:frid,:msg_title,:msg_content,:annex_list)");
            $sql_statement->bindParam(':time_stamp',$server_time_stamp);
            $sql_statement->bindParam(':uuid',$uuid);
            $sql_statement->bindParam(':app_id',$this->app_id);
            $sql_statement->bindParam(':mgid',$mgid);
            $sql_statement->bindParam(':frid',$frid);
            $sql_statement->bindParam(':msg_title',$title);
            $sql_statement->bindParam(':msg_content',$content);
            $sql_statement->bindParam(':annex_list',$annex_list);
            if($sql_statement->execute())
                return 1;
            else
            {
                $this->error_info['sendUserMessage']=array(
                    'code'=>1018,
                    'title'=>"失败",
                    'content'=>"异常错误",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            $this->error_info['sendUserMessage']=$this->error_info['getUuidInfo'];
            return 0;
        }
    }

    /*
    *通过mgid和uuid读取用户的消息
    *成功返回消息内容反之0,需要传入mgid,uuid为可选选项
    */
    public function getUserMessage($mgid,$uuid='')
    {
        if(empty($uuid))
            $uuid=$this->uuid;
        
        //先验证目标用户是否存在
        if($this->getUuidInfo($uuid))
        {
            //查询消息记录
            $table_name=$this->database_object->getTablename('message_user');
            $sql_statement=$this->database_object->object->prepare("SELECT time_stamp,uuid,app_id,mgid,frid,msg_title,msg_content,read_msg,annex_list FROM {$table_name} WHERE uuid=:uuid AND mgid=:mgid ORDER BY id DESC LIMIT 0,1");
            $sql_statement->bindParam(':uuid',$uuid);
            $sql_statement->bindParam(':mgid',$mgid);
            $sql_statement->execute();
            $result_sql_temp=$sql_statement->fetch();
            if(isset($result_sql_temp['time_stamp']))
            {
                //第一次读取就标为已读
                if($result_sql_temp['read_msg']==='N')
                {
                    $sql_statement=$this->database_object->object->prepare("UPDATE {$table_name} SET read_msg='Y' WHERE uuid=:uuid AND  uuid=:uuid AND mgid=:mgid");
                    $sql_statement->bindParam(':uuid',$uuid);
                    $sql_statement->bindParam(':mgid',$mgid);
                    $sql_statement->execute();
                }
                $result_data=array(
                    'time_stamp'=>$result_sql_temp['time_stamp'],
                    'time'=>date("Y-m-d H:i:s",$result_sql_temp['time_stamp']),
                    'uuid'=>$result_sql_temp['uuid'],
                    'app_id'=>$result_sql_temp['app_id'],
                    'mgid'=>$result_sql_temp['mgid'],
                    'frid'=>$result_sql_temp['frid'],
                    'msg_title'=>$result_sql_temp['msg_title'],
                    'msg_content'=>$result_sql_temp['msg_content'],
                    'annex_list'=>empty($result_sql_temp['annex_list'])?array():json_decode($result_sql_temp['annex_list']),
                    'system'=>$result_sql_temp['frid']==="system"?1:0
                );
                return $result_data;
            }
            else
            {
                $this->error_info['getUserMessage']=array(
                    'code'=>1057,
                    'title'=>"失败",
                    'content'=>"消息不存在",
                    'variable'=>''
                );
                return 0;
            }
        }
        else
        {
            $this->error_info['getUserMessage']=$this->error_info['getUuidInfo'];
            return 0;
        }
    }

/*类结束*/
}

?>