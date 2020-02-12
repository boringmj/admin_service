<?php

#program/server_info

//定义输出形式为无任何输出
$result['mode']=3;

//PDO
if(class_exists('PDO'))
{
    echo "[<font color=green>支持</font>]PDO<br>";
}
else
{
    echo "[<font color=red>不支持</font>]PDO<br>";
}

//ZipArchive
if(class_exists('ZipArchive'))
{
    echo "[<font color=green>支持</font>]ZipArchive<br>";
}

//GD
if(function_exists('imagecreate'))
{
    echo "[<font color=green>支持</font>]GD<br>";
}
else
{
    echo "[<font color=red>不支持</font>]GD<br>";
}

//UUID
if(function_exists('uuid_create'))
{
    echo "[<font color=green>支持</font>]UUID<br>";
}
else
{
    echo "[<font color=red>不支持</font>]UUID,已自动启用备用方案<br>";
}

//SESSION
if(function_exists('session_start'))
{
    echo "[<font color=green>支持</font>]SESSION<br>";
}
else
{
    echo "[<font color=red>不支持</font>]SESSION,已自动启用备用方案<br>";
}

//cUrl
if(function_exists('curl_close'))
{
    echo "[<font color=green>支持</font>]cUrl<br>";
}
else
{
    echo "[<font color=red>不支持</font>]cUrl,已自动启用备用方案<br>";
}

?>