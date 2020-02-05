<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Task extends AbstractController {
    
    public function getTask ($type, $userId) {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $activityInfo = $this->db->getRow($sql, $type);
        if (!$activityInfo) {
            return new ApiReturn('', 402, '无效领取');
        }
        $today = date('Y-m-d');
        switch ($type) {
            case 'walk':
            case 'walk_stage':
                $walkReward = new WalkCounter($userId);
                $taskInfo = $walkReward->getReturnInfo($type);
                break;
            case 'newer':
                return new ApiReturn('', 501, '无效获取');
            case 'wechat':
                $unionId = $this->model->user->userInfo($userId, 'unionid');
                $sql = 'SELECT activity_award_min FROM t_activity WHERE activity_type = "wechat"';
                $taskInfo = array('isBuild' => $unionId ? 1 : 0, 'award' => $this->db->getOne($sql));
                break;
            case 'sign':
                $sql = 'SELECT check_in_days FROM t_user WHERE user_id = ?';
                $checkInDays = $this->db->getOne($sql, $userId);
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                $todayInfo = $this->db->getRow($sql, $userId, $today, $type);
                if(!$todayInfo) {
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? AND receive_status = 1 ORDER BY receive_id DESC LIMIT 1';
                    $isSignLastDay = $this->db->getOne($sql, $userId, date('Y-m-d', strtotime("-1 day")), $type);
                    if (!$isSignLastDay) {
                        $checkInDays = 0;
                        $sql = 'UPDATE t_user SET check_in_days = ? WHERE user_id = ?';
                        $this->db->exec($sql, 0, $userId);
                    }
                    //获取奖励金币范围
                    $sql = 'SELECT award_min FROM t_award_config WHERE config_type = :type AND counter_min = :counter';
                    $awardRow = $this->db->getRow($sql, array('type' => 'sign', 'counter' => (($checkInDays + 1) % 7) ?? 7));
                    
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, receive_gold = ?';
                    $this->db->exec($sql, $userId, $today, $type, $awardRow['award_min']);
                }
                $fromDate = $today;
                $checkInReturn = array('checkInDays' => $checkInDays, 'checkInInfo' => array());
                if ($checkInDays) {
                    $checkInDays -= ($todayInfo['receive_status'] ?? 0);
                    $fromDate = date('Y-m-d', strtotime('-' . $checkInDays . 'days'));
                }
                $sql = 'SELECT receive_id id , receive_gold num, receive_status isReceive, is_double isDouble, IF(receive_date="' . $today . '", 1, 0) isToday FROM t_gold2receive WHERE user_id = ? AND receive_date >= ? AND receive_type = ? ORDER BY receive_id';
                $checkInInfo = $this->db->getAll($sql, $userId, $fromDate, $type);
                
                $i = 0;
                $sql = 'SELECT counter_min, award_min FROM t_award_config WHERE config_type = "sign" ORDER BY config_id ASC';
                $checkInConfigList = $this->db->getAll($sql);
                foreach ($checkInConfigList as $config) {
                    $checkInReturn['checkInInfo'][] = array_merge(array('day' => $config['counter_min'], 'award' => $config['award_min']), $checkInInfo[$i] ?? array());
                    $i++;
                }
                $taskInfo = $checkInReturn;
                break;
            default :
                $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ?';
                $todayCount = $this->db->getOne($sql, $userId, $today, $type);
                if (!$todayCount) {
                    //第一次领取
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? ORDER BY receive_id DESC LIMIT 1';
                    $historyLastdayInfo = $this->db->getRow($sql, $userId, date('Y-m-d', strtotime("-1 day")), $type);
                    if ($historyLastdayInfo && strtotime($historyLastdayInfo['end_time']) > time()) {
                        $endTime = $historyLastdayInfo['end_time'];
                    } else {
                        $endTime = date('Y-m-d H:i:s');
                    }
                    $gold = rand($activityInfo['activity_award_min'], $activityInfo['activity_award_max']);
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_date = ?, receive_type = ?, end_time = ?, receive_gold = ?';
                    $this->db->exec($sql, $userId, $today, $type, date('Y-m-d H:i:s'), $gold);
                }
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? ORDER BY receive_id DESC LIMIT 1';
                $historyInfo = $this->db->getRow($sql, $userId, $today, $type);
                $taskInfo = array();
                $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = ? AND receive_status = 1';
                $receiveCount = $this->db->getOne($sql, $userId, $today, $type);
                $taskInfo = array('receiveCount' => $receiveCount, 
                    'endTime' => strtotime($historyInfo['end_time']) * 1000,
                    'isReceive' => $historyInfo['receive_status'],
                    'id' => $historyInfo['receive_id'],
                    'num' => $historyInfo['receive_gold'],
                    'serverTime' => time() * 1000,
                    'countMax' => $activityInfo['activity_max']);
                if ('tab' == $type) {
                    //to do移动到数据库中
                    $taskInfo['probability'] = $activityInfo['activity_remark'];
                }
        }
        return $taskInfo;
    }
    
    public function doTask ($type, $userId) {
        
    }
    
    
}