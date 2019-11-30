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
        $this->calculationReward();
    }
    
    public function unreceivedList () {
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk" AND receive_status = 0 ORDER BY receive_id LIMIT 5';
        $walk = $this->db->getALL($sql, $this->userId, $this->todayDate);
        
        $sql = 'SELECT receive_id id, receive_gold num, receive_type type, receive_status isReceived FROM t_gold2receive WHERE user_id = ? AND receive_date = ? AND receive_type = "walk_stage"';
        $walkStage = $this->db->getAll($sql, $this->userId, $this->todayDate);
        $stageReward = array();
        array_walk($this->stageReward, function($v, $key) use (&$stageReward) {$stageReward['step_' . $key] =$v;});
        return array('awardCoins1' => $walk, 'awardCoins2' => $walkStage, 'stageReward' => $stageReward);
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
    
}