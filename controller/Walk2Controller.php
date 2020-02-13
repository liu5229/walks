<?php 

Class Walk2Controller extends WalkController {
    //提现汇率
    protected $withdrawalRate = 10000;
    protected $userId;
    
    /**
     * 验证用户有效性
     * 
     */
    public function init() {
        parent::init();
        $userId = $this->model->user2->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }
    
    /**
     * 更新用户步数
     * 401 无效更新
     * @return \ApiReturn
     * 
     */
    public function updateWalkAction () {
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, '无效更新');
        }
        $walkReward = new WalkCounter2($this->userId, $this->inputData['stepCount']);
        return new ApiReturn(array('stepCount' => $walkReward->getStepCount()));
    }

    /**
     * 获取任务信息
     * 501 无效获取
     * @return \ApiReturn
     * 
     */
    public function taskAction() {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 501, '无效获取');
        }
        $taskClass = new Task();
        $return = $taskClass->getTask($this->inputData['type'], $this->userId);
        if ($return instanceof ApiReturn) {
            return $return;
        }
        return new ApiReturn($return);
    }
    
    /**
     * 领取任务奖励
     * 402 无效领取
     * 403 重复领取
     * 404 今日已签到
     * 405 领取时间未到
     * 406 先获取任务信息
     * @return \ApiReturn
     * 
     */
    public function getAwardAction () {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 402, '无效领取');
        }
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
        if (!$activityInfo) {
            return new ApiReturn('', 402, '无效领取');
        }
        if (!$activityInfo['activity_status']) {
            return new ApiReturn('', 204, '活动结束领取奖励失败');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter2($this->userId, $this->inputData['stepCount'] ?? 0);
                $receiveInfo = $walkReward->verifyReceive(array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                ));
                if ($receiveInfo) {
                    if (1 == $receiveInfo['receive_status']) {
                        return new ApiReturn('', 403, '重复领取');
                    } else {
                        $doubleStatus = $this->inputData['isDouble'] ?? 0;
                        $updateStatus = $this->model->user2->updateGold(array(
                            'user_id' => $this->userId,
                            'gold' => $this->inputData['num'] * ($doubleStatus + 1),
                            'source' => $this->inputData['type'],
                            'type' => 'in',
                            'relation_id' => $this->inputData['id']));
                        if (TRUE === $updateStatus) {
                            $walkReward->receiveSuccess($this->inputData['id'], $doubleStatus);
                            $goldInfo = $this->model->user2->getGold($this->userId);
                            return new ApiReturn(array('awardGold' => $this->inputData['num'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                        }
                        return $updateStatus;
                    }
                } else {
                    return new ApiReturn('', 402, '无效领取');
                }
            case 'newer'://user2model/get-userInfo
            case 'wechat'://user2/build-wechat
            case 'do_invite'://user2/build-invited
            case 'invited'://user2/build-invited
            case 'invited_count'://脚本crons/invited_count.php
            case 'lottery'://activity2/lottery-award
                return new ApiReturn('', 402, '无效领取');
            case 'sign':
                $sql = 'SELECT receive_id, receive_status, receive_gold, end_time, is_double
                        FROM t_gold2receive
                        WHERE receive_id =:receive_id
                        AND user_id = :user_id
                        AND receive_gold = :receive_gold
                        AND receive_type = :receive_type
                        AND receive_date = :receive_date';
                $historyInfo = $this->db->getRow($sql, array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'user_id' => $this->userId,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                   'receive_date' => $today,
                ));
                if (!$historyInfo) {
                    return new ApiReturn('', 402, '无效领取');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $secondDoubleStatus = $this->inputData['secondDou'] ?? 0;
                if ($historyInfo['receive_status']) {
                    if (!$secondDoubleStatus) {  
                        return new ApiReturn('', 404, '今日已签到');
                    } elseif ($historyInfo['is_double']) {
                        return new ApiReturn('', 402, '无效领取');
                    }
                } else {
                    $sql = 'UPDATE t_user SET check_in_days = check_in_days + 1 WHERE user_id = ?';
                    $this->db->exec($sql, $this->userId);
                }
                $updateStatus = $this->model->user2->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $historyInfo['receive_gold'] * ($doubleStatus + 1),
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyInfo['receive_id']));
                //奖励金币成功
                if (TRUE === $updateStatus) {
                    $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                    $this->db->exec($sql, ($secondDoubleStatus || $doubleStatus) ? 1 : 0, $historyInfo['receive_id']);
//                    $walkReward->receiveSuccess($this->inputData['id']);
                    $goldInfo = $this->model->user2->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'], 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
            default :
                //为了领取累计奖励移除receive_data=$today验证 
                $sql = 'SELECT receive_id, receive_status, receive_gold, end_time
                        FROM t_gold2receive
                        WHERE receive_id =:receive_id
                        AND user_id = :user_id
                        AND receive_gold = :receive_gold
                        AND receive_type = :receive_type';
                $historyInfo = $this->db->getRow($sql, array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'user_id' => $this->userId,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? ''
                ));
                if (!$historyInfo) {
                    return new ApiReturn('', 402, '无效领取');
                }
                if ($historyInfo['receive_status']) {
                    return new ApiReturn('', 403, '重复领取');
                }
                if ($historyInfo['end_time'] && strtotime($historyInfo['end_time']) > time()) {
                    return new ApiReturn('', 405, '领取时间未到');
                }
                $doubleStatus = $this->inputData['isDouble'] ?? 0;
                $updateStatus = $this->model->user2->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $historyInfo['receive_gold'] * ($doubleStatus + 1),
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyInfo['receive_id']));
                //奖励金币成功
                if (TRUE === $updateStatus) {
                    $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                    $this->db->exec($sql, $doubleStatus, $historyInfo['receive_id']);
                    
                    if (!in_array($this->inputData['type'], array('drink', 'lottery_count'))) {
                        $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                        $activityCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                        if (!$activityInfo['activity_max'] || $activityCount < $activityInfo['activity_max']) {
                            $endDate = date('Y-m-d H:i:s', strtotime('+' . $activityInfo['activity_duration'] . 'minute'));
                            $sql = 'SELECT * FROM t_award_config WHERE config_type = ? AND counter_min = ?';
                            $configInfo = $this->db->getRow($sql, $this->inputData['type'], $activityCount + 1);
                            if ($configInfo) {
                                $gold = rand($configInfo['award_min'], $configInfo['award_max']);
                            } else {
                                $gold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                            }
                            $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, end_time = ?, receive_gold = ?';
                            $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], $endDate, $gold);
                        }
                    }
                    
                    $goldInfo = $this->model->user2->getGold($this->userId);
                    return new ApiReturn(array('awardGold' => $historyInfo['receive_gold'] * ($doubleStatus + 1), 'currentGold' => $goldInfo['currentGold']));
                }
                return $updateStatus;
        }
    }
    
    /**
     * 申请提现接口
     * @return \ApiReturn
     */
    public function requestWithdrawalAction () {
        if (isset($this->inputData['amount']) && $this->inputData['amount']) {
            $withdrawalAmount = $this->inputData['amount'];
            $withdrawalGold = $this->inputData['amount'] * $this->withdrawalRate;
            //获取当前用户可用金币
            $sql = 'SELECT SUM(change_gold) FROM t_gold WHERE user_id = ?';
            $totalGold = $this->db->getOne($sql, $this->userId);
            $sql = 'SELECT SUM(withdraw_gold) FROM t_withdraw WHERE user_id = ? AND withdraw_status = "pending"';
            $bolckedGold = $this->db->getOne($sql, $this->userId);
            $currentGold = $totalGold - $bolckedGold;
            
            if ($withdrawalGold > $currentGold) {
                return new ApiReturn('', 502, '提现所需金币不足');
            }
            //是否绑定支付宝
            $sql = 'SELECT alipay_account, alipay_name FROM t_user WHERE user_id = ?';
            $alipayInfo = $this->db->getRow($sql, $this->userId);
            if (isset($alipayInfo['alipay_account']) && $alipayInfo['alipay_account'] && isset($alipayInfo['alipay_name']) && $alipayInfo['alipay_name']) {
                //1元提现只能一次 to do
                if (1 == $withdrawalAmount) {
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND withdraw_amount = 1 AND (withdraw_status = "pending" OR withdraw_status = "success")';
                    if ($this->db->getOne($sql, $this->userId)) {
                        return new ApiReturn('', 503, '1元提现只支持一次');
                    }
                }
                $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, 
                        withdraw_amount = :withdraw_amount, 
                        withdraw_gold = :withdraw_gold, 
                        withdraw_status = "pending", 
                        alipay_account = :alipay_account, 
                        alipay_name = :alipay_name';
                $this->db->exec($sql, array('user_id' => $this->userId,
                    'withdraw_amount' => $withdrawalAmount,
                    'withdraw_gold' => $withdrawalGold, 
                    'alipay_account' => $alipayInfo['alipay_account'],
                    'alipay_name' => $alipayInfo['alipay_name']));
                return new ApiReturn('');
            } else {
                return new ApiReturn('', 504, '未绑定支付宝账号');
            }
        } else {
            return new ApiReturn('', 501, '缺少提现金额');
        }
    }
    
    /**
     * 金币明细列表
     * @return \ApiReturn
     */
    public function goldDetailAction () {
        $sql = 'SELECT gold_source source,change_gold value, change_type type, create_time gTime FROM t_gold WHERE user_id = ? AND create_time >= ? ORDER BY gold_id DESC';
        $goldDetail = $this->db->getAll($sql, $this->userId, date('Y-m-d 00:00:00', strtotime('-3 days')));
        $sql = 'SELECT activity_type, activity_name FROM t_activity ORDER BY activity_id DESC';
        $activeTypeList = $this->db->getPairs($sql);
        array_walk($goldDetail, function (&$v) use($activeTypeList) {
            switch ($v['type']) {
                case 'in':
                    $v['gSource'] = $activeTypeList[$v['source']] ?? $v['source'];
                    break;
                case 'out':
                    $v['gSource'] = 'withdraw' == $v['source'] ? '提现' : $v['source'];
                    $v['value'] = 0 - $v['value'];
                    break;
            }
            if ('system' == $v['source']) {
                $v['gSource'] = '官方操作';
            }
            $v['gTime'] = strtotime($v['gTime']) * 1000;
        });
        return new ApiReturn($goldDetail);    
    }
    
    /**
     * 提现明细列表
     * @return \ApiReturn
     */
    public function withdrawDetailAction () {
        $statusArray = array('pending' => '审核中', 'success' => '审核成功', 'failure' => '审核失败');
        $sql = "SELECT withdraw_amount amount, withdraw_status status, create_time wTime  FROM t_withdraw WHERE user_id = ? ORDER BY withdraw_id DESC";
        $withdrawDetail = $this->db->getAll($sql, $this->userId);
        array_walk($withdrawDetail, function (&$v) use ($statusArray) {
            $v['status'] = $statusArray[$v['status']];
            $v['wTime'] = strtotime($v['wTime']) * 1000;
        });
        return new ApiReturn($withdrawDetail);    
    }
}