<?php 

Class WalkController extends AbstractController {
    
    public function awardAction () {
        $token = $_SERVER[HTTP_ACCESSTOKEN];
        $userId = $this->model->user->verifyToken($token);
        if (!isset($this->inputData['stepCount'])) {
            return new ApiReturn('', 301, 'miss step count');
        }
        $walkReward = new WalkCounter($userId, $this->inputData['stepCount']);
        
//        $sql = 'SELECT * FROM t_walk WHERE ';
//        $this->db->getAll();
//        var_dump();
    }
}