<?php 

Class UserController extends AbstractController {
    
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            if (isset($this->inputData['accessToken'])) {
                
            } else {
                
            }
            $userInfo = array(
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
            return new apiReturn($userInfo);
        } else {
            return new apiReturn('', 301, 'miss device id');
        }
    }
    
    public function sendSmsCodeAction () {
        if (!isset($this->inputData['phone'])) {
            return new apiReturn('', 302, 'miss phone number');
        }
//        var_dump($_SERVER);
        return new apiReturn('');
        
        //insert error log
        return new apiReturn('', 303, 'sending failure');
    }
    
    public function buildPhoneAction () {
        if (!isset($this->inputData['phone'])) {
            return new apiReturn('', 302, 'miss phone number');
        }
        if (!isset($this->inputData['smsCode'])) {
            return new apiReturn('', 304, 'miss smsCode');
        }
        $userInfo = $this->model->user->getUserInfo();
        return new apiReturn($userInfo);
        
    }
    
}