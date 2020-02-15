<?php

#program/tutorial

//自定义输出模式
$result['mode']=3;

if(empty($_POST['tid'])&&empty($_GET['tid']))
{
    //参数不全
    header('HTTP/1.1 404 NOT FOUND');
}
else
{
    //补全参数
    if(empty($_POST['tid']))
    {
        $_POST['tid']=$_GET['tid'];
    }
    //取当前时间戳
    $server_time_stamp=time();
    $table_name=$Database->getTablename('tutorial');
    //查询已经通过审核的教程
    $sql_statement=$Database->object->prepare("SELECT id,restatus,nickname,uuid,tid,like_number,reward_number,watch_number,time_stamp,content,title FROM {$table_name} WHERE tid=:tid AND restatus='Y' ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':tid',$_POST['tid']);
    $sql_statement->execute();
    $result_sql_temp=$sql_statement->fetch();
    if(isset($result_sql_temp['time_stamp']))
    {
        //记录一次查询次数,本处不判断成功与否
        settype($result_sql_temp['watch_number'],"int");
        settype($result_sql_temp['reward_number'],"int");
        settype($result_sql_temp['like_number'],"int");
        $watch_number=$result_sql_temp['watch_number']+1;
        $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET watch_number=:watch_number WHERE tid=:tid");
        $sql_statement->bindParam(':watch_number',$watch_number);
        $sql_statement->bindParam(':tid',$_POST['tid']);
        $sql_statement->execute();
        $template_path="template/tutorial/tutorial_show.html";
        if(is_file($template_path))
        {
            $return_content=file_get_contents($template_path);
            //绑定模板变量
            $content_array=array(
                "\${title}"=>$Safety->xss($result_sql_temp['title']),
                "\${content}"=>$Safety->xss($result_sql_temp['content']),
                "\${time_stamp}"=>$result_sql_temp['time_stamp'],
                "\${watch_number}"=>$watch_number,
                "\${reward_number}"=>$result_sql_temp['reward_number'],
                "\${like_number}"=>$result_sql_temp['like_number'],
                "\${uuid}"=>$result_sql_temp['uuid'],
                "\${nickname}"=>$Safety->xss($result_sql_temp['nickname']),
                "\${tid}"=>$result_sql_temp['tid'],
                "\${restatus}"=>$result_sql_temp['restatus'],
                "\${organization}"=>$main_config['organization_config']['name'],
                "\n"=>"<br>"
            );
            foreach($content_array as $key=>$value)
            {
                $return_content=str_replace($key,$value,$return_content);
            }
            echo $return_content;
        }
        else
        {
            //模板丢失
            header('HTTP/1.1 404 NOT FOUND');
        }
    }
    else
    {
        //没有找到该教程
        header('HTTP/1.1 404 NOT FOUND');
    }
}
?>