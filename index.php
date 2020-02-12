<?php

#index

//屏蔽错误信息
error_reporting(0);

//最终返回,取决于最终如何返,返回形式,1为json形式(array),2为html形式(html),其他为主页无输出(可在程序自行输出)
$result=array(
    'mode'=>1,
    'array'=>array(),
    'html'=>"",
    'key'=>"",
    'exit'=>0
);

//导入基础文件
include_once 'main.php';

if(!$result['exit'])
    include_program('main');

//输出结果,如果返回形式不正确将不能输出
if($result['mode']===1)
{
    //json形式输出
    header('Content-Type:application/json');
    echo json_encode($result['array']);
}
else if($result['mode']===2)
{
    //正常网页形式输出
    echo $result['html'];
}
else if($result['mode']===3)
{
    //不做任何输出,一般为图片输出
}
else if($result['mode']===4)
{
    //json加密输出
    echo $Encryption->encodeIUM(json_encode($result['array']),$result['key']);
}

?>