<h1>这里模拟黑客(恶意用户)拦截到的即将提交到服务器的数据</h1>
<form name="input" action="/?class=application&mode=admin_application_user_ibrary&from=list" method="POST" enctype="multipart/form-data">
<?php
$Encryption=new Encryption();
$server_variable=array(
    'from'=>"list",
    'app_id'=>"vxRLjxyaGTNa1573840643",
    'nonce'=>"1008611",
    'time'=>"",
    'time_stamp'=>time(),
    'uuid'=>isset($_GET['uuid'])?$_GET['uuid']:"",
    'ukey'=>isset($_GET['ukey'])?$_GET['ukey']:"",
    'page'=>isset($_GET['page'])?$_GET['page']:"1"
);
$server_sign='';
foreach($server_variable as $key=>$value)
{
    $server_sign.=$server_sign?"&{$key}={$value}":"{$key}={$value}";
    if($key==="from")
    echo "[GET]";
    else
    echo "[POST]";
    echo "{$key}=>{$value}<br>";
    //echo "{$key}<input type=\"text\" name=\"{$key}\" value=\"$value\"><br>";
}
$server_sign_temp=$server_sign.'&app_key=3t8afM2j4HEWiOXjqiqr96XikOshI6P6';
$server_sign_temp=md5($server_sign_temp);
$server_sign.="&sign={$server_sign_temp}";
$s=$Encryption->encodeIum($server_sign,"3t8afM2j4HEWiOXjqiqr96XikOshI6P6");
echo "sign=>{$server_sign_temp}<br>";
echo "s原值=>{$server_sign}<br>";
echo "s<input type=\"text\" name=\"s\" value=\"{$s}\"><br>";
echo "app_id<input type=\"text\" name=\"app_id\" value=\"{$server_variable['app_id']}\"><br>";

class Encryption
{
    /*
    *简单的异或加密解密,秘钥长度不够会被循环使用
    */
    public function xorEnc($str,$key)
    {
        //预定义结果
        $ret='';
        //取秘钥的md5值
        $key=md5($key);
        $keylen=strlen($key);
        for($i=0;$i<strlen($str);$i++)
        {
            $k=$i%$keylen;
            $ret.=$str[$i]^$key[$k];
        }
        return $ret;
    }

    /*
    *在异或的基础上加强加密强度,暂时取名叫IUM加密
    */
    public function encodeIum($str,$key)
    {
        return base64_encode($this->xorEnc(base64_encode($str),base64_encode(md5($key))));
    }

    /*
    *解密加密的数据
    */
    public function decodeIum($str,$key)
    {
        return base64_decode($this->xorEnc(base64_decode($str),base64_encode(md5($key))));
    }
}
?>
<br>
<input type="submit" value="Submit">
</form>