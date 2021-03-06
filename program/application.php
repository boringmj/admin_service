<?php

#program/application

//补全预处理参数
if(empty($_POST['app_id']))
    $_POST['app_id']="";
if(getPermission($_POST['app_id'],'application_api')==='Y')
{
    if(!empty($_GET['mode'])&&!preg_match('/.*\..*/',$_GET['mode']))
    {
        $path='./program/application/'.$_GET['mode'].'.php';
        if(is_file($path))
        {
            //规定输出方式为加密形式
            $result['mode']=4;

            //处理加密的数据
            $app_key=getAppkey($_POST['app_id']);
            if(empty($app_key))
            {
                //匹配不到就返回正常的json
                $result['mode']=1;
                $result_code=1013;
                $result_content='非法请求';
                $result['array']['application']=array(
                    'title'=>"失败",
                    'content'=>$result_content,
                    'code'=>$result_code,
                    'variable'=>""
                );
                $result['exit']=1;
            }
            else
            {
                //将秘钥设置为app_key
                $result['key']=$app_key;
                if(!empty($_POST['s']))
                {
                    //尝试解密
                    $temp_data=$Encryption->decodeIUM($_POST['s'],$result['key']);
                    //销毁传入的s参数
                    unset($_POST['s']);
                    //将上传的数据分割为数组
                    $temp_data=explode('&',$temp_data);
                    foreach( $temp_data as $temp_data_value)
                    {
                        //截取出提交数据的
                        if(strpos($temp_data_value,'='))
                        {
                            $name=substr($temp_data_value,0,strpos($temp_data_value,'='));
                            $value=substr($temp_data_value,strpos($temp_data_value,'=')+1);
                            $_POST[$name]=$value;
                        }
                        else
                        {
                            $_POST[$temp_data_value]="";
                        }
                    }
                }
            }
            //没有致命错误导致退出就继续执行
            if(!$result['exit'])
                include_program('application/'.$_GET['mode']);
        }
        else
        {
            $result_code=1005;
            $result_content='mode类不在指定范围内';
            $result['array'][]=array(
                'title'=>"失败",
                'content'=>$result_content,
                'code'=>$result_code,
                'variable'=>$_GET['mode']
            );
            $result['exit']=1;
        }
    }
    else
    {
        $result_code=1006;
        $result_content='指定api接口参数不合法';
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>''
        );
        $result['exit']=1;
    }
}
else
{
    $result_code=10000;
    $result_content='无权调用接口';
    $result['array']['application']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>''
    );
    $result['exit']=1;
}
?>