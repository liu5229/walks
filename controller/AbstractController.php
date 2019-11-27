<?php 

 Class AbstractController {
    protected $temp = array();
    protected $mode = '';
    
    public function __construct()
    {
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
                    $this->temp['db'] = new newPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
                    break;
                default :
                    throw new \Exception("Can't find plugin " . $name);
            }
        }
        return $this->temp[$name];
    }
}