<?php
/**
 * @author  Lambda47
 * @version 1.0
 * @link    https://github.com/lambda47/ExLogger
 */

/**
 * Class ExLogger
 * 记录每次访问的GET参数、POST参数、SESSION变量和执行的SQL语句
 */
class ExLogger {
    protected $CI;
    /**
     * 所有执行的SQL语句
     * @access protected
     * @var array
     */
    protected $queries = array();
    /**
     * 是否记录执行的SQL语句
     * @access private
     * @var bool
     */
    private $log_query = false;
    /**
     * 是否记录GET参数
     * @access private
     * @var bool
     */
    private $log_get = false;
    /**
     * 是否记录POST参数
     * @access private
     * @var bool
     */
    private $log_post = false;
    /**
     * 是否记录SESSION变量
     * @access private
     * @var bool
     */
    private $log_session = false;
    /**
     * 目录名
     * @access private
     * @var string
     */
    private $directory_name;
    /**
     * 控制器名
     * @access private
     * @var string
     */
    private $controller_name;
    /**
     * 调用方法名
     * @access private
     * @var string
     */
    private $action_name;

    /**
     * 记录GET参数
     * @var int
     */
    const LOG_GET = 0b1;
    /**
     * 记录POST参数
     * @var int
     */
    const LOG_POST = 0b10;
    /**
     * 记录GET和POST参数
     * @var int
     */
    const LOG_REQUEST = 0b11;
    /**
     * 记录SESSION变量
     * @var int
     */
    const LOG_SESSION = 0b100;
    /**
     * 记录执行的SQL语句
     * @var int
     */
    const LOG_QUERY = 0b1000;

    /**
     * 构造函数
     *
     * @access public
     * @param int|null $log_option 要记录的内容(GET、POST、SESSION、QUERY)
     */
    public function __construct($log_option = NULL) {
        $this->CI =& get_instance();
        $this->directory_name = empty($this->CI->router->directory) ? ''
            : substr($this->CI->router->directory, 0, strlen($this->CI->router->directory) - 1);
        $this->controller_name = empty($this->CI->router->class) ? '' : $this->CI->router->class;
        $this->action_name = empty($this->CI->router->method) ? '' : $this->CI->router->method;
        if (isset($log_option))
        {
            $this->get($log_option & self::LOG_GET);
            $this->post($log_option & self::LOG_POST);
            $this->session($log_option & self::LOG_SESSION);
            $this->queries($log_option & self::LOG_QUERY);
        }
    }

    /**
     * 魔术方法，处理未定义的方法名
     *
     * 处理的方法名包含get、post、session、require、queries、save_get、save_post、save_get_post、save_post_get、
     * save_request_session_queries ...
     *
     * @access public
     * @param string $name 方法名
     * @param array $arguments 调用方法时传递的参数
     * @return $this|void get、post、session、require和queries方法返回$this，save开头的方法返回void
     */
    public function __call($name, $arguments) {
        $arg = empty($arguments) ? true : $arguments[0];
        switch ($name)
        {
            case 'get':
            case 'post':
            case 'session':
                $this->{'log_'.$name} = $arg;
                break;
            case 'request':
                $this->log_get = $arg;
                $this->log_post = $arg;
                break;
            case 'queries':
                if ($arg) {
                    $this->queries = $this->get_queries();
                }
                $this->log_query = $arg;
                break;
            default:
                $name_ary = explode('_', $name);
                if ($name_ary[0] === 'save')
                {
                    array_shift($name_ary);
                    foreach ($name_ary as $item)
                    {
                        call_user_func(array($this, $item), empty($arguments) ? array(true) : $arguments);
                    }
                    $this->save();
                    return;
                }
                break;
        }
        return $this;
    }

    /**
     * 获取本次访问执行的所有SQL语句
     *
     * @access protected
     * @return array 本次访问执行的所有SQL语句
     */
    protected function get_queries() {
        $queries = array();
        foreach (get_object_vars($this->CI) as $name => $value)
        {
            if (is_object($value) && $value instanceof CI_Model)
            {
                $query_sqls = $value->db->queries;
                $query_times = $value->db->query_times;
                foreach ($query_sqls as $index => $sql) {
                    $queries[] = array('sql' => $sql, 'time' => $query_times[$index]);
                }
                break;
            }
        }
        return $queries;
    }

    /**
     * 将记录的数据保存到日志文件
     *
     * @access public
     * @return void
     */
    public function save() {
        $log_path = ($this->CI->config->item('log_path') !== '') ? $this->CI->config('log_path') : APPPATH.'logs'.DIRECTORY_SEPARATOR;
        if (!file_exists($log_path))
        {
            mkdir($log_path, 0755, $log_path);
        }
        $log_file = 'exlog-'.date('Y-m-d').'.php';
        if ($fp = @fopen($log_path.$log_file, 'ab'))
        {
            flock($fp, LOCK_EX);
            $request_message =  date('Y-m-d H:i:s')."\t".$this->directory_name."\t".$this->controller_name . ' => ' . $this->action_name."\n";
            fwrite($fp, $request_message);
            if ($this->log_get)
            {
                fwrite($fp, str_repeat('=', 100)."\n");
                fwrite($fp, 'GET:'.(empty($_GET) ? 'Empty' : '')."\n");
                if (!empty($_GET))
                {
                    foreach ($_GET as $key => $value)
                    {
                        if (is_array($value))
                        {
                            fwrite($fp, $key . ":\t" . var_export($value, true) . "\n");
                        }
                        else
                        {
                            fwrite($fp, $key . ":\t" . $value . "\n");
                        }
                    }
                }
            }
            if ($this->log_post)
            {
                fwrite($fp, str_repeat('-', 100)."\n");
                fwrite($fp, 'POST:'.(empty($_POST) ? 'Empty' : '')."\n");
                if (!empty($_POST))
                {
                    foreach ($_POST as $key => $value)
                    {
                        if (is_array($value))
                        {
                            fwrite($fp, $key . ":\t" . var_export($value, true) . "\n");
                        }
                        else
                        {
                            fwrite($fp, $key . ":\t" . $value . "\n");
                        }
                    }
                }
            }
            if ($this->log_session)
            {
                fwrite($fp, str_repeat('-', 100)."\n");
                fwrite($fp, 'SESSION:'.(empty($_SESSION) ? 'Empty' : '')."\n");
                if (!empty($_SESSION))
                {
                    foreach ($_SESSION as $key => $value)
                    {
                        if (is_array($value))
                        {
                            fwrite($fp, $key . ":\t" . var_export($value, true) . "\n");
                        }
                        else
                        {
                            fwrite($fp, $key . ":\t" . $value . "\n");
                        }
                    }
                }
            }
            if ($this->log_query)
            {
                fwrite($fp, str_repeat('-', 100)."\n");
                fwrite($fp, 'QUERY:'.(empty($this->queries) ? 'Empty' : '')."\n");
                foreach($this->queries as $key => $value)
                {
                    $query_sql = preg_replace('/\s+/', ' ', $value['sql']);
                    fwrite($fp, ($key + 1).":\t(".$value['time']." microsecond)\t".$query_sql."\n");
                }
            }
            fwrite($fp, "\n\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
