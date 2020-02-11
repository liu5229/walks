<?php 

Class User2Controller extends UserController {
    
    /**
     * 获取用户信息
     * 301 无效设备号
     * @return \ApiReturn
     */
    public function infoAction() {
        if (isset($this->inputData['deviceId'])) {
            $userInfo = $this->model->user2->getUserInfo($this->inputData['deviceId'], $this->inputData['userDeviceInfo'] ?? array());
            if (isset($this->inputData['userDeviceInfo']['source']) && isset($this->inputData['userDeviceInfo']['versionCode'])) {
                $sql = 'SELECT ad_status FROM t_version_ad WHERE version_id = ? AND app_name = ?';
                $userInfo['adStatus'] = $this->getOne($sql, $this->inputData['userDeviceInfo']['source'], $this->inputData['userDeviceInfo']['versionCode']) ?: 0;
            } else {
                $userInfo['adStatus'] = 0;
            }
            $this->model->user2->todayFirstLogin($userInfo['userId']);
            $this->model->user2->lastLogin($userInfo['userId']);
            return new ApiReturn($userInfo);
        } else {
            return new ApiReturn('', 301, '无效设备号');
        }
    }
    
    /**
     * 获取手机验证码
     * @return \ApiReturn|\apiReturn
     */
    public function sendSmsCodeAction () {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
        $phoneInfo = $this->model->user2->userInfo($userId, 'phone_number');
        if ($phoneInfo) {
            return new ApiReturn('', 309, '不能重复绑定');
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
    
    /**
     * 绑定手机号
     * @return \ApiReturn|\apiReturn
     */
    public function buildPhoneAction () {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['phone'])) {
            return new ApiReturn('', 302, 'miss phone number');
        }
        if (!isset($this->inputData['smsCode'])) {
            return new ApiReturn('', 304, 'miss smsCode');
        }
        $phoneInfo = $this->model->user2->userInfo($userId, 'phone_number');
        if ($phoneInfo) {
            return new ApiReturn('', 309, '不能重复绑定');
        }
        $sql = 'SELECT create_time FROM t_sms_code WHERE user_id = ? AND code_value = ?';
        $codeInfo = $this->db->getOne($sql, $userId, $this->inputData['smsCode']);
        if ($codeInfo) {
            if (strtotime($codeInfo) < strtotime("-5 minutes")) {
                return new ApiReturn('', 308, '验证码过期');
            }
            $sql = 'UPDATE t_user SET phone_number = ?, nickname = ? WHERE user_id = ?';
            $this->db->exec($sql, $this->inputData['phone'], substr_replace($this->inputData['phone'], '****', 3, 4), $userId);
            $return = array();
            $sql = 'DELETE FROM t_sms_code WHERE user_id = ?';
            $this->db->exec($sql, $userId);
            return new ApiReturn($return);
        } else {
            return new ApiReturn('', 307, '验证码错误');
        }
    }
    
    /**
     * 获取版本信息
     * @return \ApiReturn|\apiReturn
     */
    public function getVersionAction () {
        $sql = 'SELECT * FROM t_version ORDER BY version_id DESC LIMIT 1';
        $versionInfo = $this->db->getRow($sql);
        return new ApiReturn(array(
            'versionCode' => $versionInfo['version_id'],
            'versionName' => $versionInfo['version_name'],
            'forceUpdate' => $versionInfo['is_force_update'],
            'apkUrl' => HOST_NAME . $versionInfo['version_url'],
            'updateLog' => $versionInfo['version_log'],
            'needUpdateVersionCode' => $versionInfo['need_update_id'],
        ));
    }
    
    /**
     * 获取运营位列表
     * @return \ApiReturn|\apiReturn
     */
    public function getAdAction() {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $adCount = array('start' => 3, 'top' => 4, 'new' => 0, 'daily' => 0);
        if (!isset($this->inputData['location']) || !in_array($this->inputData['location'], array_keys($adCount))) {
            return new ApiReturn('', 305, '没有广告位置');
        }
        $sql = 'SELECT advertise_type, advertise_name, advertise_subtitle, CONCAT(?, advertise_image) img, advertise_url, advertise_validity_type, advertise_validity_type, advertise_validity_start, advertise_validity_end, advertise_validity_length
                FROM t_advertise
                WHERE advertise_location = ?
                AND advertise_status = 1
                ORDER BY advertise_sort DESC';
        $advertiseList = $this->db->getAll($sql, HOST_NAME, $this->inputData['location']);
        $returnList = $tempArr = array();
        $adLimitCount = $adCount[$this->inputData['location']];
        $todayTime = time();
        $taskClass = new Task();
        $userCreateTime = $this->model->user2->userInfo($userId, 'create_time');
        $isAllBuild = 'new' == $this->inputData['location'] ? TRUE : FALSE;
        foreach ($advertiseList as $advertiseInfo) {
            if ($adLimitCount && $adLimitCount <= count($returnList)) {
                break;
            }
            switch ($advertiseInfo['advertise_validity_type']) {
                case 'fixed':
                    if ($todayTime < strtotime($advertiseInfo['advertise_validity_start']) || $todayTime > strtotime($advertiseInfo['advertise_validity_end'])) {
                        break 2;
                    }
                    break;
                case 'limited':
                    $adEndTime = strtotime('+ ' . $advertiseInfo['advertise_validity_length'] . 'days', strtotime($userCreateTime));
                    if ($adEndTime < $todayTime) {
                        break 2;
                    }
                    break;
            }
            $tempArr = array('type' => $advertiseInfo['advertise_type'],
                'name' => $advertiseInfo['advertise_name'],
                'subName' => $advertiseInfo['advertise_subtitle'],
                'img' => $advertiseInfo['img'],
                'url' => $advertiseInfo['advertise_url']);
            if ('task' == $advertiseInfo['advertise_type']) {
                $tempArr['info'] = $taskClass->getTask($advertiseInfo['advertise_url'], $userId);
                if ($isAllBuild) {
                    $isAllBuild = $tempArr['info']['isBuild'] ? TRUE : FALSE;
                }
            }
            $returnList[] = $tempArr;
        }
        if ($isAllBuild) {
            $returnList = [];
        }
        return new ApiReturn($returnList);
    }
    
    /**
     * 绑定支付宝
     * @return \ApiReturn|\apiReturn
     */
    public function buildAlipayAction () {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['account'])) {
            return new ApiReturn('', 310, '缺少支付宝账号');
        }
        if (!isset($this->inputData['name'])) {
            return new ApiReturn('', 310, '缺少支付宝姓名');
        }
        if (!isset($this->inputData['idCard'])) {
            return new ApiReturn('', 310, '缺少身份证号码');
        }
        $sql = 'UPDATE t_user SET alipay_account = ?, alipay_name = ?, id_card = ? WHERE user_id = ?';
        $this->db->exec($sql, $this->inputData['account'], $this->inputData['name'], $this->inputData['idCard'], $userId);
        return new ApiReturn('');
    }
    
    /**
     * 获取提现信息
     * @return \ApiReturn|\apiReturn
     */
    public function getWithdrawAction () {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $sql = 'SELECT alipay_account account, phone_number phone FROM t_user WHERE user_id = ?';
        $userInfo = $this->db->getRow($sql, $userId);
        if ($userInfo['account']) {
            $userInfo['account'] = substr_replace($userInfo['account'], '****', 3, 4);
        }
        if ($userInfo['phone']) {
            $userInfo['phone'] = substr_replace($userInfo['phone'], '****', 3, 4);
        }
        return new ApiReturn($userInfo);
    }
    
    /**
     * 绑定微信
     * @return \ApiReturn|\apiReturn
     */
    public function buildWechatAction () {
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['unionid'])) {
            return new ApiReturn('', 311, 'miss unionid');
        }
        $unionInfo = $this->model->user2->userInfo($userId, 'unionid');
        if ($unionInfo) {
            return new ApiReturn('', 309, '不能重复绑定');
        }
        $sql = 'UPDATE t_user SET openid = ?, nickname = ?, language = ?, sex = ?, province = ?, city = ?, country = ?, headimgurl = ?, unionid = ? WHERE user_id = ?';
        $this->db->exec($sql, $this->inputData['openid'] ?? '', $this->inputData['nickname'] ?? '', $this->inputData['language'] ?? '', $this->inputData['sex'] ?? 0, $this->inputData['province'] ?? '', $this->inputData['city'] ?? '', $this->inputData['country'] ?? '', $this->inputData['headimgurl'] ?? '', $this->inputData['unionid'], $userId);
        $return = array();
        $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ?  AND gold_source = ?';
        $awardInfo = $this->db->getOne($sql, $userId, 'wechat');
        if (!$awardInfo) {
            $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "wechat"';
            $gold = $this->db->getOne($sql);
            $this->model->user2->updateGold(array('user_id' => $userId,
                'gold' => $gold,
                'source' => 'wechat',
                'type' => 'in'));
            $return['award'] = $gold;
        }
        return new ApiReturn($return);
    }
    
    /**
     * 填写邀请码
     * @return \ApiReturn|\apiReturn
     */
    public function buildInvitedAction () {
        $invitedId = $this->model->user2->verifyToken();
        if ($invitedId instanceof apiReturn) {
            return $invitedId;
        }
        if (!isset($this->inputData['invitedCode'])) {
            return new ApiReturn('', 312, 'miss invited code');
        }
        $sql = 'SELECT COUNT(*) FROM t_user_invited WHERE invited_id = ?';
        $invitedInfo = $this->db->getOne($sql, $invitedId);
        if ($invitedInfo) {
            return new ApiReturn('', 309, '不能重复绑定');
        }
        $sql = 'SELECT user_id, create_time FROM t_user WHERE invited_code = ?';
        $userInfo = $this->db->getRow($sql, $this->inputData['invitedCode']);
        if (!$userInfo) {
            return new ApiReturn('', 313, '无效的邀请码');
        }
        $invitedCreate = $this->model->user2->userInfo($invitedId, 'create_time');
        if (strtotime($invitedCreate) < strtotime($userInfo['create_time'])) {
            return new ApiReturn('', 314, '填写失败');//
        }
        $sql = 'INSERT INTO t_user_invited SET user_id = ?, invited_id = ?';
        $this->db->exec($sql, $userInfo['user_id'], $invitedId);
        
        $return = array();
        $sql = 'SELECT COUNT(*) FROM t_gold WHERE user_id = ?  AND gold_source = ?';
        $awardInfo = $this->db->getOne($sql, $userInfo['user_id'], 'invited');
        if (!$awardInfo) {
            $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "invited"';
            $gold = $this->db->getOne($sql);
            $this->model->user2->updateGold(array('user_id' => $invitedId,
                'gold' => $gold,
                'source' => 'invited',
                'type' => 'in'));
            $return['award'] = $gold;
            $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "do_invite"';
            $gold = $this->db->getOne($sql);
            $this->model->user2->updateGold(array('user_id' => $userInfo['user_id'],
                'gold' => $gold,
                'source' => 'do_invite',
                'type' => 'in'));
        }
        return new ApiReturn($return);
    }
}