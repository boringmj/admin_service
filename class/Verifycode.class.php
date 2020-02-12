<?php

#class/Verifycode

class Verifycode
{
    public static function get($one,$two, $font_size = 28)
    {
        //文件头...
        ob_get_clean();

        header("Content-Type:image/png;charset=utf-8;");

        //创建真彩色白纸
        $width            = $font_size*5;
        $height           = $font_size+1;
        $im               = imagecreatetruecolor($width, $height);
        //获取背景颜色
        $background_color = imagecolorallocate($im, 255, 255, 255);
        //填充背景颜色
        imagefill($im, 0, 0, $background_color);
        //获取边框颜色
        $border_color     = imagecolorallocate($im, 200, 200, 200);
        //画矩形，边框颜色200,200,200
        imagerectangle($im,0,0,$width - 1, $height - 1,$border_color);

        //逐行炫耀背景，全屏用1或0
        for($i = 2;$i < $height - 2;$i++) {
            //获取随机淡色
            $line_color = imagecolorallocate($im, rand(200,255), rand(200,255), rand(200,255));
            //画线
            imageline($im, 2, $i, $width - 1, $i, $line_color);
        }

        //设置印上去的文字
        $firstNum  = $one;
        $secondNum = $two;
        $actionStr = $firstNum > $secondNum ? '-' : '+';

        //获取第1个随机文字
        $imstr[0]["s"] = $firstNum;
        $imstr[0]["x"] = rand(2, 5);
        $imstr[0]["y"] = rand(1, 4);

        //获取第2个随机文字
        $imstr[1]["s"] = $actionStr;
        $imstr[1]["x"] = $imstr[0]["x"] + $font_size - 1 + rand(0, 1);
        $imstr[1]["y"] = rand(1,5);

        //获取第3个随机文字
        $imstr[2]["s"] = $secondNum;
        $imstr[2]["x"] = $imstr[1]["x"] + $font_size - 1 + rand(0, 1);
        $imstr[2]["y"] = rand(1, 5);

        //获取第3个随机文字
        $imstr[3]["s"] = '=';
        $imstr[3]["x"] = $imstr[2]["x"] + $font_size - 1 + rand(0, 1);
        $imstr[3]["y"] = 3;

        //获取第3个随机文字
        $imstr[4]["s"] = '?';
        $imstr[4]["x"] = $imstr[3]["x"] + $font_size - 1 + rand(0, 1);
        $imstr[4]["y"] = 3;

        //文字
        $text = '';
        //写入随机字串
        for($i = 0; $i < 5; $i++) {
            //获取随机较深颜色
            $text_color = imagecolorallocate($im, rand(50, 180), rand(50, 180), rand(50, 180));
            $text .= $imstr[$i]["s"];
            //画文字
            imagechar($im, $font_size, $imstr[$i]["x"], $imstr[$i]["y"], $imstr[$i]["s"], $text_color);
        }
        //显示图片
        ImagePng($im);
        //销毁图片
        ImageDestroy($im);
    }
}  