<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class UserModel extends AbstractModel {
    
    public function getUserInfo($token = '') {
        $sql = 'SELECT * FROM t_user WHERE access_token = ?';
        $userInfo = $this->db->getRow($sql, $token);
        return  array(
            'userId' => $userInfo['user_id'],
            'accessToken' => $userInfo['access_token'],
            'nickname' => $userInfo['nickname'],
            'sex' => $userInfo['sex'],
            'province' => $userInfo['province'],
            'city' => $userInfo['city'],
            'country' => $userInfo['country'],
            'headimgurl' => $userInfo['headimgurl'],
            'isRegistered' => true,
            'hasCashed' => true
        );
    }
}