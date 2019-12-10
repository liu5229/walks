<?php 

 Class AbstractController {
    protected $temp = array();
    protected $mode = '';
    protected $inputData;
    
    public function __construct()
    {
        $this->init();
//        $this->mode = 'POST';
//        if ($this->mode) {
//            echo 'to do';//
//        }
    }
    
    public function init()
    {
        $this->inputData = json_decode(file_get_contents("php://input"), TRUE);
    }

    public function __get($name) 
    {
        if (!isset($this->temp[$name])) {
            switch ($name) {
                case 'db':
                    $this->temp['db'] = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
                    $this->temp['db']->exec("SET time_zone = 'Asia/Shanghai'");
                    $this->temp['db']->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                    break;
                case 'model':
                    $this->temp['model'] = new Model();
                    break;
                default :
                    $pluginName = ucfirst($name);
                    $this->temp[$name] = new $pluginName();
            }
        }
        return $this->temp[$name];
    }
}