<?php

$content_array=array(
    "\${user_nickname}"=>$Safety->xss($User->user_info['nickname']),
    "\${user_name}"=>$Safety->xss($User->user_info['name']),
    "\${time}"=>$User->user_info['time'],
    "\${identification_name}"=>$User->user_info['identification_name'],
    "\${integral}"=>$User->user_info['integral']
);
foreach($content_array as $key=>$value)
{
    $default_content=str_replace($key,$value,$default_content);
}

?>