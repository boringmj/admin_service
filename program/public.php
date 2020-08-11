<?php

#program/public

if(isset($_GET['mode']))
{
    //设置不必须参数
    if(empty($_GET['type']))
    $_GET['type']='text/html';

    $template=$_GET['mode'];
    $template=$main_config['html_config']['public'].'/'.$template;
    if(!preg_match('/(.*\.\..*)/',$template)&&is_file($template))
    {
        header("Content-type: {$_GET['type']}");
        $default_content=file_get_contents($template);
        $content_array=array(
            "\${public}"=>$main_config['html_config']['public'],
            "\${name}"=>$main_config['organization_config']['name'],
            "\${development}"=>$main_config['organization_config']['development'],
            "\${value}"=>isset($_GET['value'])?$Safety->value_url($_GET['value']):'',
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
        $result['array']['public']=array(
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
    $result_code=1011;
    $result_content='必要参数为空';
    $result['array']['public']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>""
    );
    $result['exit']=1;
}

?>