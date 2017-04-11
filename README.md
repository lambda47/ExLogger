# ExLogger
ExLogger是一个CI的Library：

1.  应用save方式会以日志文件保存此次请求的控制器和执行的方法，GET参数，POST参数，SESSION变量和所执行的全部SQL语句，默认存储在logs文件夹下，文件名默认为exlog-YYYY-mm-dd.php
2.  应用console方式会将此次请求的控制器和执行方法，GET参数，POST参数，SESSION变量和所执行的全部SQL语句信息保存在响应的HTTP header中，配合chrome插件ExLogger For Chrome，可以在Chrome浏览器的console中显示信息

##调用方式
通过构造器调用
```php
$logger = new ExLogger(ExLogger::LOG_REQUEST | ExLogger::LOG_SESSION | ExLogger::LOG_QUERY);
$logger->save();
```
```php
$logger = new ExLogger(ExLogger::LOG_ALL);
$logger->console();
```
链式操作调用
```php
$logger = new ExLogger();
$logger->get()->post()->session()->queries()->save();
```
```php
$logger = new ExLogger();
$logger->request()->session()->queries()->console();
```
以save、console、save_console、console_save开头的方法调用
```php
$logger = new ExLogger();
//save后面接post、get、request、session、requires，数量和顺序可变，以下划线“_”分割
$logger->save_post_queries();
```
```php
$logger = new ExLogger();
//console后面接post、get、request、session、requires，数量和顺序可变，以下划线“_”分割
$logger->console_post_queries_session();
```
##每次请求后自动执行
可以在config中开启hook，然后在post_controller hook中调用ExLogger

```php
$config['enable_hooks'] = TRUE;
```
```php
$hook['post_controller'] = function () {
    $CI =& get_instance();
    $CI->load->library('ExLogger');
    $logger = new ExLogger();
    $logger->save_request_session_queries();
};
```
**post\_controller hook会在控制器完全运行结束时执行，如果程序执行中通过exit或die退出，post_controller hook中的代码将无法执行**

这种情况下可以通过register\_shutdown\_function方式实现，在index.php中添加如下代码

```php
if (ENVIRONMENT != 'production')
{
	ob_start();
	register_shutdown_function(function ()
	{
		$CI =& get_instance();
		$CI->load->library('ExLogger');
		$logger = new ExLogger();
		$logger->console_request_session_queries();
		ob_flush();
	});
}

require_once BASEPATH.'core/CodeIgniter.php';
```
##EXPLAIN SQL
对支持explain命令的数据库，记录执行sql explain的结果
```php
$logger = new Exlogger();
$logger->explain_sql(true);
```
```php
$logger = new Exlogger(Exlogger::SQL_EXPLAIN);
```