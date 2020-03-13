<?php

#class/Adminapi

class Adminapi
{
    //必要参数(目前没有提供函数设置)
    public $database_object;            //数据库对象
    public $admin_config=array();       //后台配置信息

    //系统回馈参数(系统一般不直接返回结果,需要回馈参数中自行查找,大多回馈参数出于提高效率而存在的,不可直接访问)
    public $api_info=array();        //查询到的api_id对应的信息(至少需要调用一次checkApi()才会有数据)
    public $error_info=array();         //错误信息


    //检查api_id的状态,不进行及时过期处理,但过期不能被使用
    public function checkApi($api_id)
    {
        $server_time_stamp=time();
        $table_name=$this->database_object->getTablename('admin_application');
        $sql_statement=$this->database_object->object->prepare("SELECT * FROM {$table_name} WHERE api_id=:api_id AND expired_time_stamp>:time_stamp AND api_states='Y' ORDER BY id DESC LIMIT 0,1");
        $sql_statement->bindParam(':api_id',$api_id);
        $sql_statement->bindParam(':time_stamp',$server_time_stamp);
        $sql_statement->execute();
        $result_sql_temp=$sql_statement->fetch(PDO::FETCH_ASSOC);
        if(!empty($result_sql_temp['uuid']))
        {
            $this->api_info=$result_sql_temp;
            return 1;
        }
        else
        {
            $this->error_info['checkApi']=array(
                'code'=>1065,
                'title'=>"失败",
                'content'=>"请求异常",
                'variable'=>''
            );
            return 0;
        }
    }
}

?>