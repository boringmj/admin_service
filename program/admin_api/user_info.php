<?php

#program/admin_api/user_info

//做一些基础准备,不得不承认效率被降低了
include_class("Adminapi");
$Adminapi=new Adminapi();
$Adminapi->database_object=$Database;
$Adminapi->admin_config=$main_config['admin_config'];
//预定义基础变量方便存储其他数据
$user_info=array();
//检查接口是否处于正常可用状态
if($Adminapi->checkApi($_POST['api_id']))
{
    if($Adminapi->api_info['user_count']<$Adminapi->api_info['user_max'])
    {
        //注册接口是否已经开启
        if($Adminapi->api_info['ap_user_login_states']==='Y')
        {
            //检验基础参数是否已经传入
            if(empty($_POST['uuid'])||empty($_POST['ukey']))
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
                                'uuid'=>$_POST['uuid'],
                                'ukey'=>$_POST['ukey']
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
                //验证uuid是否和ukey匹配
                $table_name=$Database->getTablename('admin_api_temporary_login');
                $sql_statement=$Database->object->prepare("SELECT time_stamp,uuid FROM {$table_name} WHERE uuid=:uuid AND ukey=:ukey AND api_id=:api_id ORDER BY id DESC LIMIT 0,1");
                $sql_statement->bindParam(':uuid',$_POST['uuid']);
                $sql_statement->bindParam(':ukey',$_POST['ukey']);
                $sql_statement->bindParam(':api_id',$_POST['api_id']);
                $sql_statement->execute();
                $result_sql=$sql_statement->fetch();
                if(isset($result_sql['uuid'])&&$result_sql['uuid']===$_POST['uuid'])
                {
                    //先验证是否过期再考虑获取用户信息
                    if($result_sql['time_stamp']>=time()-30*24*60*60)
                    {
                        $table_name=$Database->getTablename('admin_api_user');
                        $sql_statement=$Database->object->prepare("SELECT user,uuid,nickname,proving,integral,ugroup,vip FROM {$table_name} WHERE uuid=:uuid AND api_id=:api_id ORDER BY id DESC LIMIT 0,1");
                        $sql_statement->bindParam(':uuid',$_POST['uuid']);
                        $sql_statement->bindParam(':api_id',$_POST['api_id']);
                        $sql_statement->execute();
                        $result_sql=$sql_statement->fetch(PDO::FETCH_ASSOC);
                        if(!empty($result_sql['user'])&&(($result_sql['proving']=="0"&&$Adminapi->api_info['ap_email_verification_states']==='N')||$result_sql['proving']=="1"))
                        {
                            //有绑定用户且用户状态正常即算验证通过,并传递信息
                            $user_info=$result_sql;
                        }
                        else
                        {
                            $result_code=99994;
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
                    else
                    {
                        $result_code=99995;
                        $result_content='令牌已过期';
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
                    $result_code=99996;
                    $result_content='无效的令牌';
                    $result['array']['admin_api']=array(
                        'title'=>"失败",
                        'content'=>$result_content,
                        'code'=>$result_code,
                        'variable'=>""
                    );
                    $result['exit']=1;
                }
            }
            //没有出现错误即视为通过鉴权验证
            if(!$result['exit'])
            {
                //这样写主要是给以后留轮子
                $result_code=0;
                $result_content='获取成功';
                $result['array']['admin_api']=array(
                    'title'=>"成功",
                    'content'=>$result_content,
                    'code'=>$result_code,
                    'variable'=>$user_info
                );
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