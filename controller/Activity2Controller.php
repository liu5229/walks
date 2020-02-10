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
        $sql = 'SELECT c.counter_min, c.award_min, g.gold_id 
                FROM t_award_config c
                LEFT JOIN t_gold g ON g.relation_id = c.config_id AND g.gold_source = c.config_type AND g.user_id = ?
                WHERE c.config_type = ? 
                ORDER BY c.counter_min ASC';
        $invitedList = $this->db->getAll($sql, $this->userId, 'invited_count');
        
        $invitedArr = array();
        foreach ($invitedList as $invitedInfo) {
            $invitedArr[] = array('count' => $invitedInfo['counter_min'], 'award' => $invitedInfo['award_min'], 'isGet' => $invitedInfo['gold_id'] ? 1 : 0);
        }
        $return['code'] = $this->model->user2->userInfo($this->userId, 'invited_code');
        $return['invitedList'] = $invitedArr;
        
        //invited
        $sql = 'SELECT SUM(change_gold) FROM t_gold WHERE user_id = ? AND gold_source IN ("do_invite", "invited_count")';
        $return['invitedTotal'] = $this->db->getOne($sql, $this->userId) ?: 0;
        
        $sql = 'SELECT COUNT(id) FROM t_user_invited WHERE user_id = ?';
        $return['invitedCount'] = $this->db->getOne($sql, $this->userId);
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
            $drinkTime = strtotime(date('Y-m-d ' . $drinkInfo['counter_min'] . ':00:00'));
            if ($nowTime > $drinkTime) {
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
            $tempArr = array_merge($tempArr, array('date' => $drinkTime * 1000, 'award' => $drinkInfo['award_min']));
            $return[] = $tempArr;
        }
        return new ApiReturn(array_reverse($return));
    }
    
    public function getLotteryAction () {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $lotteryActInfo = $this->db->getRow($sql, 'lottery');
        $return = array();
        
        $todayDate = date('Y-m-d');
        //当前次数 剩余次数  抽奖金币信息
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type
                FROM t_gold2receive 
                WHERE receive_date = ? 
                AND user_id = ? 
                AND receive_type = ? 
                ORDER BY receive_status ASC, receive_id DESC';
        $lotteryReceiveInfo = $this->db->getAll($sql, $todayDate, $this->userId, 'lottery');
        if ($lotteryReceiveInfo) {
            $return['currentAward'] = current($lotteryReceiveInfo);
            $return['currentCount'] = count($lotteryReceiveInfo);
        } else {
            $award = rand($lotteryActInfo['activity_award_min'], $lotteryActInfo['activity_award_max']);
            $sql = 'INSERT INTO t_gold2receive SET
                    receive_date = ?,
                    user_id = ?,
                    receive_type = "lottery",
                    receive_gold = ?';
            $this->db->exec($sql, $todayDate, $this->userId, $award);
            $return['currentAward'] = array('id' => $this->db->lastInsertId(), 'num' => $award, 'type' => 'lottery');
            $return['currentCount'] = 0;
        }
        $return['restCount'] = $lotteryActInfo['activity_max'] - $return['currentCount'];
        
        //累计抽奖列表
        $sql = 'SELECT c.counter_min count, c.award_min award, g.receive_id id, g.receive_gold num, g.receive_type type, g.receive_status isReceive
                FROM t_award_config c
                LEFT JOIN t_gold2receive g ON g.receive_walk = c.config_id AND g.receive_type = c.config_type AND g.user_id = ? AND receive_date = ?
                WHERE c.config_type = ?
                ORDER BY c.counter_min ASC';
        $lotteryCountList = $this->db->getAll($sql, $this->userId, $todayDate, 'lottery_count');
        $return['totalAward'] = $lotteryCountList;
        
        return new ApiReturn($return);
    }
    
    public function lotteryAwardAction () {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $lotteryActInfo = $this->db->getRow($sql, 'lottery');
        $todayDate = date('Y-m-d');
        
        $sql = 'SELECT COUNT(receive_id)
                FROM t_gold2receive 
                WHERE receive_date = ? 
                AND user_id = ? 
                AND receive_type = ? 
                ORDER BY receive_status ASC, receive_id DESC';
        $lotteryReceiveInfo = $this->db->getOne($sql, $todayDate, $this->userId, 'lottery');
        if ($lotteryActInfo['activity_max'] == $lotteryReceiveInfo) {
            return new ApiReturn('', 501, '今日抽奖次数已用完');
        }
        
        $sql = 'SELECT receive_id, receive_status, receive_gold, receive_type
                FROM t_gold2receive
                WHERE receive_id =:receive_id
                AND user_id = :user_id
                AND receive_gold = :receive_gold
                AND receive_type = :receive_type
                AND receive_date = :receive_date';
        $awardInfo = $this->db->getRow($sql, array(
           'receive_id' => $this->inputData['id'] ?? 0,
           'user_id' => $this->userId,
           'receive_gold' => $this->inputData['num'] ?? 0,
           'receive_type' => $this->inputData['type'] ?? '',
           'receive_date' => $today,
        ));
        
        if ($awardInfo) {
            //领取金币
            if ($awardInfo['receive_status']) {
                return new ApiReturn('', 502, '重复领取');
            }
            $updateStatus = $this->model->user2->updateGold(array(
                'user_id' => $this->userId,
                'gold' => $awardInfo['receive_gold'],
                'source' => $awardInfo['receive_type'],
                'type' => 'in',
                'relation_id' => $awardInfo['receive_id']));
            if (TRUE === $updateStatus) {
                $sql = 'UPDATE t_gold2receive SET receive_status = 1 WHERE receive_id = ?';
                $this->db->exec($sql, $awardInfo['receive_id']);
            } else {
                return $updateStatus;
            }
        } else {
            //填写0金币的记录 
            $sql = 'INSERT INTO t_gold2receive SET
                    receive_date = ?,
                    user_id = ?,
                    receive_type = "lottery",
                    receive_gold = 0,
                    receive_status = 1';
            $this->db->exec($sql, $todayDate, $this->userId);
        }
        
        $return['currentCount'] = $lotteryReceiveInfo + 1;
        $return['restCount'] = $lotteryActInfo['activity_max'] - $return['currentCount'];
        if ($return['restCount']) {
            if ($awardInfo) {
                //生成下一个
                $award = rand($lotteryActInfo['activity_award_min'], $lotteryActInfo['activity_award_max']);
                $sql = 'INSERT INTO t_gold2receive SET
                        receive_date = ?,
                        user_id = ?,
                        receive_type = "lottery",
                        receive_gold = ?';
                $this->db->exec($sql, $todayDate, $this->userId, $award);
                $return['currentAward'] = array('id' => $this->db->lastInsertId(), 'num' => $award, 'type' => 'lottery');
            } else {
                //返回最近未领取的
                $sql = 'SELECT receive_id id, receive_gold num, receive_type type
                        FROM t_gold2receive 
                        WHERE receive_date = ? 
                        AND user_id = ? 
                        AND receive_type = ? 
                        ORDER BY receive_status ASC, receive_id DESC';
                $return['currentAward'] = $this->db->getRow($sql, $todayDate, $this->userId, 'lottery');;
            }
        } else {
            $return['currentAward'] = array();
        }
        
        $sql = 'SELECT config_id, award_min FROM t_award_config WHERE config_type = ? AND counter_min = ?';
        $lotteryCountAwardInfo = $db->getRow($sql, 'lottery_count', $return['currentCount']);
        if ($lotteryCountAwardInfo) {
            $sql = 'INSERT INTO t_gold2receive SET
                    receive_date = ?,
                    user_id = ?,
                    receive_type = "lottery_count",
                    receive_gold = ?,
                    receive_walk = ?';
            $this->db->exec($sql, $todayDate, $this->userId, $lotteryCountAwardInfo['award_min'], $lotteryCountAwardInfo['config_id']);
        }
        
        //累计抽奖列表
        $sql = 'SELECT c.counter_min count, c.award_min award, g.receive_id id, g.receive_gold num, g.receive_type type, g.receive_status isReceive
                FROM t_award_config c
                LEFT JOIN t_gold2receive g ON g.receive_walk = c.config_id AND g.receive_type = c.config_type AND g.user_id = ? AND receive_date = ?
                WHERE c.config_type = ?
                ORDER BY c.counter_min ASC';
        $lotteryCountList = $this->db->getAll($sql, $this->userId, 'lottery_count');
        $return['totalAward'] = $lotteryCountList;
        
        return new ApiReturn($return);
    }
}

