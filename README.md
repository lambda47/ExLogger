# ExLogger
ExLogger十一个CI的Library，以日志文件保存此次请求的控制器和执行的方法，GET参数，POST参数，SESSION变量和所执行的全部SQL语句，默认存储在logs文件夹下，文件名默认为exlog-YYYY-mm-dd.php。

##调用方式
通过构造器调用
<pre>
$logger = new ExLogger(ExLogger::LOG_REQUEST | ExLogger::LOG_SESSION | ExLogger::LOG_QUERY);
$logger->save();
</pre>
链式操作调用
<pre>
$logger = new ExLogger();
$logger->get()->post()->session()->queries()->save();
</pre>
以save开头的方法调用
<pre>
$logger = new ExLogger();
//save后面接post、get、request、session、requires，数量和顺序可变，以下划线“_”分割
$logger->save_post_queries();
</pre>
##每次请求后自动执行
可以在config中开启hook，然后在post_controller hook中调用ExLogger。
<pre>
$config['enable_hooks'] = TRUE;
</pre>
<pre>
$hook['post_controller'] = function () {
    $CI =& get_instance();
    $CI->load->library('ExLogger');
    $logger = new ExLogger();
    $logger->save_request_session_queries();
};
</pre>