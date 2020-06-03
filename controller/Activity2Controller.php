<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
Class Activity2Controller extends AbstractController {
    protected $userId;
    protected $clockinConfig = array(
        1 => array('min' => ' 06:30:00', 'max' => ' 08:00:00'),
        2 => array('min' => ' 06:30:00', 'max' => ' 08:00:00'),
        3 => array('min' => ' 07:30:00', 'max' => ' 09:00:00'),
        4 => array('min' => ' 09:00:00', 'max' => ' 11:00:00'),
        5 => array('min' => ' 12:30:00', 'max' => ' 14:00:00'),
        6 => array('min' => ' 13:30:00', 'max' => ' 15:00:00'),
        7 => array('min' => ' 15:00:00', 'max' => ' 16:00:00'),
        8 => array('min' => ' 15:00:00', 'max' => ' 17:30:00'),
        9 => array('min' => ' 18:00:00', 'max' => ' 20:00:00'),
        10 => array('min' => ' 21:00:00', 'max' => ' 22:30:00'),
    );
    protected $scratchImgList = array(1 => 'https://oss.stepcounter.cn/img/scratch_01.png', 2 => 'https://oss.stepcounter.cn/img/scratch_02.png', 3 => 'https://oss.stepcounter.cn/img/scratch_03.png', 4 => 'https://oss.stepcounter.cn/img/scratch_04.png', 5 => 'https://oss.stepcounter.cn/img/scratch_05.png', 6 => 'https://oss.stepcounter.cn/img/scratch_06.png', 7 => 'https://oss.stepcounter.cn/img/scratch_07.png', 8 => 'https://oss.stepcounter.cn/img/scratch_08.png', 9 => 'https://oss.stepcounter.cn/img/scratch_09.png', 10 => 'https://oss.stepcounter.cn/img/scratch_10.png');
    protected $scratchClock = array();
    
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
     * 获取用户邀请信息
     * @return \ApiReturn
     */
    public function getInvitedDetailAction() {
        $sql = 'SELECT u.nickname, g.change_gold gold, unix_timestamp(i.create_time) * 1000 cTime
                FROM t_user_invited i
                LEFT JOIN t_user u ON i.invited_id = u.user_id
                LEFT JOIN t_gold g ON g.gold_source = "do_invite" AND g.relation_id = i.id
                WHERE i.user_id = ?
                ORDER BY i.id DESC';
        $returnList = $this->db->getAll($sql, $this->userId);
        return new ApiReturn($returnList);
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
    
    /**
     * 获取大转盘活动
     * @return \ApiReturn
     */
    public function getLotteryAction () {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $lotteryActInfo = $this->db->getRow($sql, 'lottery');
        $return = array();
        
        $todayDate = date('Y-m-d');
        //当前次数 剩余次数  抽奖金币信息
        $sql = 'SELECT receive_id, receive_gold, receive_type, receive_status
                FROM t_gold2receive
                WHERE receive_date = ? 
                AND user_id = ? 
                AND receive_type = ? 
                ORDER BY receive_status ASC, receive_id DESC';
        $lotteryReceiveInfo = $this->db->getAll($sql, $todayDate, $this->userId, 'lottery');
        if ($lotteryReceiveInfo) {
            $currentAward = current($lotteryReceiveInfo);
            $return['currentAward'] = array('id' => $currentAward['receive_id'], 'num' => $currentAward['receive_gold'], 'type' => $currentAward['receive_type']);
            $return['currentCount'] = count($lotteryReceiveInfo) - ($currentAward['receive_status'] ? 0 : 1);
        } else {
            $award = rand($lotteryActInfo['activity_award_min'], $lotteryActInfo['activity_award_max']);
            $sql = 'INSERT INTO t_gold2receive SET
                    receive_date = ?,
                    user_id = ?,
                    receive_type = "lottery",
                    receive_gold = ?';
            $this->db->exec($sql, $todayDate, $this->userId, $award);
            $return['currentAward'] = array('id' => $this->db->lastInsertId(), 'num' => (string) $award, 'type' => 'lottery');
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
    
    /**
     * 领取大转盘活动
     * @return \ApiReturn
     */
    public function lotteryAwardAction () {
        $sql = 'SELECT * FROM t_activity WHERE activity_type = ?';
        $lotteryActInfo = $this->db->getRow($sql, 'lottery');
        if (!$lotteryActInfo['activity_status']) {
            return new ApiReturn('', 204, '领取失败，请稍后再试');
        }
        
        $todayDate = date('Y-m-d');
        
        $sql = 'SELECT COUNT(receive_id)
                FROM t_gold2receive 
                WHERE receive_date = ? 
                AND user_id = ? 
                AND receive_type = ? 
                AND receive_status = 1
                ORDER BY receive_status ASC, receive_id DESC';
        $lotteryReceiveInfo = $this->db->getOne($sql, $todayDate, $this->userId, 'lottery');
        if ($lotteryActInfo['activity_max'] <= $lotteryReceiveInfo) {
            return new ApiReturn('', 501, '今日抽奖次数已用完，请明天再来');
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
           'receive_date' => $todayDate,
        ));
        
        if ($awardInfo) {
            //领取金币
            if ($awardInfo['receive_status']) {
                return new ApiReturn('', 401, '您已领取过该奖励');
            }
            $doubleStatus = $this->inputData['isDouble'] ?? 0;
            $updateStatus = $this->model->user2->updateGold(array(
                'user_id' => $this->userId,
                'gold' => $awardInfo['receive_gold'] * ($doubleStatus + 1),
                'source' => $awardInfo['receive_type'],
                'type' => 'in',
                'relation_id' => $awardInfo['receive_id']));
            if (TRUE === $updateStatus) {
                $sql = 'UPDATE t_gold2receive SET receive_status = 1, is_double = ? WHERE receive_id = ?';
                $this->db->exec($sql, $doubleStatus, $awardInfo['receive_id']);
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
        
        $currentCount = $lotteryReceiveInfo + 1;
        $restCount = $lotteryActInfo['activity_max'] - $currentCount;
        if ($restCount) {
            if ($awardInfo) {
                //生成下一个
                $award = rand($lotteryActInfo['activity_award_min'], $lotteryActInfo['activity_award_max']);
                $sql = 'INSERT INTO t_gold2receive SET
                        receive_date = ?,
                        user_id = ?,
                        receive_type = "lottery",
                        receive_gold = ?';
                $this->db->exec($sql, $todayDate, $this->userId, $award);
            }
        }
        
        $sql = 'SELECT config_id, award_min FROM t_award_config WHERE config_type = ? AND counter_min = ?';
        $lotteryCountAwardInfo = $this->db->getRow($sql, 'lottery_count', $currentCount);
        if ($lotteryCountAwardInfo) {
            $sql = 'INSERT INTO t_gold2receive SET
                    receive_date = ?,
                    user_id = ?,
                    receive_type = "lottery_count",
                    receive_gold = ?,
                    receive_walk = ?';
            $this->db->exec($sql, $todayDate, $this->userId, $lotteryCountAwardInfo['award_min'], $lotteryCountAwardInfo['config_id']);
        }
        
        $goldInfo = $this->model->user2->getGold($this->userId);
        return new ApiReturn(array('awardGold' => $awardInfo ? ($awardInfo['receive_gold']  * ($doubleStatus + 1)) : 0, 'currentGold' => $goldInfo['currentGold']));
    }
    
    /**
     * 打卡页接口
     * @return \ApiReturn
     */
    public function getClockinAction () {
        $nowTime = time();
        $todayDate = date('Y-m-d');
        $returnList = array();
        $sql = 'SELECT * FROM t_award_config WHERE config_type = ? ORDER BY counter_min ASC';
        $clockinList = $this->db->getAll($sql, 'clockin');
        
        foreach ($clockinList as $clockinInfo) {
            $config = $this->clockinConfig[$clockinInfo['counter_min']];
            $current = 0;
            $tempArr = array();
            if ($nowTime >= strtotime($todayDate . $config['min'])) {
                if ($nowTime <= strtotime($todayDate . $config['max'])) {
                    $current = 1;
                }
                $sql = 'INSERT INTO t_gold2receive (user_id, receive_gold, receive_walk, receive_type, receive_date)
                        SELECT :user_id, :receive_gold, :receive_walk, :receive_type, :receive_date FROM DUAL
                        WHERE NOT EXISTS (SELECT receive_id FROM t_gold2receive WHERE user_id = :user_id AND receive_walk = :receive_walk AND receive_type = :receive_type AND receive_date = :receive_date)';
                $return = $this->db->exec($sql, array(
                    'user_id' => $this->userId,
                    'receive_gold' => $clockinInfo['award_min'],
                    'receive_walk' => $clockinInfo['counter_min'],
                    'receive_type' => 'clockin',
                    'receive_date' => $todayDate));
                if ($return) {
                    $tempArr = array(
                        'id' => $this->db->lastInsertId(),
                        'num' => $clockinInfo['award_min'],
                        'type' => 'clockin',
                        'isReceived' => 0);
                } else {
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_walk = ? AND receive_type = ? AND receive_date = ?';
                    $clockinDetail = $this->db->getRow($sql, $this->userId, $clockinInfo['counter_min'], 'clockin', $todayDate);
                    $tempArr = array(
                        'id' => $clockinDetail['receive_id'],
                        'num' => $clockinDetail['receive_gold'],
                        'type' => 'clockin',
                        'isReceived' => $clockinDetail['receive_status']);
                }
            }
            $returnList['list'][] = array_merge($tempArr, array('award' => $clockinInfo['award_min'], 'isCurrent' => $current));
        }
        
        $sql = 'SELECT COUNT(*) FROM t_gold2receive WHERE user_id = ? AND receive_type = ? AND receive_date = ? AND receive_status = 1';
        $receiveClockinCount = $this->db->getOne($sql, $this->userId, 'clockin', $todayDate);
        
        $sql = 'SELECT * FROM t_award_config WHERE config_type = ? ORDER BY counter_min ASC';
        $clockinTotalList = $this->db->getAll($sql, 'clockin_count');
        foreach ($clockinTotalList as $clockinTotalInfo) {
            $tempArr = array();
            if ($clockinTotalInfo['counter_min'] <= $receiveClockinCount) {
                $sql = 'INSERT INTO t_gold2receive (user_id, receive_gold, receive_walk, receive_type, receive_date)
                        SELECT :user_id, :receive_gold, :receive_walk, :receive_type, :receive_date FROM DUAL
                        WHERE NOT EXISTS (SELECT receive_id FROM t_gold2receive
                        WHERE user_id = :user_id AND receive_walk = :receive_walk AND receive_type = :receive_type AND receive_date = :receive_date)';
                $return = $this->db->exec($sql, array(
                    'user_id' => $this->userId,
                    'receive_gold' => $clockinTotalInfo['award_min'],
                    'receive_walk' => $clockinTotalInfo['counter_min'],
                    'receive_type' => 'clockin_count',
                    'receive_date' => $todayDate));
                if ($return) {
                    $tempArr = array(
                        'id' => $this->db->lastInsertId(),
                        'num' => $clockinTotalInfo['award_min'],
                        'type' => 'clockin_count',
                        'isReceived' => 0);
                } else {
                    $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_walk = ? AND receive_type = ? AND receive_date = ?';
                    $clockinTotalDetail = $this->db->getRow($sql, $this->userId, $clockinTotalInfo['counter_min'], 'clockin_count', $todayDate);
                    $tempArr = array(
                        'id' => $clockinTotalDetail['receive_id'],
                        'num' => $clockinTotalDetail['receive_gold'],
                        'type' => 'clockin_count',
                        'isReceived' => $clockinTotalDetail['receive_status']);
                }
            }
            $returnList['total'][] = array_merge($tempArr, array('count' => $clockinTotalInfo['counter_min']));
        }
        return new ApiReturn($returnList);
    }

    /**
     * 刮刮卡喜报轮播列表接口 todo
     * @return ApiReturn
     */
    public function scratchNewsAction () {
        $returnList = array('恭喜换乐马，成功刮中30000金币', '恭喜换乐马2，成功刮中30000金币', '恭喜换乐马3，成功刮中30000金币', '恭喜换乐马4，成功刮中30000金币');
        return new ApiReturn($returnList);
    }

    /**
     * 刮刮卡列表接口 todo
     * @return ApiReturn
     */
    public function scratchListAction () {
        $config = array(7, 11, 15, 20, 23);
        $nowHours = date('H');
        $todayDate = date('Y-m-d');
        $endTime = '';
        $batch = 0;
        foreach ($config as $k => $hours) {
            if ($nowHours < $hours) {
                if (0 == $k) {
                    $todayDate = date('Y-m-d', strtotime('-1 day'));
                    $batch = 5;
                } else {
                    $batch = $k - 1;
                }
                $endTime = strtotime(date('Y-m-d ' . $hours . ':00:00')) * 1000;
                break;
            }
        }
        if (!$endTime) {
            $batch = 1;
            $endTime = strtotime(date('Y-m-d 7:00:00', strtotime('+1 day'))) * 1000;
        }
        var_dump($batch);

        //查找刮刮卡信息
        $sql = 'SELECT * FROM t_activity_scratch WHERE user_id = ? AND receive_date = ? AND scratch_batch = ? ORDER BY receive_status ASC, id ASC';
        $scratchList = $this->db->getAll($sql, $this->userId, $todayDate, $batch);
        $returnList = array();
        if ($scratchList) {
            $lockList = array_diff(array(6, 7, 8, 9, 10), array_column($scratchList, 'sort'));
            $lockAdd = FALSE;
            foreach ($scratchList as $scratchInfo) {
                if (1 == $scratchInfo['receive_status'] && $lockList && !$lockAdd) {
                    //添加未解锁的刮刮卡 排序位于未打开和 已打开的刮刮卡之间
                    foreach ($lockList as $lockKey) {
                        $returnList[] = array('bgImg' => $this->scratchImgList[$lockKey], 'isLock' => 1, 'isOpen' => 0, 'number' => $lockKey);
                    }
                    $lockAdd = TRUE;
                }
                $returnList[] = array('bgImg' => $this->scratchImgList[$scratchInfo['scratch_num']], 'isLock' => 0, 'isOpen' => $scratchInfo['receive_status'], 'number' => $scratchInfo['scratch_num'], 'id' => $scratchInfo['id'], 'gold' => $scratchInfo['receive_gold'], 'type' => 'scratch', 'content' => json_decode($scratchInfo['scratch_content']));
            }
            if (!$lockAdd) {
                for ($i=5;$i<=10;$i++) {
                    $returnList[] = array('bgImg' => $this->scratchImgList[$i], 'isLock' => 1, 'isOpen' => 0, 'number' => $i);
                }
            }
        } else {
            foreach ($this->scratchImgList as $key => $scratchImg) {
                $content = $this->__scratchContent();
                if ($key > 5) {
                    $returnList[] = array('bgImg' => $scratchImg, 'isLock' => 1, 'isOpen' => 0, 'number' => $key);
                    continue;
                }
                $sql = 'INSERT INTO t_activity_scratch (`user_id`, `receive_gold`, `scratch_num`, `scratch_batch`, `scratch_content`, `receive_date`) SELECT :user_id, :receive_gold, :scratch_num, :scratch_batch, :scratch_content, :receive_date FROM DUAL WHERE NOT EXISTS (SELECT id FROM t_activity_scratch WHERE user_id = :user_id AND scratch_num = :scratch_num AND scratch_batch = :scratch_batch AND receive_date = :receive_date)';
                $result = $this->db->exec($sql, array('user_id' => $this->userId, 'receive_gold' => $content['gold'], 'scratch_num' => $key, 'scratch_batch' => $batch, 'scratch_content' => $content['content'], 'receive_date' => $todayDate));
                if ($result) {
                    $returnList[] = array('bgImg' => $scratchImg, 'isLock' => 0, 'isOpen' => 0, 'number' => $key, 'id' => $this->db->lastInsertId(), 'gold' => $content['gold'], 'type' => 'scratch', 'content' => json_decode($content['content']));
                } else {
                    return new ApiReturn('', 205, '访问失败，请稍后再试');
                }
            }
        }
        return new ApiReturn(array('list' => $returnList, 'currentTime' => time() * 1000, 'endTime' => $endTime));

        //clock 7
        $a = array('bgImg' => 'https://oss.stepcounter.cn/img/scratch_01.png', 'gold' => 1, 'is_lock' => 1, 'isOpen' => 1, 'id' => 1, 'number' => 1, 'type' => 'scratch', 'content' => array(0, 0, 0, 1, 1, 1), 'sort' => '');
    }

    /**
     * 上报观看激励视频解锁刮刮卡接口 todo
     * @return ApiReturn
     */
    public function scratchUnlockAction () {

    }

    /**
     * 领取刮刮卡金币奖励接口 todo
     * @return ApiReturn
     */
    public function scratchAwardAction () {

    }

    /**
     * 获取刮刮卡结果
     * @return array
     */
    protected function __scratchContent () {
        $golds = rand(0, 5);
        $return = array_fill(0,6,0);
        foreach ($return as $k => &$v) {
            if ($k < $golds) {
                $v = 1;
            } else {
                break;
            }
        }
        shuffle($return);
        return array('gold' => $golds * 50 , 'content' => json_encode($return));
    }

    
}

