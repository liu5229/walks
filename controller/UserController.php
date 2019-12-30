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
        $sql = 'SELECT create_time FROM t_sms_code WHERE user_id = ?';
        $smsInfo = $this->db->getOne($sql, $userId);
        if ($smsInfo && strtotime($smsInfo) > strtotime('-1 minutes') ) {
            return new ApiReturn('', 306, '发送太频繁');
        }
        $code = (string) rand(100000, 999999);
        $sms = new Sms();
        $return = $sms->sendMessage($this->inputData['phone'], $code);
        if ($return) {
            $sql = 'REPLACE INTO t_sms_code SET user_id = ?, code_value = ?';
            $this->db->exec($sql, $userId, $code);
            return new ApiReturn('');
        } else {
            //insert error log
            return new ApiReturn('', 303, 'sending failure');
        }
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
        $sql = 'SELECT create_time FROM t_sms_code WHERE user_id = ? AND code_value = ?';
        $codeInfo = $this->db->getOne($sql, $userId, $this->inputData['smsCode']);
        if ($codeInfo) {
            if (strtotime($codeInfo) < strtotime("-5 minutes")) {
                return new ApiReturn('', 308, '验证码过期');
            }
            $sql = 'UPDATE t_user SET phone_number = ? WHERE user_id = ?';
            $this->db->exec($sql, $this->inputData['phone'], $userId);
            $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ?  AND gold_source = ?';
            $awardInfo = $this->db->getOne($sql, $userId, 'phone');
            $return = array();
            if (!$awardInfo) {
                $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "phone"';
                $gold = $this->db->getOne($sql);
                $this->model->user->updateGold(array('user_id' => $userId,
                    'gold' => $gold,
                    'source' => 'phone',
                    'type' => 'in'));
                $return['award'] = $gold;
            }
            $sql = 'DELETE FROM t_sms_code WHERE user_id = ?';
            $this->db->exec($sql, $userId);
            return new ApiReturn($return);
        } else {
            return new ApiReturn('', 307, '验证码错误');
        }
    }
    
    public function getVersionAction () {
        $sql = 'SELECT * FROM t_version ORDER BY version_id DESC LIMIT 1';
        $versionInfo = $this->db->getRow($sql);
        return new ApiReturn(array(
            'versionCode' => $versionInfo['version_id'],
            'versionName' => $versionInfo['version_name'],
            'forceUpdate' => $versionInfo['is_force_update'],
            'apkUrl' => HOST_NAME . $versionInfo['version_url'],
            'updateLog' => $versionInfo['version_log'],
        ));
    }
    
    public function getAdAction() {
        $adCount = array('index' => 3, 'user' => 2);
        if (!isset($this->inputData['location']) && !in_array($this->inputData['location'], array_keys($adCount))) {
            return new ApiReturn('', 305, '没有广告位置');
        }
        $sql = 'SELECT advertise_type type, advertise_name name, CONCAT(?, advertise_image) img, advertise_url url FROM t_advertise WHERE advertise_location = ? AND advertise_status = 1 ORDER BY advertise_id DESC LIMIT ' . $adCount[$this->inputData['location']];
        return new ApiReturn($this->db->getAll($sql, HOST_NAME, $this->inputData['location']));
    }
}