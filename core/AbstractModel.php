<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class AbstractModel {
    
    
    public function __get ($name) {
        if ('db' == $name) {
            $return = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
            $return->exec("SET time_zone = 'Asia/Shanghai'");
            $return->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return $return;
        }
        throw new \Exception("Can't find plugin " . $name);
    }
}