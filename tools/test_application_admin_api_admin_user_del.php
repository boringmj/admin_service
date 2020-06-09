<?php
if(empty($_GET['encode_type']))
$_GET['encode_type']='';
if(empty($_GET['return']))
$_GET['return']='json';
?>
<h1>这里模拟黑客(恶意用户)拦截到的即将提交到服务器的数据</h1>
<form name="input" action="/?class=admin_api&mode=admin_user_del<?php echo "&encode_type={$_GET['encode_type']}&return={$_GET['return']}"?>" method="POST" enctype="multipart/form-data">
<?php
$Encryption=new Encryption();
$server_variable=array(
    'api_id'=>"15910148037Us6Fmu4aUL8S4rIHXsALO",
    'nonce'=>"1008611",
    'time'=>"",
    'time_stamp'=>time(),
    'user'=>isset($_GET['user'])?$_GET['user']:"wuliaomj"
);
$server_sign='';
echo "[POST]app_id<input type=\"text\" name=\"app_id\" value=\"vxRLjxyaGTNa1573840643\"><br>";
foreach($server_variable as $key=>$value)
{
    $server_sign.=$server_sign?"&{$key}={$value}":"{$key}={$value}";
    echo "[POST]";
    //echo "{$key}=>{$value}<br>";
    echo "{$key}<input type=\"text\" name=\"{$key}\" value=\"$value\"><br>";
}
$server_sign_temp=$server_sign.'&api_key=ZNQWOtREgH1JnWKXLGcAe2p5OVISdUG4m9B1';
$server_sign_temp=md5($server_sign_temp);
echo "[POST]sign<input type=\"text\" name=\"sign\" value=\"{$server_sign_temp}\"><br>";
//$server_sign.="&sign={$server_sign_temp}";
//$s=$Encryption->encodeIum($server_sign,"ZNQWOtREgH1JnWKXLGcAe2p5OVISdUG4m9B1");
//echo "sign=>{$server_sign_temp}<br>";
//echo "s原值=>{$server_sign}<br>";
//echo "s<input type=\"text\" name=\"s\" value=\"{$s}\"><br>";
//echo "app_id<input type=\"text\" name=\"app_id\" value=\"{$server_variable['app_id']}\"><br>";
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