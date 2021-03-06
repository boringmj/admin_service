<?php

#program/admin_api/login

//做一些基础准备,不得不承认效率被降低了
include_class("Adminapi");
$Adminapi=new Adminapi();
$Adminapi->database_object=$Database;
$Adminapi->admin_config=$main_config['admin_config'];
//检查接口是否处于正常可用状态
if($Adminapi->checkApi($_POST['api_id']))
{
    if($Adminapi->api_info['user_count']<$Adminapi->api_info['user_max'])
    {
        //注册接口是否已经开启
        if($Adminapi->api_info['ap_user_login_states']==='Y')
        {
            //检验基础参数是否已经传入
            if(empty($_POST['user'])||empty($_POST['password']))
            {
                $result_code=1011;
                $result_content='必要参数为空';
                $result['array']['admin_api']=array(
                    'title'=>"失败",
                    'content'=>$result_content,
                    'code'=>$result_code,
                    'variable'=>""
                );
                $result['exit']=1;
            }
            //检验是否需要签名
            if(!$result['exit']&&$Adminapi->api_info['ap_sign_states']==='Y')
            {
                //一旦涉及签名就应该出现三重验证
                if(empty($_POST['sign'])||empty($_POST['nonce'])||!(!empty($_POST['time'])||!empty($_POST['time_stamp'])))
                {
                    $result_code=1011;
                    $result_content='必要参数为空';
                    $result['array']['admin_api']=array(
                        'title'=>"失败",
                        'content'=>$result_content,
                        'code'=>$result_code,
                        'variable'=>""
                    );
                    $result['exit']=1;
                }
                else
                {
                    //取时间戳
                    if(!empty($_POST['time_stamp']))
                    $time=$_POST['time_stamp'];
                    else
                    $time=strtotime($_POST['time']);
                    settype($time,'int');

                    //判断时差是否在规定时间内
                    if(time()-$time<=5*60&&time()-$time>=-(5*60))
                    {
                        if(getConce($_POST['nonce'],$_POST['sign'],$_POST['api_id']))
                        {
                            //环境下参数正常时执行
                            //签名
                            $server_variable=array(
                                'api_id'=>$_POST['api_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'user'=>$_POST['user'],
                                'password'=>$_POST['password']
                            );
                            $server_sign='';
                            foreach($server_variable as $key=>$value)
                            {
                                $server_sign.=$server_sign?"&{$key}=".getSignString($value):"{$key}=".getSignString($value);
                            }
                            $api_key=$Adminapi->api_info['api_key'];
                            $server_sign.='&api_key='.getSignString($api_key);
                            $server_sign=md5($server_sign);
                            if($server_sign!=$_POST['sign'])
                            {
                                $result_code=1068;
                                $result_content='非法请求';
                                $result['array']['admin_api']=array(
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
                            $result_code=1068;
                            $result_content='非法请求';
                            $result['array']['admin_api']=array(
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
                        $result_code=1068;
                        $result_content='非法请求';
                        $result['array']['admin_api']=array(
                            'title'=>"失败",
                            'content'=>$result_content,
                            'code'=>$result_code,
                            'variable'=>""
                        );
                        $result['exit']=1;
                    }
                }
            }
            //所有条件满足即视为验证通过(前面还可以添加人机识别之类的)
            if(!$result['exit'])
            {
                //预处理超出限制数据
                $_POST['user']=mb_substr($_POST['user'],0,32);
                //用户登录事件处理
                $table_name=$Database->getTablename('admin_api_user');
                $sql_statement=$Database->object->prepare("SELECT user,uuid,nickname,proving FROM {$table_name} WHERE user=:user AND passwd=:passwd AND span_id=:span_id ORDER BY id DESC LIMIT 0,1");
                $passwd=md5($_POST['api_id'].md5(base64_encode($_POST["password"])));
                $sql_statement->bindParam(':user',$_POST['user']);
                $sql_statement->bindParam(':passwd',$passwd);
                $sql_statement->bindParam(':span_id',$Adminapi->api_info['user_library']);
                $sql_statement->execute();
                $result_sql=$sql_statement->fetch();
                if(isset($result_sql['user'])&&$result_sql['user']===$_POST['user'])
                {
                    //验证用户状态是否正常,0为为验证邮箱,1为正常用户
                    if(($result_sql['proving']=="0"&&$Adminapi->api_info['ap_email_verification_states']==='N')||$result_sql['proving']=="1")
                    {
                        //用户登录成功,接下来就是用户鉴权处理(更新并存储ukey),先销毁之前的,在重新插入
                        $table_name=$Database->getTablename('admin_api_temporary_login');
                        $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE api_id=:api_id AND uuid=:uuid");
                        $sql_statement->bindParam(':api_id',$_POST['api_id']);
                        $sql_statement->bindParam(':uuid',$result_sql['uuid']);
                        $sql_statement->execute();
                        //这里重新插入数据(相当于更新了)
                        $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(api_id,time_stamp,uuid,ukey) VALUES (:api_id,:time_stamp,:uuid,:ukey)");
                        $server_temp_time=time();
                        $ukey=getRandomstring(32);
                        $sql_statement->bindParam(':api_id',$_POST['api_id']);
                        $sql_statement->bindParam(':time_stamp',$server_temp_time);
                        $sql_statement->bindParam(':uuid',$result_sql['uuid']);
                        $sql_statement->bindParam(':ukey',$ukey);
                        if($sql_statement->execute())
                        {
                            $result_code=0;
                            $result_content='登录成功';
                            $result['array']['admin_api']=array(
                                'title'=>"成功",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>array(
                                    'uuid'=>$result_sql['uuid'],
                                    'ukey'=>$ukey,
                                    'nickname'=>$result_sql['nickname']
                                )
                            );
                        }
                        else
                        {
                            $result_code=99997;
                            $result_content='系统异常';
                            $result['array']['admin_api']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>$result_sql['proving']
                            );
                            $result['exit']=1;
                        }
                    }
                    else
                    {
                        $result_code=99998;
                        $result_content='用户状态异常';
                        $result['array']['admin_api']=array(
                            'title'=>"失败",
                            'content'=>$result_content,
                            'code'=>$result_code,
                            'variable'=>$result_sql['proving']
                        );
                        $result['exit']=1;
                    }
                }
                else
                {
                    $result_code=99999;
                    $result_content='用户名或密码错误';
                    $result['array']['admin_api']=array(
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
            $result_code=1067;
            $result_content='接口已关闭';
            $result['array']['admin_api']=array(
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
        $result_code=1066;
        $result_content='用户超出上限';
        $result['array']['admin_api']=array(
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
    $result['array']['admin_api']=$Adminapi->error_info['checkApi'];
    $result['exit']=1;
}

?>