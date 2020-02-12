<?php

#program/application/ta_tutorial

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
                if($_GET['from']==='get')
                {
                    if(empty($_POST['uuid'])||empty($_POST['ukey'])||empty($_POST['condition'])||empty($_POST['suid']))
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
                        //get环境下参数正常时执行
                        //补全不必要参数
                        if(empty($_POST['type']))
                        {
                            $_POST['type']='';
                        }
                        if(empty($_POST['page']))
                        {
                            $_POST['page']=1;
                        }
                        //get签名
                        $server_variable=array(
                            'from'=>$_GET['from'],
                            'app_id'=>$_POST['app_id'],
                            'nonce'=>$_POST['nonce'],
                            'time'=>$_POST['time'],
                            'time_stamp'=>$_POST['time_stamp'],
                            'uuid'=>$_POST['uuid'],
                            'ukey'=>$_POST['ukey'],
                            'condition'=>$_POST['condition'],
                            'suid'=>$_POST['suid'],
                            'type'=>$_POST['type'],
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
                            //get签名通过
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
                                $suid_user_info=$User->getUuidInfo($_POST['suid']);
                                if(isset($suid_user_info['uuid']))
                                {
                                    //目标用户存在
                                    //定义允许查看特殊状态的组别
                                    $server_identification_group=array(
                                        '0',            //超管组
                                        '100','101',    //管理组
                                        '1002'          //教程审核组
                                    );
                                    if(($_POST['condition']==="approved"&&$_POST['type']!=="admin")||in_array($User->user_info['identification'],$server_identification_group))
                                    {
                                        $table_name=$Database->getTablename('tutorial');
                                        if($_POST['condition']==="approved")
                                        {
                                            //显示已经通过审核的教程列表
                                            if($_POST['type']==="admin")
                                            {
                                                //自己负责的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='Y' AND ruid=:ruid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='Y' AND ruid=:ruid");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
                                            else
                                            {
                                                //自己的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='Y' AND uuid=:uuid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='Y' AND uuid=:uuid");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
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
                                            if($_POST['type']==="admin")
                                            {
                                                //自己负责的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='U' AND ruid=:ruid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='U' AND ruid=:ruid");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
                                            else
                                            {
                                                //自己的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='U' AND uuid=:uuid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='U' AND uuid=:uuid");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
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
                                        else if($_POST['condition']==="fail")
                                        {
                                            //显示没通过审核的教程列表
                                            if($_POST['type']==="admin")
                                            {
                                                //自己负责的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='N' AND ruid=:ruid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='N' AND ruid=:ruid");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
                                            else
                                            {
                                                //自己的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='N' AND uuid=:uuid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='N' AND uuid=:uuid");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
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
                                        else if($_POST['condition']==="pending")
                                        {
                                            //显示正在审核(待定)的教程列表
                                            if($_POST['type']==="admin")
                                            {
                                                //自己负责的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='R' AND ruid=:ruid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='R' AND ruid=:ruid");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
                                            else
                                            {
                                                //自己的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE restatus='R' AND uuid=:uuid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE restatus='R' AND uuid=:uuid");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
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
                                        else if($_POST['condition']==="all")
                                        {
                                            //显示所有教程列表
                                            if($_POST['type']==="admin")
                                            {
                                                //自己负责的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE ruid=:ruid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE ruid=:ruid");
                                                $sql_statement->bindParam(':ruid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
                                            else
                                            {
                                                //自己的教程列表
                                                $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,title FROM {$table_name} WHERE uuid=:uuid ORDER BY id DESC LIMIT {$limit},20");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_data=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
                                                $sql_statement=$Database->object->prepare("SELECT id FROM {$table_name} WHERE uuid=:uuid");
                                                $sql_statement->bindParam(':uuid',$_POST['suid']);
                                                $sql_statement->execute();
                                                $temp_return_count=count($sql_statement->fetchAll(PDO::FETCH_ASSOC));
                                            }
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
                                        $result_code=1042;
                                        $result_content='用户组无权限';
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
                                    $result['array']['application']=$User->error_info['getUuidInfo'];
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