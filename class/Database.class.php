<?php

#class/Database

class Database{

    public $prefix;
    public $object;

    protected $host;
    protected $user;
    protected $passwd;
    protected $database;
    protected $error;
    protected $type='mysql';

    //设置连接地址
    public function setHost($host)
    {
        $this->host=$host;
    }
    
    //设置连接用户
    public function setUser($user)
    {
        $this->user=$user;
    }

    //设置连接密码
    public function setPasswd($passwd)
    {
        $this->passwd=$passwd;
    }

    //设置连接库名
    public function setDatabase($database)
    {
        $this->database=$database;
    }

    //获取数据表名称
    public function getTablename($table)
    {
        return "{$this->prefix}{$table}";
    }

    //连接数据库
    public function link()
    {
        try
        {

            $database_link="{$this->type}:host={$this->host};dbname={$this->database}";
            $this->object=new PDO($database_link,$this->user,$this->passwd);
            return 1;
        }
        catch(PDOException $err)
        {
            $this->error=$err->getMessage();
            return 0;
        }
    }
    
}

?>