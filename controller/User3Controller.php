<?php 

Class User3Controller extends User2Controller {
    
    /**
     * 获取用户信息
     * 301 无效设备号
     * @return \ApiReturn
     */
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            $userInfo = $this->model->user3->getUserInfo($this->inputData['deviceId'], $this->inputData['userDeviceInfo'] ?? array());
            if (isset($this->inputData['userDeviceInfo']['source']) && isset($this->inputData['userDeviceInfo']['versionCode'])) {
                $sql = 'SELECT ad_status FROM t_version_ad WHERE version_id = ? AND app_name = ?';
                $userInfo['adStatus'] = $this->db->getOne($sql, $this->inputData['userDeviceInfo']['versionCode'], $this->inputData['userDeviceInfo']['source']) ?: 0;
            } else {
                $userInfo['adStatus'] = 0;
            }
            $this->model->user2->todayFirstLogin($userInfo['userId']);
            $this->model->user2->lastLogin($userInfo['userId']);
            unset($userInfo['userId']);
            return new ApiReturn($userInfo);
        } else {
            return new ApiReturn('', 205, '访问失败，请稍后再试');
        }
    }

}