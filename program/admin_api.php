<?php

#program/admin_api

if(empty($_POST['api_id']))
    $_POST['api_id']="";
if(!empty($_GET['mode'])&&!preg_match('/.*\..*/',$_GET['mode']))
{
    //尝试获取api_id对应的api_key
    $api_key=getApikey($_POST['api_id']);
    if(empty($api_key))
    {
        //匹配不到就直接返回
        $result_code=1064;
        $result_content='非法请求';
        $result['array']['admin_api']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>""
        );
        $result['exit']=1;
    }
    else
    {
        //验证mode是否存在
        $path='./program/admin_api/'.$_GET['mode'].'.php';
        if(is_file($path))
        {
            if(!$result['exit'])
            {
                //处理返回方式和接收方式
                if(empty($_GET['encode_type']))
                    $_GET['encode_type']='';
                if(empty($_GET['return']))
                    $_GET['return']='json';

                //处理传入的数据,如果有要求加密就尝试解密,如果没有加密就不处理
                if($_GET['encode_type']==="ium")
                {
                    //处理加密的数据
                    $result['key']=$api_key;
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
                    include_program('admin_api/'.$_GET['mode']);
                
                //处理传出数据
                if($_GET['return']==='ium')
                {
                    //IUM加密形式
                    $result['mode']=4;
                    //将秘钥设置为api_key
                    $result['key']=$api_key;
                }
                else
                {
                    //默认为json的形式
                    $result['mode']=1;
                }
            }
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

function getApikey($api_id)
{
    global $Database;
    $table_name=$Database->getTablename('admin_application');
    $sql_statement=$Database->object->prepare("SELECT api_key FROM {$table_name} WHERE api_id=:api_id ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':api_id',$api_id);
    $sql_statement->execute();
    $result_sql=$sql_statement->fetch();
    return isset($result_sql['api_key'])?$result_sql['api_key']:'';
}

?>