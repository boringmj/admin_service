<?php

#program/application/tutorial

//引入User类文件
include_class("User");
//实例化User类
$User=new User();
//同步参数
$User->setStartSalt($main_config['user_info']['start_salt']);
$User->setEndSalt($main_config['user_info']['end_salt']);

if(empty($_GET['from']))
{
    $result_code=1009;
    $result_content='from没有指定目标';
    $result['array']['application']=array(
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
        $result['array']['application']=array(
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
                if($_GET['from']==='submit')
                {
                    if(empty($_POST['uuid'])||empty($_POST['ukey'])||empty($_POST['title'])||empty($_POST['content']))
                    {
                        $result_code=1011;
                        $result_content='必要参数为空';
                        $result['array']['application']=array(
                            'title'=>"失败",
                            'content'=>$result_content,
                            'code'=>$result_code,
                            'variable'=>""
                        );
                        $result['exit']=1;
                    }
                    else
                    {
                        //submit环境下参数正常时执行
                        //submit签名
                        $server_variable=array(
                            'from'=>$_GET['from'],
                            'app_id'=>$_POST['app_id'],
                            'nonce'=>$_POST['nonce'],
                            'time'=>$_POST['time'],
                            'time_stamp'=>$_POST['time_stamp'],
                            'uuid'=>$_POST['uuid'],
                            'ukey'=>$_POST['ukey'],
                            'title'=>$_POST['title'],
                            'content'=>$_POST['content']
                        );
                        $server_sign='';
                        foreach($server_variable as $key=>$value)
                        {
                            $server_sign.=$server_sign?"&{$key}=".getSignString($value):"{$key}=".getSignString($value);
                        }
                        $app_key=getAppkey($_POST['app_id']);
                        $server_sign.='&app_key='.getSignString($app_key);
                        $server_sign=md5($server_sign);
                        if($server_sign===$_POST['sign'])
                        {
                            //submit签名通过
                            //取当前时间戳
                            $server_time_stamp=time();
                            //取原始数据值
                            $_POST['title']=base64_decode($_POST['title']);
                            $_POST['content']=base64_decode($_POST['content']);
                            if(empty($_POST['title'])||empty($_POST['content']))
                            {
                                $result_code=1078;
                                $result_content='无法取得原始数据';
                                $result['array']['application']=array(
                                    'title'=>"失败",
                                    'content'=>$result_content,
                                    'code'=>$result_code,
                                    'variable'=>""
                                );
                                $result['exit']=1;
                            }
                            else
                            {
                                //抛弃超过系统最大存储空间的数据
                                $_POST['title']=mb_substr($_POST['title'],0,32);
                                $_POST['content']=mb_substr($_POST['content'],0,1024*5);
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
                                    //存储教程
                                    $table_name=$Database->getTablename('tutorial');
                                    $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(time_stamp,nickname,uuid,app_id,tid,like_number,reward_number,watch_number,restatus,title,content) VALUES (:time_stamp,:nickname,:uuid,:app_id,:tid,0,0,0,'U',:title,:content)");
                                    $tid=getRandstringid();
                                    $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                                    $sql_statement->bindParam(':nickname',$User->user_info['nickname']);
                                    $sql_statement->bindParam(':uuid',$_POST['uuid']);
                                    $sql_statement->bindParam(':app_id',$_POST['app_id']);
                                    $sql_statement->bindParam(':tid',$tid);
                                    $sql_statement->bindParam(':title',$_POST["title"]);
                                    $sql_statement->bindParam(':content',$_POST["content"]);
                                    if($sql_statement->execute())
                                    {
                                        $result_code=0;
                                        $result_content='提交成功';
                                        $result['array']['application']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>"请等待审核"
                                        );
                                    }
                                    else
                                    {
                                        $result_code=1018;
                                        $result_content='异常错误';
                                        $result['array']['application']=array(
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
                                    $result['array']['application']=$User->error_info['getUserInfo'];
                                    $result['exit']=1;
                                }
                            }   
                        }
                        else
                        {
                            $result_code=1014;
                            $result_content='非法请求';
                            $result['array']['application']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>$_POST['sign']
                            );
                            $result['exit']=1;
                        }
                    }
                }
                else if($_GET['from']==='list')
                {
                    if(empty($_POST['uuid'])||empty($_POST['ukey'])||empty($_POST['condition']))
                    {
                        $result_code=1011;
                        $result_content='必要参数为空';
                        $result['array']['application']=array(
                            'title'=>"失败",
                            'content'=>$result_content,
                            'code'=>$result_code,
                            'variable'=>""
                        );
                        $result['exit']=1;
                    }
                    else
                    {
                        //list环境下参数正常时执行
                        //补全不必要参数
                        if(empty($_POST['page']))
                        {
                            $_POST['page']=1;
                        }
                        //list签名
                        $server_variable=array(
                            'from'=>$_GET['from'],
                            'app_id'=>$_POST['app_id'],
                            'nonce'=>$_POST['nonce'],
                            'time'=>$_POST['time'],
                            'time_stamp'=>$_POST['time_stamp'],
                            'uuid'=>$_POST['uuid'],
                            'ukey'=>$_POST['ukey'],
                            'condition'=>$_POST['condition'],
                            'page'=>$_POST['page']
                        );
                        $server_sign='';
                        foreach($server_variable as $key=>$value)
                        {
                            $server_sign.=$server_sign?"&{$key}=".getSignString($value):"{$key}=".getSignString($value);
                        }
                        $app_key=getAppkey($_POST['app_id']);
                        $server_sign.='&app_key='.getSignString($app_key);
                        $server_sign=md5($server_sign);
                        if($server_sign===$_POST['sign'])
                        {
                            //list签名通过
                            //取当前时间戳
                            $server_time_stamp=time();
                            $limit=($_POST['page']-1)*20;
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
                                $table_name=$Database->getTablename('tutorial');
                                if($_POST['condition']==="approved")
                                {
                                    //显示已经通过审核的教程列表
                                    $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='Y' ORDER BY id DESC LIMIT {$limit},20");
                                    $sql_statement->execute();
                                    $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                    $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='Y'");
                                    $sql_statement->execute();
                                    $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                    $result_code=0;
                                    $result_content='获取成功';
                                    $result['array']['application']=array(
                                        'title'=>"成功",
                                        'content'=>$result_content,
                                        'code'=>$result_code,
                                        'variable'=>array(
                                            'count'=>$temp_return_count,
                                            'page'=>ceil($temp_return_count/20),
                                            'data'=>$temp_return_data
                                        )
                                    );
                                }
                                else if($_POST['condition']==="unprocessed")
                                {
                                    //显示还没处理的教程列表
                                    //定义允许显示未接取教程的用户组列表
                                    $server_identification_group=array(
                                        '0',            //超管组
                                        '100','101',    //管理组
                                        '1002','1003'   //教程审核组
                                    );
                                    //判断用户组是否允许更改
                                    if(in_array($User->user_info['identification'],$server_identification_group))
                                    {
                                        $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='U' ORDER BY id DESC LIMIT {$limit},20");
                                        $sql_statement->execute();
                                        $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                        $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='U'");
                                        $sql_statement->execute();
                                        $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                        $result_code=0;
                                        $result_content='获取成功';
                                        $result['array']['application']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'count'=>$temp_return_count,
                                                'page'=>ceil($temp_return_count/20),
                                                'data'=>$temp_return_data
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result_code=1042;
                                        $result_content='用户组无权限';
                                        $result['array']['application']=array(
                                            'title'=>"失败",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>''
                                        );
                                        $result['exit']=1;
                                    }
                                }
                                else if($_POST['condition']==="fail")
                                {
                                    //显示没通过审核的教程列表
                                    //定义允许显示未通过教程的用户组列表
                                    $server_identification_group=array(
                                        '0',            //超管组
                                        '100','101',    //管理组
                                        '1002'          //教程审核组
                                    );
                                    //判断用户组是否允许更改
                                    if(in_array($User->user_info['identification'],$server_identification_group))
                                    {
                                        $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='N' ORDER BY id DESC LIMIT {$limit},20");
                                        $sql_statement->execute();
                                        $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                        $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='N'");
                                        $sql_statement->execute();
                                        $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                        $result_code=0;
                                        $result_content='获取成功';
                                        $result['array']['application']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'count'=>$temp_return_count,
                                                'page'=>ceil($temp_return_count/20),
                                                'data'=>$temp_return_data
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result_code=1042;
                                        $result_content='用户组无权限';
                                        $result['array']['application']=array(
                                            'title'=>"失败",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>''
                                        );
                                        $result['exit']=1;
                                    }
                                }
                                else if($_POST['condition']==="pending")
                                {
                                    //显示正在审核(待定)的教程列表
                                    //定义允许显示正在审核教程的用户组列表
                                    $server_identification_group=array(
                                        '0',            //超管组
                                        '100','101',    //管理组
                                        '1002'          //教程审核组
                                    );
                                    //判断用户组是否允许更改
                                    if(in_array($User->user_info['identification'],$server_identification_group))
                                    {
                                        $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='R' ORDER BY id DESC LIMIT {$limit},20");
                                        $sql_statement->execute();
                                        $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                        $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='R'");
                                        $sql_statement->execute();
                                        $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                        $result_code=0;
                                        $result_content='获取成功';
                                        $result['array']['application']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'count'=>$temp_return_count,
                                                'page'=>ceil($temp_return_count/20),
                                                'data'=>$temp_return_data
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result_code=1042;
                                        $result_content='用户组无权限';
                                        $result['array']['application']=array(
                                            'title'=>"失败",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>''
                                        );
                                        $result['exit']=1;
                                    }
                                }
                                else if($_POST['condition']==="all")
                                {
                                    //显示所有教程列表
                                    //定义允许显示所有教程的用户组列表
                                    $server_identification_group=array(
                                        '0',            //超管组
                                        '100','101',    //管理组
                                        '1002'          //教程审核组
                                    );
                                    //判断用户组是否允许更改
                                    if(in_array($User->user_info['identification'],$server_identification_group))
                                    {
                                        $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} ORDER BY id DESC LIMIT {$limit},20");
                                        $sql_statement->execute();
                                        $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                        $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name}");
                                        $sql_statement->execute();
                                        $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                        $result_code=0;
                                        $result_content='获取成功';
                                        $result['array']['application']=array(
                                            'title'=>"成功",
                                            'content'=>$result_content,
                                            'code'=>$result_code,
                                            'variable'=>array(
                                                'count'=>$temp_return_count,
                                                'page'=>ceil($temp_return_count/20),
                                                'data'=>$temp_return_data
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $result_code=1042;
                                        $result_content='用户组无权限';
                                        $result['array']['application']=array(
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
                                    //不存在类型
                                    $result_code=1045;
                                    $result_content='非法请求';
                                    $result['array']['application']=array(
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
                                $result['array']['application']=$User->error_info['getUserInfo'];
                                $result['exit']=1;
                            }
                        }
                        else
                        {
                            $result_code=1014;
                            $result_content='非法请求';
                            $result['array']['application']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>$_POST['sign']
                            );
                            $result['exit']=1;
                        }
                    }
                }
                else
                {
                    $result_code=1010;
                    $result_content='from指定目标不合法';
                    $result['array']['application']=array(
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
                $result['array']['application']=array(
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
            $result['array']['application']=array(
                'title'=>"失败",
                'content'=>$result_content,
                'code'=>$result_code,
                'variable'=>""
            );
            $result['exit']=1;
        }
    }
}
?>