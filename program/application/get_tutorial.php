<?php

#program/application/get_tutorial

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
                if($_GET['from']==='list')
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
                        //显示已经通过审核的教程列表
                        $table_name=$Database->getTablename('tutorial');
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