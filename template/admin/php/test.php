<?php

//引入Admin类文件
include_class("Admin");
//实例化Admin类
$Admin=new Admin();

//设置必要参数
$Admin->app_id=$_SESSION['app_id'];
$Admin->database_object=$Database;
$Admin->uuid=$_SESSION['uuid'];

$Admin->setBalance(1.12);
$Admin->setBalance(-1);

$content_array=array(
    "\${balance}"=>$Admin->getBalance(),
    "\${getCreateCount}"=>$Admin->getCreateCount(),
    "\${maxCreateCount}"=>$main_config['admin_config']['create_max'],
    "\${getCreateCountMonth}"=>$Admin->getCreateCountMonth(),
    "\${maxCreateCountMonth}"=>$main_config['admin_config']['create_month_max'],
    "\${user_name}"=>$Safety->xss($User->user_info['name'])
);
foreach($content_array as $key=>$value)
{
    $default_content=str_replace($key,$value,$default_content);
}


?>