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
        $sql = 'SELECT COUNT(receive_id) 
                FROM t_gold2receive
                WHERE receive_id =:receive_id
                AND user_id = :user_id
                AND receive_gold = :receive_gold
                AND receive_type = :receive_type
                AND receive_date = :receive_date';
        $receiveInfo = $this->db->getOne($sql, array(
           'receive_id' => $this->inputData['id'] ?? 0,
           'user_id' => $userId,
           'receive_gold' => $this->inputData['num'] ?? 0,
           'receive_type' => $this->inputData['type'] ?? '',
           'receive_date' => date('Y-m-d'),
        ));
        if ($receiveInfo) {
            $updateStatus = $this->model->user->updateGold(array(
                'gold' => $this->inputData['num'],
                'gold_source' => $this->inputData['type'],
                'change_type' => 'in',
                'relation_id' => $this->inputData['id']));
            if (200 == $updateStatus->code) {
                $sql = 'UPDATE t_gold2receive SET receive_status = 1 WHERE receive_id = ?';
                $this->db->exec($sql, $this->inputData['id']);
            }
            return $updateStatus;
        } else {
            return new ApiReturn('', 402, '无效领取');
        }
    }
}