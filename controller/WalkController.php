<?php 

Class WalkController extends AbstractController {
    
    public function awardAction () {
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, 'miss step count');
        }
//        接口更新步数比已记录步数少 to do
        $walkReward = new WalkCounter($userId, $this->inputData['stepCount']);
        
        $data = $walkReward->unreceivedList();
        return new ApiReturn($data);
    }
    
    public function getAwardAction () {
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($userId, $this->inputData['stepCount'] ?? 0);
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
                            'user_id' => $userId,
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
                $sql = 'SELECT COUNT(*) 
                        FROM t_gold
                        WHERE gold_source = ?
                        AND change_date = ?';
                $isSignToday = $this->db->getOne($sql, "sign", $today);
                if ($isSignToday) {
                    return new ApiReturn('', 404, '今日已签到');
                }
                $isSignLastDay = $this->db->getOne($sql, "sign", date('Y-m-d', strtotime("-1 day")));
                
                $sql = 'SELECT check_in_days FROM t_user WHERE user_id = ?';
                $checkInDays = $this->db->getOne($sql, $userId);
                
                $updateCheckInDays = $isSignLastDay ? ($checkInDays + 1) : 1;
                $sql = 'UPDATE t_user SET check_in_days = ? WHERE user_id = ?';
                $this->db->exec($sql, $updateCheckInDays, $userId);
                
                //获取奖励金币范围
                $sql = 'SELECT award_min, award_max FROM t_award_config WHERE config_type = :type AND counter_min <= :counter AND counter_max >= :counter';
                $awardRow = $this->db->getRow($sql, array('type' => 'sign', 'counter' => ($updateCheckInDays % 7) ?? 7));
                
                //生成奖励金币
                $signGold = rand($awardRow['award_min'], $awardRow['award_max']);
                
                //如果领取超过1000,签到情况怎么做？ to do
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $userId,
                        'gold' => $signGold,
                        'source' => $this->inputData['type'],
                        'type' => 'in'));
                //奖励金币成功
                if (200 == $updateStatus->code) {
//                    $walkReward->receiveSuccess($this->inputData['id']);
                    return new ApiReturn(array('awardGold' => $signGold));
                }
                return $updateStatus;
                break;
            case 'limit':
                $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
                $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
                
                $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ? ORDER BY history_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $userId, $today, $this->inputData['type']);
                
                if ($historyInfo) {
                    //非第一次领取
                    if (1 == $historyInfo['history_status']) {
                        return new ApiReturn('', 402, '无效领取');
                    } else {
                        //领取时间未到
                        if ($activityInfo['activity_duration']) {
                            if (!$historyInfo['end_date'] || strtotim($historyInfo['end_date']) > time()) {
                                return new ApiReturn('', 402, '无效领取');
                            }
                        }
                    }
                }
                if (!$historyInfo) {
                    $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, history_type = ?';
                    $this->db->exec($sql, $userId, $today, $this->inputData['type']);
                }
                $activityAwardGold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                $updateStatus = $this->model->user->updateGold(array(
                        'user_id' => $userId,
                        'gold' => $activityAwardGold,
                        'source' => $this->inputData['type'],
                        'type' => 'in'));
                //奖励金币成功
                if (200 == $updateStatus->code) {
                    $sql = 'UPDATE t_activity_history SET history_status = 1 WHERE history_id = ?';
                    $this->db->exec($sql, $historyInfo['history_id']);
                    
                    $sql = 'SELECT COUNT(*) FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_type = ?';
                    $activityCount = $this->db->getOne($sql, $userId, $today, $this->inputData['type']);
                    if ($activityCount < $activityInfo['activity_max']) {
                        $endDate = date('Y-m-d', strtotime('+' . $activityInfo['activity_duration'] . 'minute'));
                        $sql = 'INSERT INTO t_activity_history SET user_id = ?, history_date = ?, end_date = ?';
                        $this->db->exec($sql, $userId, $today, $endDate);
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
}