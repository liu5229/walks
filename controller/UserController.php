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
    
    public function getAdAction() {
        $adCount = array('index' => 3, 'user' => 2);
        if (!isset($this->inputData['location']) && !in_array($this->inputData['location'], array_keys($adCount))) {
            return new ApiReturn('', 305, '没有广告位置');
        }
        $sql = 'SELECT advertise_type type, CONCAT(?, ?, advertise_image) img, advertise_url url FROM t_advertise WHERE advertise_location = ? AND advertise_status = 1 ORDER BY advertise_id DESC LIMIT ' . $adCount[$this->inputData['location']];
        return new ApiReturn($this->db->getAll($sql, HOST_NAME, IMG_DIR, $this->inputData['location']));
    }
}