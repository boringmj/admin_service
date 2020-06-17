<?php

#program/monitor

/**
 * 监控说明
 *
 * 监控需要运营商通过任意方式任意频率访问,这里推荐每3分钟访问一次
 * 监控涉及异步更新和数据处理,这可能会加大服务器的负担 
 * 短时间内我不会更新监控的访问权限,所以需要用户自行鉴权
*/

//引入邮件配置
include_once 'config/mail.php';

//定义一些常用的公共变量
$server_time_stamp=time();

//api_id过期处理
$table_name=$Database->getTablename('admin_application');
$sql_statement=$Database->object->prepare("SELECT expired_time_stamp,uuid,id,api_id FROM {$table_name} WHERE expired_time_stamp<=:time_stamp AND (api_states='Y' OR api_states='N') ORDER BY id DESC");
$sql_statement->bindParam(':time_stamp',$server_time_stamp);
$sql_statement->execute();
$result_sql_temp=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
foreach($result_sql_temp as $temp_data)
{
    $uuid=$temp_data['uuid'];
    //先获取用户的信息
    $table_name=$Database->getTablename('user');
    $sql_statement=$Database->object->prepare("SELECT * FROM {$table_name} WHERE uuid<=:uuid ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':uuid',$uuid);
    $sql_statement->execute();
    $result_sql_temp_user_info=$sql_statement->fetch(PDO::FETCH_ASSOC);
    $content_array=array(
        "\${title}"=>"过期提醒",
        "\${organization}"=>$main_config['organization_config']['name'],
        "\${date}"=>$ban_date=date("Y-m-d H:i:s",$server_time_stamp),
        "\${user_name}"=>$result_sql_temp_user_info['user'],
        "\${expired_time}"=>date("Y-m-d",$temp_data['expired_time_stamp']),
        "\${product_name}"=>"admin_service",
        "\${product_id}"=>$temp_data['id'],
        "\${delete_time}"=>date("Y-m-d",$temp_data['expired_time_stamp']+7*24*60*60)
    );
    $ret_mail=$Sendmail->send("过期提醒",$result_sql_temp_user_info['email'],"default/expired",$content_array);
    if($ret_mail===1)
    {
       //邮件发送成功就变更为过期状态,发送失败就等着下次发送吧
        $table_name=$Database->getTablename('admin_application');
        $sql_statement=$Database->object->prepare("UPDATE {$table_name} SET api_states='E' WHERE uuid=:uuid AND api_id=:api_id");
        $sql_statement->bindParam(':api_id',$temp_data['api_id']);
        $sql_statement->bindParam(':uuid',$uuid);
        $sql_statement->execute();
    }
}

//api_id过期不续费删除处理(因为需求,需要先获取用户信息,然后依次删除,这会影响性能)
/*
//直接删除过期api_id,这样写有一个缺点,就是没办法清理多余的信息和发送删除的消息,优点是效率高速度快代码少
$table_name=$Database->getTablename('admin_application');
$sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE expired_time_stamp<=".(time()-7*24*60*60));
*/
$table_name=$Database->getTablename('admin_application');
$sql_statement=$Database->object->prepare("SELECT expired_time_stamp,uuid,id,api_id FROM {$table_name} WHERE expired_time_stamp<=:time_stamp AND api_states='E' ORDER BY id DESC");
$expired_time_stamp=time()-7*24*60*60;
$sql_statement->bindParam(':time_stamp',$expired_time_stamp);
$sql_statement->execute();
$result_sql_temp=$sql_statement->fetchAll(PDO::FETCH_ASSOC);
//删除已经超过删除日的所有资源
foreach($result_sql_temp as $temp_data)
{
    $table_name=$Database->getTablename('admin_application');
    $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE api_id=:api_id");
    $sql_statement->bindParam(':api_id',$temp_data['api_id']);
    if($sql_statement->execute())
    {
        //删除成功就删除对应的各类资源(本来考虑的是发个邮件提醒一下,结果我不想邮箱模板就放弃了),因为涉及版本问题,这里不验证是否删除成功
        $table_name=$Database->getTablename('admin_api_user');
        $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE api_id=:api_id");
        $sql_statement->bindParam(':api_id',$temp_data['api_id']);
        $sql_statement->execute();
    }
}

//这里删除已经过期的用户库


?>