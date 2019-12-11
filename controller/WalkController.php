<?php 

Class WalkController extends AbstractController {
    //提现汇率
    protected $withdrawalRate = 10000;
    protected $userId;
    
    public function init() {
        parent::init();
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }
    
    public function updateWalkAction () {
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, 'miss step count');
        }
        $walkReward = new WalkCounter($this->userId, $this->inputData['stepCount']);
        return new ApiReturn(array('stepCount' => $walkReward->getStepCount()));
    }

    public function taskAction() {
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 406, '无效获取');
        }
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
        if (!$activityInfo) {
            return new ApiReturn('', 406, '无效获取');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($this->userId);
                return new ApiReturn($walkReward->getReturnInfo($this->inputData['type']));
                break;
            case 'sign':
                
                break;
            default :
                $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? ORDER BY history_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $this->userId, $today, $this->inputData['type']);
                $return = array();
                if ($historyInfo) {
                    //非第一次领取
                    $sql = 'SELECT COUNT(*) FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? AND history_status = 1';
                    $receiveCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                    $return = array('receiveCount' => $receiveCount, 'endTime' => strtotime($historyInfo['end_date']) * 1000);
                } else {
                    $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? ORDER BY history_id DESC LIMIT 1';
                    $historyLastdayInfo = $this->db->getRow($sql, $this->userId, date('Y-m-d', strtotime("-1 day")), $this->inputData['type']);
                    
                    if ($historyLastdayInfo && strtotim($historyLastdayInfo['end_date']) > time()) {
                        $endTime = $historyLastdayInfo['end_date'];
                    } else {
                        $endTime = date('Y-m-d H:i:s');
                    }
                    $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, history_type = ?, end_date = ?';
                    $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], date('Y-m-d H:i:s'));
                    $return = array('receiveCount' => 0, 'endTime' => strtotime($endTime) * 1000);
                }
                $return['serverTime'] = time() * 1000;
                $return['countMax'] = $activityInfo['activity_max'];
                return new ApiReturn($return);
        }
    }

    public function awardAction () {
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, 'miss step count');
        }
//        接口更新步数比已记录步数少 to do
        $walkReward = new WalkCounter($this->userId, $this->inputData['stepCount']);
        
        $data = $walkReward->unreceivedList();
        return new ApiReturn($data);
    }
    
    public function getAwardAction () {
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($this->userId, $this->inputData['stepCount'] ?? 0);
                $receiveInfo = $walkReward->verifyReceive(array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                ));
                if ($receiveInfo) {
                    if (1 == $receiveInfo['receive_status']) {
                        return new ApiReturn('', 403, '重复领取');
                    } else {
                        $updateStatus = $this->model->user->updateGold(array(
                            'user_id' => $this->userId,
                            'gold' => $this->inputData['num'],
                            'source' => $this->inputData['type'],
                            'type' => 'in',
                            'relation_id' => $this->inputData['id']));
                        if (200 == $updateStatus->code) {
                            $walkReward->receiveSuccess($this->inputData['id']);
                            return new ApiReturn($walkReward->getReturnInfo($this->inputData['type']));
                        }
                        return $updateStatus;
                    }
                } else {
                    return new ApiReturn('', 402, '无效领取');
                }
                break;
            case 'sign':
                $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? ORDER BY history_id DESC LIMIT 1';
                $isSignToday = $this->db->getRow($sql, $this->userId, $today, $this->inputData['type']);
                if ($isSignToday) {
                    return new ApiReturn('', 404, '今日已签到');
                }
                $isSignLastDay = $this->db->getOne($sql, $this->userId, date('Y-m-d', strtotime("-1 day")), $this->inputData['type']);
                
                $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, history_type = ?, end_date = ?';
                $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], date('Y-m-d H:i:s'));
                $historyId = $this->db->lastInsertId();
                
                $sql = 'SELECT check_in_days FROM t_user WHERE user_id = ?';
                $checkInDays = $this->db->getOne($sql, $this->userId);
                
                $updateCheckInDays = $isSignLastDay ? ($checkInDays + 1) : 1;
                $sql = 'UPDATE t_user SET check_in_days = ? WHERE user_id = ?';
                $this->db->exec($sql, $updateCheckInDays, $this->userId);
                
                //获取奖励金币范围
                $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = :type AND counter_min <= :counter AND counter_max >= :counter';
                $awardRow = $this->db->getRow($sql, array('type' => 'sign', 'counter' => ($updateCheckInDays % 7) ?? 7));
                
                //生成奖励金币
                $signGold = rand($awardRow['award_min'], $awardRow['award_max']);
                
                //如果领取超过1000,签到情况怎么做？ to do
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $signGold,
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyId));
                //奖励金币成功
                if (200 == $updateStatus->code) {
                    $sql = 'UPDATE t_activity_history SET history_status = 1 WHERE history_id = ?';
                    $this->db->exec($sql, $historyId);
//                    $walkReward->receiveSuccess($this->inputData['id']);
                    return new ApiReturn(array('awardGold' => $signGold));
                }
                return $updateStatus;
                break;
            case 'limit':
                $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
                $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
                
                $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? ORDER BY history_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $this->userId, $today, $this->inputData['type']);
                
                if ($historyInfo) {
                    //非第一次领取
                    if (1 == $historyInfo['history_status']) {
                        return new ApiReturn('', 403, '重复领取');
                    } else {
                        //领取时间未到
                        if ($activityInfo['activity_duration']) {
                            if (!$historyInfo['end_date'] || strtotime($historyInfo['end_date']) > time()) {
                                return new ApiReturn('', 405, '领取时间未到');
                            }
                        }
                    }
                }
                if (!$historyInfo) {
                    $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, history_type = ?, end_date = ?';
                    $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], date('Y-m-d H:i:s'));
                    $historyId = $this->db->lastInsertId();
                } else {
                    $historyId = $historyInfo['history_id'];
                }
                
                $activityAwardGold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $this->userId,
                        'gold' => $activityAwardGold,
                        'source' => $this->inputData['type'],
                        'type' => 'in',
                        'relation_id' => $historyId));
                //奖励金币成功
                if (200 == $updateStatus->code) {
                    $sql = 'UPDATE t_activity_history SET history_status = 1 WHERE history_id = ?';
                    $this->db->exec($sql, $historyId);
                    
                    $sql = 'SELECT COUNT(*) FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ?';
                    $activityCount = $this->db->getOne($sql, $this->userId, $today, $this->inputData['type']);
                    if ($activityCount < $activityInfo['activity_max']) {
                        $endDate = date('Y-m-d H:i:s', strtotime('+' . $activityInfo['activity_duration'] . 'minute'));
                        $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, history_type = ?, end_date = ?';
                        $this->db->exec($sql, $this->userId, $today, $this->inputData['type'], $endDate);
                    }
                    return new ApiReturn(array('awardGold' => $activityAwardGold));
                }
                return $updateStatus;
//                    if ($activityInfo['activity_max']) {
//
//                    }
//                    $sql = 'SELECT COUNT(*) FROM t_activity_history WHERE history_type = ? AND ';
                break;
            default :
                return new ApiReturn('', 402, '无效领取');
        }
        
    }
    
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
                    $sql = 'SELECT COUNT(*) FROM t_withdraw WHERE user_id = ? AND (withdraw_status = "pending" OR withdraw_status = "success")';
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
}