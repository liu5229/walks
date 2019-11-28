<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class userModel {
    
    public function getUserInfo($token = '') {
        return  array(
            'userId' => 10000,
            'accessToken' => 'sdffe234fasdf',
            'nickname' => 'sdffe234fasdf',
            'sex' => 1,
            'province' => 'shangHai',
            'city' => 'shangHai',
            'country' => 'China',
            'headimgurl' => 'http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0',
            'isRegistered' => true,
            'hasCashed' => true
        );
    }
}