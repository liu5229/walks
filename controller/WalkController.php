<?php 

Class WalkController extends AbstractController {
    
    public function awardAction () {
        $token = $_SERVER['HTTP_ACCESSTOKEN'];
        $userId = $this->model->user->verifyToken($token);
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 401, 'miss step count');
        }
        $walkReward = new WalkCounter($userId, $this->inputData['stepCount']);
        
        $data = $walkReward->unreceivedList();
        return new ApiReturn($data);
        
//        $sql = 'SELECT * FROM t_walk WHERE ';
//        $this->db->getAll();
//        var_dump();
    }
}