<?php

//引入Admin类文件
include_class("Admin");
//实例化Admin类
$Admin=new Admin();

//设置必要参数
$Admin->app_id=$_SESSION['app_id'];
$Admin->database_object=$Database;
$Admin->uuid=$_SESSION['uuid'];

$content_array=array(
    "\${money}"=>$Admin->getBalance(),
    "\${integral}"=>$User->user_info['integral'],
    "\${application_count}"=>0,
    "\${all_user_number}"=>0,
    "\${php_sapi_name}"=>php_sapi_name(),
    "\${PHP_VERSION}"=>PHP_VERSION,
    "\${organization_name}"=>$main_config['organization_config']['name'],
    "\${organization_development}"=>$main_config['organization_config']['development']
);
foreach($content_array as $key=>$value)
{
    $default_content=str_replace($key,$value,$default_content);
}

?>