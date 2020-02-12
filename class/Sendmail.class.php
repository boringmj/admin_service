<?php

#class/Sendmail

class Sendmail
{
    protected $config=array();

    protected function sendEmail($to,$name,$subject='',$body='',$attachment=null,$config='')
    {
        $config=is_array($config)?$config:array();
        require_once('PHPMailer/phpmailer.class.php');
        $mail=new PHPMailer();                          //PHPMailer对象
        $mail->CharSet='UTF-8';                         //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->IsSMTP();                                //设定使用SMTP服务
        $mail->IsHTML(true);                            //html邮件
        $mail->SMTPDebug=0;                             //关闭SMTP调试功能 1=errors and messages2=messages only
        $mail->SMTPAuth=true;                           //启用 SMTP 验证功能
        if($config['smtp_port']==465)
            $mail->SMTPSecure='ssl';                    //使用安全协议
        $mail->Host=$config['smtp_host'];               //SMTP 服务器
        $mail->Port=$config['smtp_port'];               //SMTP服务器的端口号
        $mail->Username=$config['smtp_user'];           //SMTP服务器用户名
        $mail->Password=$config['smtp_pass'];           //SMTP服务器密码
        $mail->SetFrom($config['from_email'],$config['from_name']);
        $replyEmail=$config['reply_email']?$config['reply_email']:$config['reply_email'];
        $replyName=$config['reply_name']?$config['reply_name']:$config['reply_name'];
        $mail->AddReplyTo($replyEmail,$replyName);
        $mail->Subject=$subject;
        $mail->MsgHTML($body);
        $mail->AddAddress($to,$name);
        if(is_array($attachment))
        { 
            //添加附件
            foreach ($attachment as $file)
            {
                if(is_array($file))
                {
                    is_file($file['path'])&&$mail->AddAttachment($file['path'],$file['name']);
                }
                else
                {
                    is_file($file)&&$mail->AddAttachment($file);
                }
            }
        }
        else
        {
            is_file($attachment)&&$mail->AddAttachment($attachment);
        }
        return $mail->Send()?1:$mail->ErrorInfo;
    }

    protected function sendMails($title,$content,$to)
    {
        $config=$this->config;
        $config["email_to"]=$to;
        $attachment='';//多个附件使用数组
        $rs=$this->sendEmail($config['email_to'],'',$title,$content,$attachment,$config);
        return $rs;
    }

    protected function getTemplate($template="default")
    {
        if(preg_match('/(.*\..*)/',$template))
            return 0;
        $default_path="template/mail/default.html";
        if(!is_file($default_path))
            return 0;
        $default_content=file_get_contents($default_path);
        $path="template/mail/".$template.'.html';
        if(is_file($path))
        {
            $main_content=file_get_contents($path);
            $main_content=str_replace("\${body}",$main_content,$default_content);
            return $main_content;
        }
        else
        {
            return 0;
        }
    }

    public function send($title,$to,$template,$content_array)
    {
        $main_content=$this->getTemplate($template);
        if($main_content===0)
            return "邮箱模板或默认模板未找到";
        $content_array["{\$}"]='$';
        foreach($content_array as $key=>$value)
        {
            $main_content=str_replace($key,$value,$main_content);
        }
        return $this->sendMails($title,$main_content,$to);
    }

    public function setConfig($config)
    {
        $this->config=$config;
    }

}

