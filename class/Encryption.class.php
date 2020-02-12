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