<?php 

Class WalkController extends AbstractController {
    
    public function awardAction () {
        $token = $_SERVER['HTTP_ACCESSTOKEN'];
        $userId = $this->model->user->verifyToken($token);
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
        $token = $_SERVER['HTTP_ACCESSTOKEN'];
        $userId = $this->model->user->verifyToken($token);
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        if (!isset($this->inputData['type'])) {
            return new ApiReturn('', 402, '无效领取');
        }
        $today = date('Y-m-d');
        switch ($this->inputData['type']) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($userId);
                $receiveInfo = $walkReward->verifyReceive(array(
                   'receive_id' => $this->inputData['id'] ?? 0,
                   'receive_gold' => $this->inputData['num'] ?? 0,
                   'receive_type' => $this->inputData['type'] ?? '',
                ));
                if ($receiveInfo) {
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
                    return new ApiReturn('', 403, '今日已签到');
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
                    return new ApiReturn('');
                }
                return $updateStatus;
                break;
            case 'limit':
                $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
                $activityInfo = $this->db->getRow($sql, $this->inputData['type']);
                
                $sql = 'SELECT * FROM t_activity_history WHERE user_id = ? AND history_date = ? AND history_status = 0 ORDER BY history_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $userId, $today);
                if ($historyInfo) {
                    if ($activityInfo['activity_duration']) {
                        if (!$historyInfo['end_date'] || strtotim($historyInfo['end_date']) > time()) {
                            return new ApiReturn('', 402, '无效领取');
                        }
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
                        return new ApiReturn('');
                    }
                    return $updateStatus;
//                    if ($activityInfo['activity_max']) {
//
//                    }
//                    $sql = 'SELECT COUNT(*) FROM t_activity_history WHERE history_type = ? AND ';
                } else {
                    return new ApiReturn('', 402, '无效领取');
                }
                break;
            default :
                return new ApiReturn('', 402, '无效领取');
        }
        
    }
}