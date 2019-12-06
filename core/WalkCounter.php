<?php

class walkCounter extends AbstractModel
{
    //领取奖励条件步数
    protected $rewardCounter = 100;
    //领取奖励最小值
    protected $rewardMin = 10;
    //领取奖励最大值
    protected $rewardMax = 30;
    //阶段奖励规则
    protected $stageReward = array(
        1000 => 10,
        3000 => 30,
        5000 => 50,
        10000 => 80);
    protected $stageFormat;
    protected $userId;
    protected $stepCount;
    protected $todayDate;

    /**
     * Constructor
     */
    public function __construct($userId, $stepCount = 0)
    {
        $this->userId = $userId;
        $this->stepCount = $stepCount;
        $this->todayDate = date('Y-m-d');
        if($this->stepCount) {
            $this->calculationReward();
        }
        $stageReward = array();
        array_walk($this->stageReward, function($v, $key) use (&$stageReward) {$stageReward['step' . $key] =$v;});
        $this->stageFormat = $stageReward;
    }
    
    public function unreceivedList () {
        return array('awardCoins1' => $this->__walkList(), 'awardCoins2' => $this->__walkStageList(), 'stageReward' => $this->stageFormat);
    }
    
    public function getReturnInfo ($type) {
        switch ($type) {
            case 'walk':
                $sql = 'SELECT receive_id id, receive_gold num, receive_type type 
                    FROM t_gold2receive 
                    WHERE user_id = ? 
                    AND receive_date = ? 
                    AND receive_type = "walk" 
                    AND receive_status = 0 
                    ORDER BY receive_id LIMIT 5, 1';
                return $this->db->getRow($sql, $this->userId, $this->todayDate);
                break;
            case 'walk_stage':
                return array('awardCoins2' => $this->__walkStageList(), 'stageReward' => $this->stageFormat);
                break;
        }
    }
    
    public function verifyReceive ($data) {
        $sql = 'SELECT COUNT(receive_id) 
                FROM t_gold2receive
                WHERE receive_id =:receive_id
                AND user_id = :user_id
                AND receive_gold = :receive_gold
                AND receive_type = :receive_type
                AND receive_date = :receive_date
                AND receive_status = 0';
        return $this->db->getOne($sql, array(
           'receive_id' => $data['receive_id'],
           'user_id' => $this->userId,
           'receive_gold' => $data['receive_gold'],
           'receive_type' => $data['receive_type'],
           'receive_date' => $this->todayDate,
        ));
    }
    
    public function receiveSuccess ($receiveId) {
        $sql = 'UPDATE t_gold2receive SET receive_status = 1 WHERE receive_id = ?';
        $this->db->exec($sql, $receiveId);
    }
        
    
    protected function calculationReward() {
        $sql = 'REPLACE INTO t_walk SET
                user_id = :user_id,
                total_walk = :total_walk,
                walk_date = :walk_date';
        $this->db->exec($sql, array('user_id' => $this->userId, 'total_walk' => $this->stepCount, 'walk_date' => $this->todayDate));
        
        //插入步数奖励待领取
        $sql = 'SELECT SUM(receive_walk) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk"';
        $receiceStep = $this->db->getOne($sql, $this->userId, $this->todayDate);
        $residualStep = $this->stepCount - $receiceStep;
        while ($residualStep >= $this->rewardCounter) {
            $sql = "INSERT INTO t_gold2receive SET 
                user_id = :user_id,
                receive_date = :receive_date,
                receive_gold = :receive_gold,
                receive_walk = :receive_walk,
                receive_type = 'walk'";
            $this->db->exec($sql, array(
                'user_id' => $this->userId,
                'receive_walk' => $this->rewardCounter, 
                'receive_date' => $this->todayDate, 
                'receive_gold' => rand($this->rewardMin, $this->rewardMax)));
            $residualStep -= $this->rewardCounter;
        }
        
        //插入阶段步数奖励待领取
        $sql = 'SELECT MAX(receive_walk) FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk_stage"';
        $stageStep = $this->db->getOne($sql, $this->userId, $this->todayDate) ?: 0;
        foreach ($this->stageReward as $step => $gold) {
            if ($step > $this->stepCount) {
                break;
            }
            if ($step <= $stageStep) {
                continue;
            }
            $sql = "INSERT INTO t_gold2receive SET 
                user_id = :user_id,
                receive_date = :receive_date,
                receive_gold = :receive_gold,
                receive_walk = :receive_walk,
                receive_type = 'walk_stage'";
            $this->db->exec($sql, array(
                'user_id' => $this->userId,
                'receive_walk' => $step, 
                'receive_date' => $this->todayDate, 
                'receive_gold' => $gold));
        }
    }
    
    protected function __walkList () {
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk" AND receive_status = 0 ORDER BY receive_id LIMIT 5';
        return $this->db->getALL($sql, $this->userId, $this->todayDate);
    }
    
    protected function __walkStageList () {
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type, receive_status isReceived FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk_stage"';
        return $this->db->getAll($sql, $this->userId, $this->todayDate);
    }
}