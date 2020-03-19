<?php

#index

/*
* 请自行阅读README.md
* 本项目github地址,也是唯一官方发布,更新地址:https://github.com/boringmj/admin_service/
* 请遵守项目声明(https://github.com/boringmj/admin_service/blob/master/README.md#%E7%89%B9%E5%88%AB%E5%A3%B0%E6%98%8E)
* 开发者:无聊的莫稽(wuliaodemoji@wuliaomj.com)
* 联系方式(QQ):3239957605
* 以上信息请勿修改,修改即视为侵权,项目作者有权依法追究责任
*/

//屏蔽错误信息
error_reporting(0);

//最终返回,取决于最终如何返,返回形式,在下面自行查看和定义输出形式
$result=array(
    'mode'=>1,
    'array'=>array(),
    'html'=>"",
    'key'=>"",
    'nonce'=>"",
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