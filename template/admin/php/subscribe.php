<?php

$content_array=array(
    "\${user_nickname}"=>$User->user_info['nickname']
);
foreach($content_array as $key=>$value)
{
    $default_content=str_replace($key,$value,$default_content);
}

?>