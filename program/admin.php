<?php

#program/admin

//引入User类文件
include_class("User");
//实例化User类
$User=new User();
//同步参数
$User->setStartSalt($main_config['user_info']['start_salt']);
$User->setEndSalt($main_config['user_info']['end_salt']);
//开启session
session_start();

//补全不必要参数
if(!isset($_GET['mode']))
    $_GET['mode']='index';
if(!isset($_GET['type']))
    $_GET['type']='text/html';
if(!isset($_COOKIE['uuid']))
    $_COOKIE['uuid']='';

//接收get方式传递的值
if(!empty($_GET['app_id']))
    $_SESSION['app_id']=$_GET['app_id'];
if(!empty($_GET['uuid']))
{
    $_SESSION['uuid']=$_GET['uuid'];
    $_COOKIE['uuid']=$_GET['uuid'];
    if(isset($_GET['setcookie'])&&$_GET['setcookie']==='Y')
        setcookie('uuid',$_GET['uuid']);
}
if(!empty($_GET['ukey']))
    $_SESSION['ukey']=$_GET['ukey'];

//设置必要参数
$User->app_id=$_SESSION['app_id'];
$User->database_object=$Database;

//设置uuid和ukey
$User->setUuid($_SESSION['uuid']);
$User->setUkey($_SESSION['ukey']);

//验证用户是否登录成功
if($User->getUserInfo()&&$_SESSION['uuid']===$_COOKIE['uuid'])
{
    //登录成功显示请求的资源
    $template=$_GET['mode'];
    $template_path="template/admin/html/".$template.".html";
    if(!preg_match('/(.*\..*)/',$template)&&is_file($template_path))
    {
        header("Content-type: {$_GET['type']}");
        $default_content=file_get_contents($template_path);
        $php_path="template/admin/php/".$template.".php";
        if(is_file($php_path))
            include_once $php_path;
        $content_array=array(
            "\${public}"=>$main_config['html_config']['public'],
            "\${name}"=>$main_config['organization_config']['name'],
            "\${development}"=>$main_config['organization_config']['development'],
            "\${value}"=>isset($_GET['value'])?$_GET['value']:'',
            "{\$}"=>'$'
        );
        foreach($content_array as $key=>$value)
        {
            $default_content=str_replace($key,$value,$default_content);
        }
        $result['html']=$default_content;
        $result['mode']=2;
    }
    else
    {
        $result_code=1023;
        $result_content='缺失公共文件';
        $result['array']['admin']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$template
        );
        $result['exit']=1;
    }
}
else
{
    header("location:/?class=public&mode=login.html&type=text/html&value=".urlencode("class=admin&mode={$_GET['mode']}&type={$_GET['type']}"));
}
?>