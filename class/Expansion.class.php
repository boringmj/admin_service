<?php

#class/Expansion

class Expansion
{
    
    /** doHttpPost ：执行POST请求，并取回响应结果
     * 参数说明
     * - $url   ：接口请求地址
     * - $params：完整接口请求参数
     * 返回数据
     * - 返回false表示失败，否则表示API成功返回的HTTP BODY部分
     * 特别声明
     * 本方法来自腾讯开放平台
    */
    public function doHttpPost($url,$params)
    {
        $curl=curl_init();
        $response=false;
        do
        {
            curl_setopt($curl,CURLOPT_URL,$url);
            $head=array(
                'Content-Type: application/x-www-form-urlencoded'
            );
            curl_setopt($curl,CURLOPT_HTTPHEADER,$head);
            $body=http_build_query($params);
            curl_setopt($curl,CURLOPT_POST,true);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
            curl_setopt($curl,CURLOPT_HEADER,false);
            curl_setopt($curl,CURLOPT_NOBODY,false);
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,true);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
            $response=curl_exec($curl);
            if ($response===false)
            {
                $response=false;
                break;
            }
    
            $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
            if ($code!=200)
            {
                $response=false;
                break;
            }
        }while(0);
        curl_close($curl);
        return $response;
    }

}

?>