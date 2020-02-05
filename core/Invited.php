<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Invited extends AbstractModel {
    protected $length = 8;
    
    public function createCode() {
        $createList = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $code = '';
        for($i=0;$i<$this->length;$i++) {
            $code .= $createList{rand(0, 33)};
        }
        $sql = 'SELECT COUNT(user_id) FROM t_user WHERE invited_code = ?';
        $isExist = $this->db->getOne($sql);
        if ($isExist) {
            return $this->createCode();
        }
        return $code;
    }
}