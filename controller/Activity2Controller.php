<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
Class Activity2Controller extends AbstractController {
    protected $userId;
    
    /**
     * 验证用户token 设置用户id
     * @return \apiReturn
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
     * 获取用户邀请信息
     * @return \ApiReturn
     */
    public function getInvitedAction() {
        $return = array();
        $sql = 'SELECT * FROM t_award_config WHERE config_type = ? ORDER BY counter_min ASC';
        $invitedList = $this->db->getAll($sql, 'invited_count');
        $sql = 'SELECT COUNT(*) FROM t_user_invited WHERE user_id = ?';
        $invitedCount = $this->db->getOne($sql, $this->userId);
        $invitedArr = array();
        foreach ($invitedList as $invitedInfo) {
            $receiveInfo = array();
            if ($invitedInfo['counter_min'] <=  $invitedCount) {
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_type = "invited_count" AND receive_walk = ?';
                $invitedDetail = $this->db->getRow($sql, $this->userId, $invitedInfo['counter_min']);
                if ($invitedDetail) {
                    $receiveInfo = array(
                        'id' => $invitedDetail['receive_id'],
                        'num' => $invitedDetail['receive_gold'],
                        'type' => 'invited_count',
                        'isReceived' => $invitedDetail['receive_status']);
                } else {
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?,
                            receive_gold = ?,
                            receive_walk = ?,
                            receive_type = "invited_count"';
                    $this->db->exec($sql, $this->userId, $invitedInfo['award_min'], $invitedInfo['counter_min']);
                    $receiveInfo = array(
                        'id' => $this->db->lastInsertId(),
                        'num' => $invitedInfo['award_min'],
                        'type' => 'invited_count',
                        'isReceived' => 0);
                }
            }
            $receiveInfo = array_merge($receiveInfo, array('count' => $invitedInfo['counter_min'], 'award' => $invitedInfo['award_min']));
            $invitedArr[] = $receiveInfo;
        }
        $return['code'] = $this->model->user2->userInfo($this->userId, 'invited_code');
        $return['invitedList'] = $invitedArr;
        return new ApiReturn($return);
    }
    
    /**
     * 获取喝水任务
     * @return \ApiReturn
     */
    public function getDrinkAction () {
        $return = array();
        $sql = 'SELECT * FROM t_award_config WHERE config_type = ? ORDER BY counter_min DESC';
        $drinkList = $this->db->getAll($sql, 'drink');
        $nowTime = time();
        $todayDate = date('Y-m-d');
        $isCurrent = 0;
        
        foreach ($drinkList as $drinkInfo) {
            $tempArr = array();
            if ($nowTime > strtotime(date('Y-m-d ' . $drinkInfo['counter_min'] . ':00:00'))) {
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_type = "drink" AND receive_walk = ? AND receive_date = ?';
                $drinkDetail = $this->db->getRow($sql, $this->userId, $drinkInfo['counter_min'], $todayDate);
                if ($drinkDetail) {
                    $tempArr = array(
                        'id' => $drinkDetail['receive_id'],
                        'num' => $drinkDetail['receive_gold'],
                        'type' => 'drink',
                        'isReceived' => $drinkDetail['receive_status']);
                } else {
                    $sql = 'INSERT INTO t_gold2receive SET user_id = ?,
                            receive_gold = ?,
                            receive_walk = ?,
                            receive_type = "drink",
                            receive_date = ?';
                    $this->db->exec($sql, $this->userId, $drinkInfo['award_min'], $drinkInfo['counter_min'], $todayDate);
                    $tempArr = array(
                        'id' => $this->db->lastInsertId(),
                        'num' => $drinkInfo['award_min'],
                        'type' => 'drink',
                        'isReceived' => 0);
                }
                $tempArr['isCurrent'] = 0;
                if (!$isCurrent) {
                    $tempArr['isCurrent'] = 1;
                    $isCurrent = 1;
                }
            }
            $tempArr = array_merge($tempArr, array('date' => $drinkInfo['counter_min'], 'award' => $drinkInfo['award_min']));
            $return[] = $tempArr;
        }
        return new ApiReturn($return);
    }
}

