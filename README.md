# admin_service
一个后台管理项目 <br>
本项目开发者:无聊的莫稽(wuliaodemoji@wuliaomj.com) <br>
项目测试环境:lnmp(linux/CentOS7 + php/7.3.6 + mysql/5.5.62 + nginx/1.16.0) <br>
如果有BUG请加QQ3239957605反馈,QQ在线咨询(不能保证我能收到您发送成功的消息):[QQ在线咨询-3239957605](https://wpa.qq.com/msgrd?v=3&uin=3239957605&site=qq&menu=yes&from=message&isappinstalled=0) <br>

# 特别声明
1. 本项目仅允许学习参考,未得到本项目开发者允许的情况下严禁在任何公网环境下搭建、使用
2. 严禁二次开发、开源本项目,严禁篡改本项目版权、署名、项目名称
3. 如需小部分引用请取得本项目开发者同意并注明出处:https://github.com/boringmj/admin_service/
4. 本项目作者保留以上声明的解释权,如有侵权、违反以上声明本项目作者有权依法追究

查看项目文件,克隆下载默认认可以上声明

# 安装部署
这个项目的安装和部署其实并不难,但比较繁琐,您可以参考如下步骤安装
1. 下载或克隆本项目,并拷贝(或剪切)至web文件夹中
2. 安装项目所需扩展,您可以参考`admin_service/main.php`检测的所有扩展
3. 给予指定目录或文件可写权限,您可以参考`admin_service/program/install.php`检测的所有可写目录或文件,如不存在请自行补充
4. 关闭除`admin_service/public`目录和`admin_service/index.php`的web访问权限(别忘记重启服务哟)
5. 配置`admin_service`目录下的所有文件
6. 通过浏览器访问`admin_service/index.php`即可

温馨小提示:
1. 项目必须在web根目录
2. 安装更新请复制更新到项目,`admin_service/config`目录除外,比对`admin_service/config`目录下的变化,自行补充或修改该目录下的文件、目录、代码段,然后访问`admin_service/index.php`安装更新
3. 默认首页请改为`index.php`

下面是Linux系统下一些命令简单用法参考
```
  mkdir <dir_name> //新建目录
  chmod -R a+w <dir_name> //给予所有用户<dir_name>目录的可写权限,其他用法请自行百度
  chown -R <user>[:group] <dir_name> //将<dir_name>目录的所有权转移给[:group]组的<user>
  vi(或vim) <file_name> //使用vi(或vim)编辑器打开<file_name>
 ```

# 技术问题
#### 返回数据可能被拦截修改
目前为止我考虑过的修改方案
1. 加密返回并简单验证
2. 签名验证

两种方案都存在一个问题,修改的工程量较大,所以目前搁这里看看大家的看法
