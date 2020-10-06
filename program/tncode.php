<?php

#program/tncode

//滑块验证码,当前处于公开状态

//使用其他输出,以便将图片正常输出
$result['mode']=3;
//导入Tncode类
include_class('Tncode');
//判断是否以开启session,未开启就开启
if(!isset($_SESSION))
{
    session_start();
}
$tn=new Tncode();

//补齐数据
if(!isset($_GET['mode']))
    $_GET['mode']="";

if($_GET['mode']==='make')
{
    $tn->make();
}
else if($_GET['mode']==='check')
{
    if($tn->check())
    {
        $_SESSION['tncode_check'] = 'ok';
        echo "ok";
    }
    else
    {
        $_SESSION['tncode_check'] = 'error';
        echo "error";
    }
}
else if($_GET['mode']==='from')
{
    if(!isset($_SESSION['tncode_check']))
        $_SESSION['tncode_check']='';
    if($_SESSION['tncode_check']==='ok')
    {
        echo '验证通过';
    }
    else
    {
        echo '验证失败';
    }
}
else
{
    //显示验证页面
    $template='template/tncode/slider.html';
    if(is_file($template))
    {
        $default_content=file_get_contents($template);
        $content_array=array(
            "\${public}"=>$main_config['html_config']['public'],
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
        $result['array']['tncode']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$template
        );
        $result['exit']=1;
    }
}

?>