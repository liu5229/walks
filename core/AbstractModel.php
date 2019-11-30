<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class AbstractModel {
    protected $db = FALSE;
    
    
    public function __get ($name) {
        if ('db' == $name) {
            if (!$this->db) {
                $this->db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
                $this->db->exec("SET time_zone = 'Asia/Shanghai'");
                $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } 
            return $this->db;
        }
        throw new \Exception("Can't find plugin " . $name);
    }
}