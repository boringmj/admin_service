<?php

#program/api/login

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
if(getPermission($_POST['app_id'],'api_login')==='Y')
{
    if(empty($_GET['from']))
    {
        $result_code=1009;
        $result_content='from没有指定目标';
        $result['array']['api']=array(
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
            $result['array']['api']=array(
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
                    if($_GET['from']==='app')
                    {
                        if(empty($_POST['user'])||empty($_POST['passwd'])||empty($_POST['imei']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['api']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //app环境下参数正常时执行
                            //app签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'imei'=>$_POST['imei'],
                                'user'=>$_POST['user'],
                                'passwd'=>$_POST['passwd']
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
                                $result['array']['api']=array(
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
                                    //app签名验证通过
                                    //设置必要参数
                                    $User->app_id=$_POST['app_id'];
                                    $User->database_object=$Database;
                                    //尝试登录
                                    if($User->UserLogin($_POST['user'],$_POST['passwd']))
                                    {
                                        //返回uuid,ukey和昵称
                                        $result_code=0;
                                        $result_content="登录成功";
                                        $result['array']['api']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'uuid'=>$User->uuid,
                                                'ukey'=>$User->ukey,
                                                'nickname'=>$User->user_info['nickname']
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result['array']['api']=$User->error_info['UserLogin'];
                                        $result['exit']=1;
                                    }
                                }
                                else
                                {
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['api']=array(
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
                    else if($_GET['from']==='web')
                    {
                        if(empty($_POST['user'])||empty($_POST['passwd']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['api']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //web环境下参数正常时执行
                            //web签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'user'=>$_POST['user'],
                                'passwd'=>$_POST['passwd']
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
                                $result['array']['api']=array(
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
                                    //web签名验证通过
                                    //设置必要参数
                                    $User->app_id=$_POST['app_id'];
                                    $User->database_object=$Database;
                                    //尝试登录
                                    if($User->UserLogin($_POST['user'],$_POST['passwd']))
                                    {
                                        //存储session
                                        session_start();
                                        $_SESSION['uuid']=$User->uuid;
                                        $_SESSION['ukey']=$User->ukey;
                                        $_SESSION['app_id']=$_POST['app_id'];
                                        //将信息返回给客户端
                                        setcookie("uuid",$User->uuid);
                                        //返回uuid,ukey和昵称
                                        $result_code=0;
                                        $result_content="登录成功";
                                        $result['array']['api']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'uuid'=>$User->uuid,
                                                'ukey'=>$User->ukey,
                                                'nickname'=>$User->user_info['nickname']
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result['array']['api']=$User->error_info['UserLogin'];
                                        $result['exit']=1;
                                    }
                                }
                                else
                                {
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['api']=array(
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
                        $result['array']['api']=array(
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
                    $result['array']['api']=array(
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
                $result['array']['api']=array(
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
    $result['array']['api']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>''
    );
    $result['exit']=1;
}
?>