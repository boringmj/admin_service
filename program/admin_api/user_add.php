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
    
}
else
{
    $result['array']['admin_api']=$Adminapi->error_info['checkApi'];
    $result['exit']=1;
}

?>