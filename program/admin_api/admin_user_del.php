<?php

#program/admin_api/admin_user_del

//做一些基础准备,不得不承认效率被降低了
include_class("Adminapi");
$Adminapi=new Adminapi();
$Adminapi->database_object=$Database;
$Adminapi->admin_config=$main_config['admin_config'];
//检查接口是否处于正常可用状态
if($Adminapi->checkApi($_POST['api_id']))
{
    //检验基础参数是否已经传入
    if(empty($_POST['user']))
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
    //为了安全着想这里会强制签名
    $Adminapi->api_info['ap_sign_states']='Y';
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
                        'user'=>$_POST['user']
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
        $sql_statement=$Database->object->prepare("SELECT user,uuid FROM {$table_name} WHERE user=:user ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':user',$_POST['user']);
        $sql_statement->execute();
        $result_sql=$sql_statement->fetch();
        if(isset($result_sql['user'])&&$result_sql['user']===$_POST['user'])
        {
            //用户存在,那么直接执行删除操作
            $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE api_id=:api_id AND uuid=:uuid");
            $sql_statement->bindParam(':api_id',$_POST['api_id']);
            $sql_statement->bindParam(':uuid',$result_sql['uuid']);
            if($sql_statement->execute())
            {
                $result_code=0;
                $result_content='用户删除成功';
                $result['array']['admin_api']=array(
                    'title'=>"成功",
                    'content'=>$result_content,
                    'code'=>$result_code,
                    'variable'=>array(
                        'user'=>$result_sql['user'],
                        'uuid'=>$result_sql['uuid']
                    )
                );
                $result['exit']=1;
            }
            else
            {
                $result_code=99997;
                $result_content='系统异常';
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
            $result_code=99993;
            $result_content='无效的用户操作';
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
    $result['array']['admin_api']=$Adminapi->error_info['checkApi'];
    $result['exit']=1;
}

?>