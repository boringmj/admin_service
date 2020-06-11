<?php

#program/install

$paths=array(
    "data",
    "uploads"
);

//最新版本
$install_data_new=14;

foreach($paths as $path)
{
    if(!is_writable($path))
    {
        $result['html'].="{$path}目录或文件不可写<br>";
        $result['mode']=2;
        $result['exit']=1;
    }
}

$install_data_path='data/install.data.json';
if(!is_file($install_data_path)&&$result['exit']===0)
{
    //安装基础数据依赖(也是第一个数据版本)
    if(empty($_GET['mode'])||$_GET['mode']!=='y')
    {
        $result['html']="您还未完成安装本程序,点击<a href='/?mode=y'>安装数据信息</a>完成安装";
        $result['mode']=2;
        $result['exit']=1;
    }
    else
    {
        install_tables($Database,$install_data_path,$install_data_new,1);
        $result['html']="数据信息安装完成";
        $result['mode']=2;
        $result['exit']=1;
    }
}
else if($result['exit']===0)
{
    $install_data_json=file_get_contents($install_data_path);
    $install_data=json_decode($install_data_json);
    if($install_data->code<$install_data_new)
    {
        if(!empty($_GET['mode'])&&$_GET['mode']==='y')
        {
            install_tables($Database,$install_data_path,$install_data_new,0);
            $result['html']="数据信息安装完成";
            $result['mode']=2;
            $result['exit']=1;
        }
        else
        {
            $result['html']="本程序检测到更新,请自行备份原数据库后(数据丢失请自行负责),点击<a href='/?mode=y'>安装更新数据信息</a>完成更新安装";
            $result['mode']=2;
            $result['exit']=1;
        }
    }
}

function install_tables($Database,$install_data_path,$install_data_new,$install_data_yes)
{
    echo "重要数据或信息将会使用红色字体打印在日志中,请注意查看<br><br>";
    if(!$install_data_yes)
        $install_data_json=file_get_contents($install_data_path);
    else
        $install_data_json=json_encode(array("code"=>1));
    $install_data=json_decode($install_data_json);
    if($install_data_yes)
    {
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            app_id VARCHAR(32) NOT NULL,
            app_key VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(app_id,app_key) VALUES (:app_id,:app_key)");
        $app_id=getRandomstring(12).time();
        $app_key=getRandomstring(32);
        $sql_statement->bindParam(':app_id',$app_id);
        $sql_statement->bindParam(':app_key',$app_key);
        echo $sql_statement->execute()?"{$table_name}数据表初始数据已插入,<font color=red>App_id={$app_id},APP_key={$app_key}</font><br>":"";
        $table_name=$Database->getTablename('temporary_nonce');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nonce VARCHAR(10) NOT NULL,
            time_stamp INT(10) NOT NULL,
            app_id VARCHAR(32) NOT NULL,
            sgin VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('api_report_info_app');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            app_id VARCHAR(32) NOT NULL,
            time_stamp INT(10) NOT NULL,
            qq VARCHAR(32) NULL,
            wechat VARCHAR(32) NULL,
            uuid VARCHAR(32) NOT NULL,
            imei VARCHAR(32) NOT NULL,
            refrom VARCHAR(32) NOT NULL,
            reason VARCHAR(1024) NOT NULL,
            filepath VARCHAR(128) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            app_id VARCHAR(32) NOT NULL,
            time_stamp INT(10) NOT NULL,
            qq VARCHAR(32) NULL,
            wechat VARCHAR(32) NULL,
            email VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            nickname VARCHAR(32) NULL,
            user VARCHAR(32) NOT NULL,
            passwd VARCHAR(32) NULL,
            identification VARCHAR(32) NULL,
            integral VARCHAR(10) NULL,
            proving INT(1) NOT NULL
        ) AUTO_INCREMENT=1000");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('temporary_verifycode');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            code_value INT(3) NOT NULL,
            imgid VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('temporary_login_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            user VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            ukey VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    if(!$install_data_yes)
    {
        echo "更新安装<br>";
    }
    $install_data_code=4;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD api_login VARCHAR(1) DEFAULT 'Y',
            ADD api_report VARCHAR(1) DEFAULT 'Y',
            ADD api_register VARCHAR(1) DEFAULT 'Y',
            ADD api_userinfo VARCHAR(1) DEFAULT 'Y',
            ADD qq_bind VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('temporary_qq_bind_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            user VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            raid VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=5;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD api_signin VARCHAR(1) DEFAULT 'N',
            ADD verifycode VARCHAR(1) DEFAULT 'Y'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('temporary_signin_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            user VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            sign_time VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('message_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            user VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL,
            mgid VARCHAR(32) NOT NULL,
            frid VARCHAR(32) NOT NULL,
            msg_title VARCHAR(32) NOT NULL,
            msg_content VARCHAR(1000) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('api_report_info_app');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD restatus VARCHAR(1) DEFAULT 'U',
            ADD deal_uuid VARCHAR(32) DEFAULT NULL
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
    }
    $install_data_code=6;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD system_api VARCHAR(1) DEFAULT 'N',
            ADD application_api VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('tutorial');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            nickname VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL,
            ruid VARCHAR(32) NULL,
            tid VARCHAR(36) NOT NULL,
            like_number INT(8) NOT NULL,
            reward_number INT(8) NOT NULL,
            watch_number INT(8) NOT NULL,
            restatus VARCHAR(1) NOT NULL,
            title VARCHAR(32) NOT NULL,
            content VARCHAR(5120) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=7;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('up_file');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL,
            flid VARCHAR(36) NOT NULL,
            file_size INT(16) NOT NULL,
            file_path VARCHAR(128) NOT NULL,
            file_type VARCHAR(8) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('download_file');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            app_id VARCHAR(32) NOT NULL,
            flid VARCHAR(36) NOT NULL,
            file_path VARCHAR(128) NOT NULL,
            file_type VARCHAR(8) NOT NULL,
            download_certificate VARCHAR(36) NOT NULL,
            download_count INT(8) NOT NULL,
            download_total INT(8) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=8;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('tutorial');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD qq_push VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD qq_api VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
    }
    $install_data_code=9;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('message_user');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD read_msg VARCHAR(1) DEFAULT 'N',
            ADD annex_list VARCHAR(5120) DEFAULT ''
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('message_user');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
                DROP `user`
        ");
        echo $sql_statement->execute()?"{$table_name}数据表删除字段成功<br>":"";
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD api_retrieve VARCHAR(1) DEFAULT 'N',
            ADD  api_alter VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('temporary_retrieve_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            user VARCHAR(32) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            email VARCHAR(32) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=10;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('temporary_retrieve_user');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD reid VARCHAR(36) DEFAULT NULL
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
    }
    $install_data_code=11;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('admin_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            balance Float(10,2) NOT NULL,
            create_time VARCHAR(6) NOT NULL,
            create_count INT(10) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('api_user_info');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD admin_api VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('admin_application');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            time_stamp INT(10) NOT NULL,
            uuid VARCHAR(32) NOT NULL,
            user_count INT(10) NOT NULL,
            user_max INT(10) NOT NULL,
            views_count INT(10) NOT NULL,
            mail_count INT(10) NOT NULL,
            mail_max INT(10) NOT NULL,
            file_count INT(10) NOT NULL,
            file_max INT(10) NOT NULL,
            expired_time_stamp INT(10) NOT NULL,
            apid VARCHAR(36) NULL,
            ap_title VARCHAR(32) NULL,
            ap_content VARCHAR(1024) NULL,
            application_name VARCHAR(16) NOT NULL,
            application_note VARCHAR(64) NOT NULL,
            api_id VARCHAR(32) NOT NULL,
            api_key VARCHAR(36) NOT NULL,
            app_id VARCHAR(32) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=12;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('admin_application');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD api_states VARCHAR(1) DEFAULT 'Y'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
    }
    $install_data_code=13;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('admin_application');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD ap_user_register_states VARCHAR(1) DEFAULT 'Y',
            ADD ap_user_max INT(2) DEFAULT 18,
            ADD ap_user_min INT(2) DEFAULT 6,
            ADD ap_sign_states VARCHAR(1) DEFAULT 'N',
            ADD ap_email_states VARCHAR(1) DEFAULT 'N',
            ADD ap_email_verification_states VARCHAR(1) DEFAULT 'N'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('admin_api_user');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            api_id VARCHAR(32) NOT NULL,
            time_stamp INT(10) NOT NULL,
            email VARCHAR(32) NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            nickname VARCHAR(32) NULL,
            user VARCHAR(32) NOT NULL,
            passwd VARCHAR(32) NULL,
            ugroup VARCHAR(32) DEFAULT 0,
            integral VARCHAR(10) DEFAULT 0,
            proving INT(1) NOT NULL,
            vip INT(1) DEFAULT 3,
            ukey VARCHAR(32) NULL
        ) AUTO_INCREMENT=1000");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }
    $install_data_code=14;
    if($install_data->code<$install_data_code)
    {
        $table_name=$Database->getTablename('admin_application');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD ap_user_login_states VARCHAR(1) DEFAULT 'Y'
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('admin_api_temporary_login');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            api_id VARCHAR(32) NOT NULL,
            time_stamp INT(10) NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            ukey VARCHAR(32) NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
        $table_name=$Database->getTablename('admin_api_user');
        $sql_statement=$Database->object->prepare("ALTER TABLE {$table_name} 
            ADD head_portrait VARCHAR(36) NULL
        ");
        echo $sql_statement->execute()?"{$table_name}数据表新增字段成功<br>":"";
        $table_name=$Database->getTablename('admin_api_upload_file');
        $sql_statement=$Database->object->prepare("CREATE TABLE {$table_name}(
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            api_id VARCHAR(32) NOT NULL,
            time_stamp INT(10) NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            flid VARCHAR(36) NOT NULL,
            file_path VARCHAR(32) NOT NULL,
            file_type VARCHAR(8) NOT NULL,
            file_size INT(16) NOT NULL
        )");
        echo $sql_statement->execute()?"{$table_name}数据表创建成功<br>":"";
    }

    $file=fopen($install_data_path,"w+");
    $install_data=json_encode(array('code'=>$install_data_new));
    fwrite($file,$install_data);
    fclose($file);
}
?>