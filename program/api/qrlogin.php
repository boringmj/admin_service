<?php

#program/api/qrlogin

$result['mode']=3;
include_class("Qrcode");
QRcode::png("ABC",false,QR_ECLEVEL_L,10,5,false);

?>