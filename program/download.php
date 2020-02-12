<?php

#program/download

//自定义输出模式
$result['mode']=3;

if(empty($_POST['n'])&&empty($_GET['n']))
{
    //参数不全
    header('HTTP/1.1 404 NOT FOUND');
    $result_code=1011;
    $result_content='必要参数为空';
    $result['array']['download']=array(
        'title'=>"失败",
        'content'=>$result_content,
        'code'=>$result_code,
        'variable'=>""
    );
    $result['exit']=1;
}
else
{
    //补全参数
    if(empty($_POST['n']))
    {
        $_POST['n']=$_GET['n'];
    }

    //获取当前时间戳
    $server_time_stamp=time();
    //设置数据表
    $table_name=$Database->getTablename('download_file');
    //销毁过期凭证
    $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".($server_time_stamp-3*60*60));
    $sql_statement->execute();
    //查询凭证
    $sql_statement=$Database->object->prepare("SELECT time_stamp,file_path,file_type,download_count,download_total FROM {$table_name} WHERE download_certificate=:download_certificate  ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':download_certificate',$_POST['n']);
    $sql_statement->execute();
    $result_sql_temp=$sql_statement->fetch();
    if(isset($result_sql_temp['time_stamp'])&&$server_time_stamp-$result_sql_temp['time_stamp']<=3*60*60)
    {
        settype($result_sql_temp['download_count'],"int");
        settype($result_sql_temp['download_total'],"int");
        if(!file_exists($result_sql_temp['file_path'])||$result_sql_temp['download_count']>=$result_sql_temp['download_total'])
        {
            //没有找到文件
            header('HTTP/1.1 404 NOT FOUND');
            $result_code=1051;
            $result_content='文件已失效';
            $result['array']['download']=array(
                'title'=>"失败",
                'content'=>$result_content,
                'code'=>$result_code,
                'variable'=>""
            );
            $result['exit']=1;
        }
        else
        {
            //存储下载次数,不做成功判断
            $download_count=$result_sql_temp['download_count']+1;
            $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET download_count=:download_count WHERE download_certificate=:download_certificate");
            $sql_statement->bindParam(':download_count',$download_count);
            $sql_statement->bindParam(':download_certificate',$_POST['n']);
            $sql_statement->execute();
            //以只读和二进制模式打开文件
            $file=fopen($result_sql_temp['file_path'],"rb" );
            //告诉浏览器这是一个文件流格式的文件
            header("Content-type: application/octet-stream");
            //请求范围的度量单
            header("Accept-Ranges: bytes");
            //Content-Length是指定包含于请求或响应中数据的字节长度
            header("Accept-Length: ".filesize($result_sql_temp['file_path']));
            header("Content-Disposition: attachment; filename=".(getRandomstring(32).".".$result_sql_temp['file_type']));
            //读取文件内容并直接输出到浏览器
            echo fread($file,filesize($result_sql_temp['file_path']));
            fclose($file);
        }
    }
    else
    {
        //凭证已过期或找不到凭证
        header('HTTP/1.1 404 NOT FOUND');
        $result_code=1050;
        $result_content='凭证无效';
        $result['array']['download']=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>""
        );
        $result['exit']=1;
    }
}
?>