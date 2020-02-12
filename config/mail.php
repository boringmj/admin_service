<?php

#config/mail

//邮箱配置信息
$config =array(
    'smtp_port'=>465,                       //smtp端口(国内很多服务器商都屏蔽了25端口,所以推荐465端口发送)
    'smtp_host'=>'',                        //smtp服务器
    'from_email'=>'',                       //发送邮箱
    'from_name'=>'',                        //发件人姓名或组织名
    'smtp_user'=>'',                        //smtp用户
    'smtp_pass'=>'',                        //smtp密码
    'reply_email'=>'',                      //回复邮箱(一般为空)
    'reply_name'=>''                        //回复名称(一般为空)
);

//引入并实例化对象
include_class('Sendmail');
$Sendmail=new Sendmail();
$Sendmail->setConfig($config);
unset($config);

?>