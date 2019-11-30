<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class UserModel extends AbstractModel {
    
    public function getUserInfo($deviceId) {
        $whereArr = $data = array();
        $whereArr[] = 1;
        $whereArr[] = 'device_id = :device_id';
        $data['device_id'] = $deviceId;
        
        $where = implode(' AND ', $whereArr);
        $sql = 'SELECT * FROM t_user WHERE ' . $where;
        $userInfo = $this->db->getRow($sql, $data);
        if ($userInfo) {
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
        } else {
            $sql = 'INSERT INTO t_user SET
                 device_id = ?,
                 app_name = "walk"';
            $userId = $this->db->lastInsertId();
            $accessToken = md5($userId . time());
            $sql = 'UPDATE t_user SET
                    access_token = ？
                    WHERE user_id = ?';
            $this->db->exec($sql, $accessToken, $userId);
            return  array(
                'userId' => $userId,
                'accessToken' => $accessToken,
                'isRegistered' => true,
                'hasCashed' => true
            );
        }
    }
}