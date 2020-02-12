<?php

#program/main

if(empty($_GET['class']))
{
    $_GET['class']='index';
}

if(!preg_match('/(.*m.*a.*i.*n.*|.*\..*|.*\/.*)/',$_GET['class']))
{
    $path='./program/'.$_GET['class'].'.php';
    if(is_file($path))
    {
        if(!$result['exit'])
            include_program($_GET['class']);
    }
    else
    {
        $result_code=1002;
        $result_content='class类不在指定范围内';
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$_GET['class']
        );
        $result['exit']=1;
    }
}
else
{
    $result_code=1001;
    $result_content='class类不能为空';
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>''
    );
    $result['exit']=1;
}

?>