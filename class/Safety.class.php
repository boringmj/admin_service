<?php

#class/Safety

class Safety
{

    public function xss($str)
    {
        return htmlspecialchars($str);
    }

    public function sql($str)
    {
        return addslashes($str);
    }

    public function filter($str)
    {
        $content_array=array(
            "\\","\$","\"","'","#","%","&","<",">","/",";","*"
        );
        foreach($content_array as $value)
        {
            $str=str_replace($value,'',$str);
        }
        return $str;
    }

    public function value_url($str)
    {
        $content_array=array(
            "\\","\"","'","<",">",":"
        );
        foreach($content_array as $value)
        {
            $str=str_replace($value,'',$str);
        }
        return $str;
    }

}

?>