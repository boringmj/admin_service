<?php

#program/monitor

/**
 * 监控说明
 *
 * 监控需要运营商通过任意方式任意频率访问,这里推荐每3小时访问一次
 * 监控涉及异步更新和数据处理,这可能会加大服务器的负担 
 * 短时间内我不会更新监控的访问权限,所以需要用户自行鉴权
 * 这里会占用掉大部分服务器资源,而且单线程删除效率很低
*/

//引入邮件配置
include_once 'config/mail.php';

//定义一些常用的公共变量
$server_time_stamp=time();

//这里做一个简单的鉴权处理
if(!(isset($_GET["key"])&&md5($_GET['key'])==md5($main_config['system_config']['monitor_key'])))
{
    //因为懒,所以就这样
    exit();
}

// @ 应用过期处理
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
        "\${organization}"=>"应用后台服务",
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
       //邮件发送成功就变更为过期状态,发送失败就等着下次发送吧(这里无需担心状态变更不及时导致的超时使用问题,每个应用都会通过时间戳进行验证过期,这个状态只是方便的)
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
        //删除成功就删除对应的各类资源(按理来说用户是不属于某个应用的,所以我再三考虑觉得还是不删除用户库的用户)
        $uuid=$temp_data['uuid'];
        //先获取用户的信息
        $table_name=$Database->getTablename('user');
        $sql_statement=$Database->object->prepare("SELECT * FROM {$table_name} WHERE uuid<=:uuid ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':uuid',$uuid);
        $sql_statement->execute();
        $result_sql_temp_user_info=$sql_statement->fetch(PDO::FETCH_ASSOC);
        $content_array=array(
            "\${title}"=>"过期删除提醒",
            "\${organization}"=>"用户库",
            "\${date}"=>$ban_date=date("Y-m-d H:i:s",$server_time_stamp),
            "\${user_name}"=>$result_sql_temp_user_info['user'],
            "\${expired_time}"=>date("Y-m-d",$temp_data['expired_time_stamp']),
            "\${product_name}"=>"admin_service",
            "\${product_id}"=>$temp_data['id'],
            "\${delete_time}"=>date("Y-m-d",$temp_data['expired_time_stamp']+7*24*60*60)
        );
        $ret_mail=$Sendmail->send("过期删除提醒",$result_sql_temp_user_info['email'],"default/delete",$content_array);
    }
}

// @ 用户库过期处理
/*
//和上面一样,自行在效率和功能上做抉择
$table_name=$Database->getTablename('admin_api_user_library');
$sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE expired_time_stamp<=".(time()-7*24*60*60));
*/
$table_name=$Database->getTablename('admin_api_user_library');
$sql_statement=$Database->object->prepare("SELECT expired_time_stamp,uuid,id,span_id FROM {$table_name} WHERE expired_time_stamp<=:time_stamp ORDER BY id DESC");
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
        "\${title}"=>"过期删除提醒",
        "\${organization}"=>$main_config['organization_config']['name'],
        "\${date}"=>$ban_date=date("Y-m-d H:i:s",$server_time_stamp),
        "\${user_name}"=>$result_sql_temp_user_info['user'],
        "\${expired_time}"=>date("Y-m-d",$temp_data['expired_time_stamp']),
        "\${product_name}"=>"admin_service",
        "\${product_id}"=>$temp_data['id'],
        "\${delete_time}"=>date("Y-m-d",$temp_data['expired_time_stamp'])
    );
    $ret_mail=$Sendmail->send("过期删除提醒",$result_sql_temp_user_info['email'],"default/delete",$content_array);
    //因为特殊需要,无论邮件是否发送成功都会删除(不验证是否成功)
    $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE span_id=:span_id");
    $sql_statement->bindParam(':span_id',$temp_data['span_id']);
    $sql_statement->execute();
}

?>