<?php

#program/admin_api/user_add

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
        echo "当前已注册用户:{$Adminapi->api_info['user_count']},最多允许注册用户:{$Adminapi->api_info['user_max']}";
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