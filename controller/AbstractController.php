<?php 

 Class AbstractController {
    protected $temp = array();
    protected $mode = '';
    protected $inputData;
    
    public function __construct()
    {
        $this->inputData = json_decode(file_get_contents("php://input"), TRUE);
//        $this->mode = 'POST';
//        if ($this->mode) {
//            echo 'to do';//
//        }
    }

    public function __get($name) 
    {
        if (!isset($this->temp[$name])) {
            switch ($name) {
                case 'db':
                    echo 111;
                    $this->temp['db'] = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
                    $this->temp['db']->exec("SET time_zone = 'Asia/Shanghai'");
                    break;
                case 'model':
                    $this->temp['model'] = new Model();
                    break;
                default :
                    throw new \Exception("Can't find plugin " . $name);
            }
        }
        return $this->temp[$name];
    }
}