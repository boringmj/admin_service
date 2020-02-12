<?php

#class/Encryption

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
        //取秘钥长度
        $keylen=strlen($key);
        //循环异或待处理数据
        for($i=0;$i<strlen($str);$i++)
        {
            //取需要使用的秘钥位置
            $k=$i%$keylen;
            //对数据异或
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
    *解密IUM加密的数据
    */
    public function decodeIum($str,$key)
    {
        return base64_decode($this->xorEnc(base64_decode($str),base64_encode(md5($key))));
    }
}

$a=new Encryption();
$str=isset($_GET['str'])?$_GET['str']:"";
$key=isset($_GET['key'])?$_GET['key']:"";
$en=$a->encodeIum($str,$key);
$de=$a->decodeIum(isset($_GET['y'])?$_GET['str']:$en,$key);
echo "加密前:{$str}<br>加密后:{$en}<br>解密后:{$de}";
echo "<br>-----Debug-----<br>秘钥:".$key."<br>秘钥处理后:".base64_encode(md5($key))."<br>待加密数据base64码:".base64_encode($str)."<br>1的异或base64码:".base64_encode($a->xorEnc('1','1'))."<br>取md5值:".md5(base64_encode(md5($key)));

?>
