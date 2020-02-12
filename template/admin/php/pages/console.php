<?php

$content_array=array(
    "\${money}"=>0,
    "\${integral}"=>$User->user_info['integral'],
    "\${application_count}"=>0,
    "\${all_user_number}"=>0,
    "\${php_sapi_name}"=>php_sapi_name(),
    "\${PHP_VERSION}"=>PHP_VERSION,
    "\${organization_name}"=>$main_config['organization_config']['name'],
    "\${organization_development}"=>$main_config['organization_config']['development']
);
foreach($content_array as $key=>$value)
{
    $default_content=str_replace($key,$value,$default_content);
}

?>