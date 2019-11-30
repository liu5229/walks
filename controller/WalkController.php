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
                switch ($this->inputData['type']) {
                    case 'walk':
                        return new ApiReturn($walkReward->walkList());
                        break;
                    case 'walk_stage':
                        return new ApiReturn($walkReward->walkStageList());
                        break;
                }
            }
            return $updateStatus;
        } else {
            return new ApiReturn('', 402, '无效领取');
        }
    }
}