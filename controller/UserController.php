<?php 

Class UserController extends AbstractController {
    
    /**
     * 301 无效设备号
     * @return \ApiReturn
     */
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            $userInfo = $this->model->user->getUserInfo($this->inputData['deviceId']);
            return new ApiReturn($userInfo);
        } else {
            return new ApiReturn('', 301, '无效设备号');
        }
    }
    
    public function sendSmsCodeAction () {
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
//        var_dump($_SERVER);
        return new ApiReturn('');
        
        //insert error log
        return new ApiReturn('', 303, 'sending failure');
    }
    
    public function buildPhoneAction () {
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
        if (!isset($this->inputData['smsCode'])) {
            return new ApiReturn('', 304, 'miss smsCode');
        }
        $userInfo = $this->model->user->getUserInfo(10000);
        return new ApiReturn($userInfo);
    }
    
    public function getVersionAction () {
        $sql = 'SELECT * FROM t_version ORDER BY version_id DESC LIMIT 1';
        $versionInfo = $this->db->getRow($sql);
        return new ApiReturn(array(
            'versionCode' => $versionInfo['version_id'],
            'versionName' => $versionInfo['version_name'],
            'forceUpdate' => $versionInfo['is_force_update'],
            'apkUrl' => HOST_NAME . APP_DIR . $versionInfo['version_url'],
            'updateLog' => $versionInfo['version_log'],
        ));
    }
}