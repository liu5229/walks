<?php 

Class UserController extends AbstractController {
    
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            $userInfo = $this->model->user->getUserInfo($this->inputData['deviceId']);
            return new ApiReturn($userInfo);
        } else {
            return new ApiReturn('', 301, 'miss device id');
        }
    }
    
    public function sendSmsCodeAction () {
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
//        var_dump($_SERVER);
        return new ApiReturn('');
        
        //insert error log
        return new ApiReturn('', 303, 'sending failure');
    }
    
    public function buildPhoneAction () {
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
        if (!isset($this->inputData['smsCode'])) {
            return new ApiReturn('', 304, 'miss smsCode');
        }
        $userInfo = $this->model->user->getUserInfo(10000);
        return new ApiReturn($userInfo);
        
    }
    
}