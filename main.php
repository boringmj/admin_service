<?php

#main

date_default_timezone_set('PRC');

include_once 'config/main.php';
include_class('Database');
include_class('Encryption');
include_class('Safety');

//预定义变量
$Database;
$main_config;
if(empty($_FILES))
    $_FILES;

//这里处理主配置文件的配置信息
$main_config['user_info']=$user_info;
unset($user_info);
$main_config['html_config']=$html_config;
unset($html_config);
$main_config['organization_config']=$organization_config;
unset($organization_config);
$main_config['system_config']=$system_config;
unset($system_config);
$main_config['admin_config']=$admin_config;
unset($admin_config);

//这里判断请求域名是否合法
if((isset($_SERVER['HTTP_HOST'])||isset($_SERVER['SERVER_ADDR']))&&$main_config['html_config']['open_request_domain'])
{
    $tmep_domain=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_ADDR'];
    $tmep_domain_array=$main_config['html_config']['request_domain'];
    $tmep_domain_array[]=$main_config['html_config']['domain'];
    if(!in_array($tmep_domain,$tmep_domain_array))
    {
        $result['array']['domain']=array(
            'title'=>"失败",
            'content'=>"当前域名被限制访问",
            'code'=>-1,
            'variable'=>$tmep_domain
        );
        $result['exit']=1;
    }
}

//这里判断各种扩展支持
if(!class_exists('PDO'))
{
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>"当前环境不支持PDO",
        'code'=>999,
        'variable'=>""
    );
    $result['exit']=1;
}
if(!class_exists('ZipArchive'))
{
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>"当前环境不支持ZipArchive",
        'code'=>998,
        'variable'=>""
    );
    $result['exit']=1;
}
if(!function_exists('imagecreate'))
{
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>"当前环境不支持GD",
        'code'=>997,
        'variable'=>""
    );
    $result['exit']=1;
}
if(!function_exists('curl_close'))
{
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>"当前环境不支持cUrl",
        'code'=>996,
        'variable'=>""
    );
    $result['exit']=1;
}
if(!function_exists('session_start'))
{
    $result['array'][]=array(
        'title'=>"失败",
        'content'=>"当前环境不支持SESSION",
        'code'=>995,
        'variable'=>""
    );
    $result['exit']=1;
}

//基础环境正常
if(!$result['exit'])
{
    //处理数据库配置
    $Database=new Database();
    $Database->setHost($database_info['host']);
    $Database->setUser($database_info['user']);
    $Database->setPasswd($database_info['passwd']);
    $Database->setDatabase($database_info['database']);
    $Database->prefix=$database_info['prefix'];
    unset($database_info);
    if(!$Database->link())
    {
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>"数据库连接失败",
            'code'=>1007,
            'variable'=>""
        );
        $result['exit']=1;
    }

    include_program('install');

    //实例化加密类
    $Encryption=new Encryption();
    //实例化安全类
    $Safety=new Safety();
}

function include_php($path)
{
    global $result;
    global $main_config;
    global $Database;
    global $Encryption;
    global $Safety;
    global $_FILES;
    $path.='.php';
    $result_code=1000;
    $result_content='php文件导入失败';
    if(is_file($path))
        include_once $path;
    else
    {
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$path
        );
        $result['exit']=1;
    }
}

function include_program($path)
{
    global $result;
    global $main_config;
    global $Database;
    global $Encryption;
    global $Safety;
    global $_FILES;
    $path='program/'.$path.'.php';
    $result_code=1003;
    $result_content='program程序文件导入失败';
    if(is_file($path))
        include_once $path;
    else
    {
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$path
        );
        $result['exit']=1;
    }
}

function include_class($path)
{
    global $result;
    $path='class/'.$path.'.class.php';
    $result_code=1004;
    $result_content='class类文件导入失败';
    if(is_file($path))
        include_once $path;
    else
    {
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$path
        );
        $result['exit']=1;
    }
}

function include_data($path)
{
    global $result;
    global $main_config;
    global $Database;
    global $Encryption;
    global $Safety;
    global $_FILES;
    $path='data/'.$path.'.php';
    $result_code=1008;
    $result_content='data数据文件导入失败';
    if(is_file($path))
        include_once $path;
    else
    {
        $result['array'][]=array(
            'title'=>"失败",
            'content'=>$result_content,
            'code'=>$result_code,
            'variable'=>$path
        );
        $result['exit']=1;
    }
}

function getRandomstring($len,$chars=null)
{
    if(is_null($chars))
    {
        $chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000*(double)microtime());
    for ($i=0,$str='',$lc=strlen($chars)-1;$i<$len;$i++)
    {
        $str.=$chars[mt_rand(0,$lc)];
    }
    return $str;
}

function getRandstringid($type=0)
{
    if(function_exists('uuid_create'))
    {
        return uuid_create($type);
    }
    else
    {
        $char="1234567890abcdef";
        $str=getRandomstring(8,$char)."-".getRandomstring(4,$char)."-".getRandomstring(4,$char)."-".getRandomstring(4,$char)."-".getRandomstring(12,$char);
        return strtolower($str);
    }
}

function isEmail($email)
{
    $chars="/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($email,'@')!==false&&strpos($email,'.')!==false)
    {
        if(preg_match($chars,$email)){
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

function getAppkey($app_id)
{
    global $Database;
    $table_name=$Database->getTablename('api_user_info');
    $sql_statement=$Database->object->prepare("SELECT app_key FROM {$table_name} WHERE app_id=:app_id ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':app_id',$app_id);
    $sql_statement->execute();
    $result_sql=$sql_statement->fetch();
    return isset($result_sql['app_key'])?$result_sql['app_key']:'';
}

function getPermission($app_id,$name)
{
    global $Database;
    $table_name=$Database->getTablename('api_user_info');
    $sql_statement=$Database->object->prepare("SELECT * FROM {$table_name} WHERE app_id=:app_id ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':app_id',$app_id);
    $sql_statement->execute();
    $result_sql=$sql_statement->fetch();
    return isset($result_sql[$name])?$result_sql[$name]:'';
}

function  getConce($nonce,$sgin,$app_id)
{
    global $Database;
    $time_stamp=time();
    $nonce=substr($nonce,0,12);
    $table_name=$Database->getTablename('temporary_nonce');
    $sql_statement=$Database->object->prepare("DELETE FROM {$table_name} WHERE time_stamp<".(time()-10*60));
    $sql_statement->execute();
    $sql_statement=$Database->object->prepare("SELECT time_stamp FROM {$table_name} WHERE app_id=:app_id AND nonce=:nonce AND sgin=:sgin ORDER BY id DESC LIMIT 0,1");
    $sql_statement->bindParam(':app_id',$app_id);
    $sql_statement->bindParam(':nonce',$nonce);
    $sql_statement->bindParam(':sgin',$sgin);
    $sql_statement->execute();
    $time_stamp_server=$sql_statement->fetch()['time_stamp'];
    if(is_null($time_stamp_server))
    {
        $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(app_id,nonce,sgin,time_stamp) VALUES (:app_id,:nonce,:sgin,:time_stamp)");
        $sql_statement->bindParam(':app_id',$app_id);
        $sql_statement->bindParam(':nonce',$nonce);
        $sql_statement->bindParam(':sgin',$sgin);
        $sql_statement->bindParam(':time_stamp',$time_stamp);
        $sql_statement->execute();
        return 1;
    }
    else
    {
        if($time_stamp-$time_stamp_server>10*60)
        {
            $sql_statement=$Database->object->prepare("INSERT INTO {$table_name}(app_id,nonce,sgin,time_stamp) VALUES (:app_id,:nonce,:sgin,:time_stamp)");
            $sql_statement->bindParam(':app_id',$app_id);
            $sql_statement->bindParam(':nonce',$nonce);
            $sql_statement->bindParam(':sgin',$sgin);
            $sql_statement->bindParam(':time_stamp',$time_stamp);
            $sql_statement->execute();
            return 1;
        }
        else
            return 0;
    }
}

function getSignString($str)
{
    global $main_config;
    if($main_config['system_config']['sign_code']==='url')
        return urlencode($str);
    else if($main_config['system_config']['sign_code']==='base64')
        return base64_encode($str);
    else
        return $str;
}

?>