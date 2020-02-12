<?php

#program/qq/bind

//引入邮件配置
include_once 'config/mail.php';

//引入User类文件
include_class("User");
//实例化User类
$User=new User();
//同步参数
$User->setStartSalt($main_config['user_info']['start_salt']);
$User->setEndSalt($main_config['user_info']['end_salt']);

//补全预处理参数
if(empty($_POST['app_id']))
    $_POST['app_id']="";
if(getPermission($_POST['app_id'],'qq_bind')==='Y')
{
    if(empty($_GET['from']))
    {
        $result_code=1009;
        $result_content='from没有指定目标';
        $result['array']['qq']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>""
        );
        $result['exit']=1;
    }
    else
    {
        if(empty($_POST['app_id'])||empty($_POST['sign'])||empty($_POST['nonce'])||!(!empty($_POST['time'])||!empty($_POST['time_stamp'])))
        {
            $result_code=1011;
            $result_content='必要参数为空';
            $result['array']['qq']=array(
                'title'=>"失败",
                'content'=>$result_content,
                'code'=>$result_code,
                'variable'=>""
            );
            $result['exit']=1;
        }
        else
        {
            //补齐不必要参数
            if(empty($_POST['time']))
                $_POST['time']='';
            if(empty($_POST['time_stamp']))
                $_POST['time_stamp']='';
            //取时间戳
            if(!empty($_POST['time_stamp']))
            $time=$_POST['time_stamp'];
            else
            $time=strtotime($_POST['time']);
            settype($time,'int');

            //判断时差是否在规定时间内
            if(time()-$time<=5*60&&time()-$time>=-(5*60))
            {
                if(getConce($_POST['nonce'],$_POST['sign'],$_POST['app_id']))
                {
                    //判断接口环境
                    if($_GET['from']==='get')
                    {
                        if(empty($_POST['uuid'])||empty($_POST['ukey'])||empty($_POST['imei']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['qq']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //get环境下参数正常时执行
                            //get签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'imei'=>$_POST['imei'],
                                'uuid'=>$_POST['uuid'],
                                'ukey'=>$_POST['ukey']
                            );
                            $server_sign='';
                            foreach($server_variable as $key=>$value)
                            {
                                $server_sign.=$server_sign?"&{$key}=".getSignString($value):"{$key}=".getSignString($value);
                            }
                            $app_key=getAppkey($_POST['app_id']);
                            if(empty($app_key))
                            {
                                $result_code=1013;
                                $result_content='非法请求';
                                $result['array']['qq']=array(
                                    'title'=>"失败",
                                    'content'=>$result_content,
                                    'code'=>$result_code,
                                    'variable'=>""
                                );
                                $result['exit']=1;
                            }
                            else
                            {
                                $server_sign.='&app_key='.getSignString($app_key);
                                $server_sign=md5($server_sign);
                                if($server_sign===$_POST['sign'])
                                {
                                    //get签名验证通过
                                    //设置必要参数
                                    $User->app_id=$_POST['app_id'];
                                    $User->database_object=$Database;
                                    //设置uuid和ukey
                                    $User->setUuid($_POST['uuid']);
                                    $User->setUkey($_POST['ukey']);
                                    //获取用户信息(直接判断是否登录无法验证用户是否处于激活状态)
                                    $User->getUserInfo();
                                    //验证用户是否登录成功
                                    if($User->user_info['get'])
                                    {
                                        //取当前时间戳
                                        $server_time_stamp=time();
                                        //生成raid
                                        $raid=md5(getRandomstring(22).time());
                                        //取数据表名称
                                        $table_name=$Database->getTablename('temporary_qq_bind_user');
                                        //删除过期的用户数据
                                        $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-24*60*60));
                                        $sql_statement->execute();
                                        //删除该用户的历史申请,方便直接创建
                                        $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE uuid=:uuid");
                                        $sql_statement->bindParam(':uuid',$_POST['uuid']);
                                        $sql_statement->execute();
                                        //创建申请
                                        $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(app_id,time_stamp,user,uuid,raid) VALUES (:app_id,:time_stamp,:user,:uuid,:raid)");
                                        $sql_statement->bindParam(':app_id',$_POST['app_id']);
                                        $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                                        $sql_statement->bindParam(':user',$User->user_info['name']);
                                        $sql_statement->bindParam(':uuid',$_POST['uuid']);
                                        $sql_statement->bindParam(':raid',$raid);
                                        if($sql_statement->execute())
                                        {
                                            $result_code=0;
                                            $result_content='申请成功';
                                            $result['array']['qq']=array(
                                                'title'=>"成功",
                                                'content'=>$result_content,
                                                'code'=>$result_code,
                                                'variable'=>array(
                                                    'uuid'=>$_POST['uuid'],
                                                    'raid'=>$raid
                                                )
                                            );
                                            $result['exit']=1;
                                        }
                                        else
                                        {
                                            $result_code=1018;
                                            $result_content='异常错误';
                                            $result['array']['qq']=array(
                                                'title'=>"失败",
                                                'content'=>$result_content,
                                                'code'=>$result_code,
                                                'variable'=>""
                                            );
                                            $result['exit']=1;
                                        }
                                    }
                                    else
                                    {
                                        $result['array']['qq']=$User->error_info['getUserInfo'];
                                        $result['exit']=1;
                                    }
                                }
                                else
                                {
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['qq']=array(
                                        'title'=>"失败",
                                        'content'=>$result_content,
                                        'code'=>$result_code,
                                        'variable'=>$_POST['sign']
                                    );
                                    $result['exit']=1;
                                }
                            }
                        }
                    }
                    else if($_GET['from']==='bind')
                    {
                        if(empty($_POST['raid'])||empty($_POST['qq']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['qq']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //bind环境下参数正常时执行
                            //bind签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'raid'=>$_POST['raid'],
                                'qq'=>$_POST['qq']
                            );
                            $server_sign='';
                            foreach($server_variable as $key=>$value)
                            {
                                $server_sign.=$server_sign?"&{$key}=".getSignString($value):"{$key}=".getSignString($value);
                            }
                            $app_key=getAppkey($_POST['app_id']);
                            if(empty($app_key))
                            {
                                $result_code=1013;
                                $result_content='非法请求';
                                $result['array']['qq']=array(
                                    'title'=>"失败",
                                    'content'=>$result_content,
                                    'code'=>$result_code,
                                    'variable'=>""
                                );
                                $result['exit']=1;
                            }
                            else
                            {
                                $server_sign.='&app_key='.getSignString($app_key);
                                $server_sign=md5($server_sign);
                                if($server_sign===$_POST['sign'])
                                {
                                    //bind签名验证通过
                                    //设置必要参数
                                    $User->app_id=$_POST['app_id'];
                                    $User->database_object=$Database;
                                    //取当前时间戳
                                    $server_time_stamp=time();
                                    //抛弃超过系统最大存储空间的数据
                                    $qq=substr($_POST['qq'],0,32);
                                    //取数据表名称
                                    $table_name=$Database->getTablename('temporary_qq_bind_user');
                                    //删除过期的用户数据
                                    $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-24*60*60));
                                    $sql_statement->execute();
                                    //尝试查询
                                    $sql_statement=$Database->object->prepare("SELECT uuid,user FROM {$table_name} WHERE app_id=:app_id AND raid=:raid ORDER BY id DESC LIMIT 0,1");
                                    $sql_statement->bindParam(':app_id',$_POST['app_id']);
                                    $sql_statement->bindParam(':raid',$_POST['raid']);
                                    $sql_statement->execute();
                                    $result_sql_temp=$sql_statement->fetch();
                                    if(isset($result_sql_temp['uuid'])&&isset($result_sql_temp['user']))
                                    {
                                        if($User->getUuidUserInfo($result_sql_temp['user'],$result_sql_temp['uuid']))
                                        {
                                            //删除已使用的raid
                                            $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE raid=:raid");
                                            $sql_statement->bindParam(':raid',$_POST['raid']);
                                            $sql_statement->execute();
                                            //验证用户是否已绑定
                                            if(!$User->getUserQq())
                                            {
                                                //用户没有绑定qq号
                                                $table_name=$Database->getTablename('user');
                                                $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET qq=:qq WHERE uuid=:uuid AND user=:user");
                                                $sql_statement->bindParam(':qq',$qq);
                                                $sql_statement->bindParam(':uuid',$result_sql_temp['uuid']);
                                                $sql_statement->bindParam(':user',$result_sql_temp['user']);
                                                if($sql_statement->execute())
                                                {
                                                    //发送绑定邮件,暂不验证发送是否成功
                                                    $content_array=array(
                                                        "\${title}"=>"绑定成功",
                                                        "\${organization}"=>$main_config['organization_config']['name'],
                                                        "\${date}"=>$ban_date=date("Y-m-d H:i:s",$server_time_stamp),
                                                        "\${user_name}"=>$result_sql_temp['user'],
                                                        "\${qq_code}"=>substr($qq,0,3)."***".substr($qq,-2)
                                                    );
                                                    $Sendmail->send("绑定通知",$User->getUserEmail(),"default/qq_bind",$content_array);
                                                    //返回信息
                                                    $result_code=0;
                                                    $result_content='绑定成功';
                                                    $result['array']['qq']=array(
                                                        'title'=>"成功",
                                                        'content'=>$result_content,
                                                        'code'=>$result_code,
                                                        'variable'=>array(
                                                            'user'=>$result_sql_temp['user']
                                                        )
                                                    );
                                                }
                                                else
                                                {
                                                    $result_code=1018;
                                                    $result_content='异常错误';
                                                    $result['array']['qq']=array(
                                                        'title'=>"失败",
                                                        'content'=>$result_content,
                                                        'code'=>$result_code,
                                                        'variable'=>""
                                                    );
                                                    $result['exit']=1;
                                                }
                                            }
                                            else
                                            {
                                                //用户已经绑定了qq号
                                                if($qq===$User->getUserQq())
                                                {
                                                    $result_code=1038;
                                                    $result_content='用户已绑定该qq号';
                                                    $result['array']['qq']=array(
                                                        'title'=>"失败",
                                                        'content'=>$result_content,
                                                        'code'=>$result_code,
                                                        'variable'=>""
                                                    );
                                                    $result['exit']=1;
                                                }
                                                else
                                                {
                                                    $result_code=1039;
                                                    $result_content='用户已绑定其他qq号';
                                                    $result['array']['qq']=array(
                                                        'title'=>"失败",
                                                        'content'=>$result_content,
                                                        'code'=>$result_code,
                                                        'variable'=>""
                                                    );
                                                    $result['exit']=1;
                                                }
                                            }
                                        }
                                        else
                                        {
                                            $result['array']['qq']=$User->error_info['getUuidUserInfo'];
                                            $result['exit']=1;
                                        }
                                    }
                                    else
                                    {
                                        $result_code=1037;
                                        $result_content='无法查询绑定信息';
                                        $result['array']['qq']=array(
                                            'title'=>"失败",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>''
                                        );
                                        $result['exit']=1;
                                    }
                                }
                                else
                                {
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['qq']=array(
                                        'title'=>"失败",
                                        'content'=>$result_content,
                                        'code'=>$result_code,
                                        'variable'=>$_POST['sign']
                                    );
                                    $result['exit']=1;
                                }
                            }
                        }
                    }
                    else
                    {
                        $result_code=1010;
                        $result_content='from指定目标不合法';
                        $result['array']['qq']=array(
                            'title'=>"失败",
                            'content'=>$result_content,
                            'code'=>$result_code,
                            'variable'=>$_GET['from']
                        );
                        $result['exit']=1;
                    }
                }
                else
                {
                    $result_code=1015;
                    $result_content='请求已过期';
                    $result['array']['qq']=array(
                        'title'=>"失败",
                        'content'=>$result_content,
                        'code'=>$result_code,
                        'variable'=>""
                    );
                    $result['exit']=1;
                }
            }
            else
            {
                $result_code=1012;
                $result_content='请求已过期';
                $result['array']['qq']=array(
                    'title'=>"失败",
                    'content'=>$result_content,
                    'code'=>$result_code,
                    'variable'=>""
                );
                $result['exit']=1;
            }
        }
    }
}
else
{
    $result_code=10000;
    $result_content='无权调用接口';
    $result['array']['qq']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>''
    );
    $result['exit']=1;
}
?>