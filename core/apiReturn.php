<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class apiReturn {
    
    protected $msg = '';
    protected $data = array();
    protected $code = 200;
    
    public function __construct ($data, $code = 200, $msg = '') {
        $this->data = $data;
        $this->code = $code;
        $this->msg = $msg;
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
    
    public function __get($name) {
        return $this->$name;
    }
}