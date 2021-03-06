<?php

#program/admin_api/register

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
        if($Adminapi->api_info['ap_user_register_states']==='Y')
        {
            //检验基础参数是否已经传入
            if(empty($_POST['user'])||empty($_POST['nickname'])||empty($_POST['email'])||empty($_POST['password']))
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
                                'nickname'=>$_POST['nickname'],
                                'email'=>$_POST['email'],
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
                $_POST['email']=mb_substr($_POST['email'],0,32);
                //目前系统没有提供用户自定义昵称长度限制,所以昵称长度在0-32个字符之间都算是合法的
                $_POST['nickname']=mb_substr($_POST['nickname'],0,32);
                //检验数据是否合法
                if(preg_match('/^[_0-9a-zA-Z]{'.$Adminapi->api_info['ap_user_min'].','.$Adminapi->api_info['ap_user_max'].'}$/i',$_POST['user']))
                {
                    if(isEmail($_POST['email'])&&mb_strlen($_POST['email'])<=$Adminapi->api_info['mail_max'])
                    {
                        //检验用户名或邮箱是否已经被使用
                        $table_name=$Database->getTablename('admin_api_user');
                        $sql_statement=$Database->object->prepare("SELECT user FROM {$table_name} WHERE user=:user AND email=:email AND span_id=:span_id  ORDER BY id DESC LIMIT 0,1");
                        $sql_statement->bindParam(':user',$_POST['user']);
                        $sql_statement->bindParam(':email',$_POST['email']);
                        $sql_statement->bindParam(':span_id',$Adminapi->api_info['user_library']);
                        $sql_statement->execute();
                        $result_sql=$sql_statement->fetch();
                        if(isset($result_sql['user'])&&$result_sql['user']===$_POST['user'])
                        {
                            $result_code=1020;
                            $result_content='用户已存在或邮箱已使用';
                            $result['array']['admin_api']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>array(
                                    "email"=>$_POST['email'],
                                    "user"=>$_POST['user']
                                )
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //将用户信息写入到数据库中
                            $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(api_id,time_stamp,email,uuid,user,nickname,passwd,span_id,proving) VALUES (:api_id,:time_stamp,:email,:uuid,:user,:nickname,:passwd,:span_id,0)");
                            $server_temp_time=time();
                            $uuid=getRandstringid();
                            //密码就这样处理吧
                            $passwd=md5($_POST['api_id'].md5(base64_encode($_POST["password"])));
                            $sql_statement->bindParam(':api_id',$_POST['api_id']);
                            $sql_statement->bindParam(':time_stamp',$server_temp_time);
                            $sql_statement->bindParam(':email',$_POST['email']);
                            $sql_statement->bindParam(':uuid',$uuid);
                            $sql_statement->bindParam(':user',$_POST['user']);
                            $sql_statement->bindParam(':nickname',$_POST['nickname']);
                            $sql_statement->bindParam(':passwd',$passwd);
                            $sql_statement->bindParam(':span_id',$Adminapi->api_info['user_library']);
                            if($sql_statement->execute())
                            {
                                //检查是否需要发送验证邮件(请不要强行开启发送邮件)
                                if($Adminapi->api_info['ap_email_verification_states']==='Y')
                                {
                                    //这里貌似还得花时间写一下
                                    //出于其他目的这里需要精心设计过
                                }
                                else
                                {
                                    $result_code=0;
                                    $result_content='注册成功';
                                    $result['array']['admin_api']=array(
                                        'title'=>"成功",
                                        'content'=>$result_content,
                                        'code'=>$result_code,
                                        'variable'=>array(
                                            "uuid"=>$uuid,
                                            "user"=>$_POST['user'],
                                            "email"=>$_POST['email'],
                                            "time_stamp"=>$server_temp_time,
                                            "time"=>date("Y-m-d H:i:s",$server_temp_time),
                                            "nickname"=>$_POST["nickname"]
                                        )
                                    );
                                }
                                if(!$result['exit'])
                                {
                                    //没有出现错误就记录一次用户注册成功(也就是消耗一次注册机会)
                                    $table_name=$Database->getTablename('admin_application');
                                    $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET user_count=:user_count WHERE api_id=:api_id");
                                    $sql_statement->bindParam(':api_id',$_POST['api_id']);
                                    $user_count=$Adminapi->api_info['user_count']+1;
                                    $sql_statement->bindParam(':user_count',$user_count);
                                    $sql_statement->execute();
                                }
                            }
                            else
                            {
                                $result_code=1018;
                                $result_content='异常错误';
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
                        $result_code=1022;
                        $result_content='邮箱不合法';
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
                    $result_code=1021;
                    $result_content='用户不合法';
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