<?php

#program/verifycode

//导入Verifycode类
include_class('Verifycode');
$Verifycode=new Verifycode();

//补全预处理参数
if(empty($_POST['app_id']))
    $_POST['app_id']="";
if(getPermission($_POST['app_id'],'verifycode')==='Y')
{
    if(empty($_GET['from']))
    {
        $result_code=1009;
        $result_content='from没有指定目标';
        $result['array']['verifycode']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>""
        );
        $result['exit']=1;
    }
    else
    {
        //限制补全的模式
        if($_GET['from']==='image')
        {
            $request_array=array("app_id","sign","nonce","time","time_stamp","imgid");
            foreach($request_array as $value)
            {
                if(!isset($_POST[$value])&&isset($_GET[$value]))
                    $_POST[$value]=$_GET[$value];
            }
        }

        //判断基础数据是否完整
        if(empty($_POST['app_id'])||empty($_POST['sign'])||empty($_POST['nonce'])||!(!empty($_POST['time'])||!empty($_POST['time_stamp'])))
        {
            $result_code=1011;
            $result_content='必要参数为空';
            $result['array']['verifycode']=array(
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
                        //get签名
                        $server_variable=array(
                            'from'=>$_GET['from'],
                            'app_id'=>$_POST['app_id'],
                            'nonce'=>$_POST['nonce'],
                            'time'=>$_POST['time'],
                            'time_stamp'=>$_POST['time_stamp']
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
                            $result['array']['verifycode']=array(
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
                                //创建零时验证码数据
                                $server_time_stamp=time();
                                $imgid=getRandomstring(22).time();
                                $code_value=rand(0,18);
                                $table_name=$Database->getTablename('temporary_verifycode');
                                //清理过期数据
                                $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-3*60*60));
                                $sql_statement->execute();
                                //存储本次请求
                                $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(app_id,time_stamp,code_value,imgid) VALUES (:app_id,:time_stamp,:code_value,:imgid)");
                                $uuid=getRandomstring(22).time();
                                $sql_statement->bindParam(':app_id',$_POST['app_id']);
                                $sql_statement->bindParam(':time_stamp',$server_time_stamp);
                                $sql_statement->bindParam(':code_value',$code_value);
                                $sql_statement->bindParam(':imgid',$imgid);
                                if($sql_statement->execute())
                                {
                                    $result_code=0;
                                    $result_content='零时验证码申请通过';
                                    $result['array']['verifycode']=array(
                                        'title'=>"成功",
                                        'content'=>$result_content,
                                        'code'=>$result_code,
                                        'variable'=>array(
                                            'imgid'=>$imgid
                                        )
                                    );
                                }
                                else
                                {
                                    $result_code=1018;
                                    $result_content='异常错误';
                                    $result['array']['verifycode']=array(
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
                                $result_code=1014;
                                $result_content='非法请求';
                                $result['array']['verifycode']=array(
                                    'title'=>"失败",
                                    'content'=>$result_content,
                                    'code'=>$result_code,
                                    'variable'=>$_POST['sign']
                                );
                                $result['exit']=1;
                            }
                        }
                    }
                    else if($_GET['from']==='image')
                    {
                        if(empty($_POST['imgid']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['verifycode']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //image签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'imgid'=>$_POST['imgid']
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
                                $result['array']['verifycode']=array(
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
                                    //image签名验证通过
                                    //取当前时间戳 
                                    $server_time_stamp=time();

                                    //验证imgid是否已过期,未过期刷新验证码并给出图片验证码
                                    $table_name=$Database->getTablename('temporary_verifycode');
                                    $sql_statement=$Database->object->prepare("SELECT time_stamp FROM {$table_name} WHERE imgid=:imgid ORDER BY id DESC LIMIT 0,1");
                                    $sql_statement->bindParam(':imgid',$_POST['imgid']);
                                    $sql_statement->execute();
                                    $result_sql_temp=$sql_statement->fetch();
                                    if(isset($result_sql_temp['time_stamp'])&&$server_time_stamp<=$result_sql_temp['time_stamp']+3*60*60)
                                    {   
                                        $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET code_value=:code_value WHERE imgid=:imgid");
                                        $firstNum=rand(1,9);
                                        $secondNum=rand(1,9);
                                        $code_value=$firstNum>$secondNum?$firstNum-$secondNum:$firstNum+$secondNum;
                                        $sql_statement->bindParam(':code_value',$code_value);
                                        $sql_statement->bindParam(':imgid',$_POST['imgid']);
                                        if($sql_statement->execute())
                                        {
                                            //使用其他输出,以便将图片正常输出
                                            $result['mode']=3;
                                            include_class('Verifycode');
                                            $Verifycode->get($firstNum,$secondNum);
                                        }
                                        else
                                        {
                                            $result_code=1018;
                                            $result_content='异常错误';
                                            $result['array']['verifycode']=array(
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
                                        $result_code=1033;
                                        $result_content='请求已过期';
                                        $result['array']['verifycode']=array(
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
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['verifycode']=array(
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
                    else if($_GET['from']==='check')
                    {
                        if(empty($_POST['imgid'])||empty($_POST['code_value']))
                        {
                            $result_code=1011;
                            $result_content='必要参数为空';
                            $result['array']['verifycode']=array(
                                'title'=>"失败",
                                'content'=>$result_content,
                                'code'=>$result_code,
                                'variable'=>""
                            );
                            $result['exit']=1;
                        }
                        else
                        {
                            //check签名
                            $server_variable=array(
                                'from'=>$_GET['from'],
                                'app_id'=>$_POST['app_id'],
                                'nonce'=>$_POST['nonce'],
                                'time'=>$_POST['time'],
                                'time_stamp'=>$_POST['time_stamp'],
                                'imgid'=>$_POST['imgid'],
                                "code_value"=>$_POST['code_value']
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
                                $result['array']['verifycode']=array(
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
                                    //check签名验证通过
                                    //取当前时间戳
                                    $server_time_stamp=time();

                                    //验证imgid是否已过期,未过期判断是否正确
                                    $table_name=$Database->getTablename('temporary_verifycode');
                                    $sql_statement=$Database->object->prepare("SELECT time_stamp,code_value FROM {$table_name} WHERE imgid=:imgid ORDER BY id DESC LIMIT 0,1");
                                    $sql_statement->bindParam(':imgid',$_POST['imgid']);
                                    $sql_statement->execute();
                                    $result_sql_temp=$sql_statement->fetch();
                                    if(isset($result_sql_temp['time_stamp'])&&$server_time_stamp<=$result_sql_temp['time_stamp']+3*60*60)
                                    {
                                        //销毁本次imgid值
                                        $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE imgid=:imgid");
                                        $sql_statement->bindParam(':imgid',$_POST['imgid']);
                                        $sql_statement->execute();
                                        if(!empty($result_sql_temp['code_value'])&&$result_sql_temp['code_value']===$_POST['code_value'])
                                        {
                                            $result_code=0;
                                            $result_content='成功';
                                            $result['array']['verifycode']=array(
                                                'title'=>"验证成功",
                                                'content'=>$result_content,
                                                'code'=>$result_code,
                                                'variable'=>""
                                            );
                                        }
                                        else
                                        {
                                            $result_code=1034;
                                            $result_content='验证码错误';
                                            $result['array']['verifycode']=array(
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
                                        $result_code=1033;
                                        $result_content='请求已过期';
                                        $result['array']['verifycode']=array(
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
                                    $result_code=1014;
                                    $result_content='非法请求';
                                    $result['array']['verifycode']=array(
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
                        $result['array']['verifycode']=array(
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
                    $result['array']['verifycode']=array(
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
                $result['array']['verifycode']=array(
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
    $result['array']['verifycode']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>''
    );
    $result['exit']=1;
}
?>