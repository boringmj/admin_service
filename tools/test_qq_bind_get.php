﻿<h1>这里模拟黑客(恶意用户)拦截到的即将提交到服务器的数据</h1>
<form name="input" action="/?class=qq&mode=bind&from=get" method="POST" enctype="multipart/form-data">
<?php
$server_variable=array(
    'from'=>"get",
    'app_id'=>"vxRLjxyaGTNa1573840643",
    'nonce'=>"1008611",
    'time'=>"",
    'time_stamp'=>time(),
    'imei'=>1008611,
    'uuid'=>isset($_GET['uuid'])?$_GET['uuid']:"",
    'ukey'=>isset($_GET['ukey'])?$_GET['ukey']:""
);
$server_sign='';
foreach($server_variable as $key=>$value)
{
    $server_sign.=$server_sign?"&{$key}={$value}":"{$key}={$value}";
    if($key==="from")
    echo "[GET]";
    else
    echo "[POST]";
    echo "{$key}<input type=\"text\" name=\"{$key}\" value=\"$value\"><br>";
}
$server_sign.='&app_key=3t8afM2j4HEWiOXjqiqr96XikOshI6P6';
$server_sign=md5($server_sign);
echo "sign<input type=\"text\" name=\"sign\" value=\"$server_sign\"><br>";

?>
<br>
<input type="submit" value="Submit">
</form>